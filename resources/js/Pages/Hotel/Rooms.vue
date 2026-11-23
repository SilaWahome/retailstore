<template>
    <HospitalityLayout>
        <div class="p-4 lg:p-6 max-w-7xl mx-auto space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900">Rooms Overview • Room Rack</h1>
                    <p class="text-sm text-slate-500">
                        Manage check-ins, housekeeping, and guest folios.
                        <span v-if="roomLimit" class="font-bold text-amber-700">
                            ({{ rooms.length }} / {{ roomLimit }} rooms used on Hotel Starter)
                        </span>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-if="isAdminOrManager"
                        @click="showRoomTypeModal = true"
                        class="px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        + Room Type
                    </button>
                    <button
                        v-if="isAdminOrManager && roomTypes.length > 0"
                        @click="showRoomModal = true"
                        class="px-3.5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700"
                    >
                        + Add Room
                    </button>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-600">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Available</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-600"></span> Occupied</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-400"></span> Cleaning</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-purple-500"></span> Reserved</span>
            </div>

            <!-- Room Rack Grid -->
            <div v-if="rooms.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <div
                    v-for="room in rooms"
                    :key="room.id"
                    class="p-4 rounded-2xl border-2 shadow-sm flex flex-col justify-between min-h-[175px]"
                    :class="roomCardClass(room.status)"
                >
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-extrabold">Room {{ room.room_number }}</span>
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-white/80">
                                {{ room.status }}
                            </span>
                        </div>
                        <div class="text-xs font-semibold opacity-75 mt-0.5">{{ room.room_type?.name }}</div>
                    </div>

                    <!-- Occupied Info -->
                    <div v-if="room.status === 'occupied' && room.active_booking" class="mt-3 space-y-1 text-xs">
                        <div class="font-bold text-sm">{{ room.active_booking.guest_name }}</div>
                        <div>Out: {{ formatDate(room.active_booking.check_out_date) }}</div>
                        <div v-if="room.active_booking.folio" class="font-extrabold">
                            Folio Balance: {{ formatUgx(folioBalance(room.active_booking.folio)) }}
                        </div>
                    </div>

                    <!-- Available Rate -->
                    <div v-else-if="room.status === 'available'" class="mt-3 text-sm font-extrabold">
                        {{ formatUgx(room.room_type?.base_price_ugx) }} / night
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 pt-3 border-t border-black/10 flex gap-2">
                        <button
                            v-if="room.status === 'available'"
                            @click="openBookingModal(room)"
                            class="flex-1 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700"
                        >
                            Check-In / Book
                        </button>

                        <button
                            v-if="room.status === 'cleaning'"
                            @click="markClean(room)"
                            class="flex-1 py-2 rounded-xl bg-yellow-500 text-white text-xs font-bold hover:bg-yellow-600"
                        >
                            Mark Clean
                        </button>

                        <template v-if="room.status === 'occupied' && room.active_booking">
                            <Link
                                v-if="canFolio"
                                :href="appUrl(`/hotel/bookings/${room.active_booking.id}/folio`)"
                                class="flex-1 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold text-center hover:bg-blue-700"
                            >
                                Folio
                            </Link>
                            <button
                                @click="checkOut(room.active_booking)"
                                class="px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800"
                            >
                                Out
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                <p class="font-semibold text-slate-600 mb-2">No rooms configured yet.</p>
                <p class="text-sm text-slate-400">Create a Room Type (e.g. Deluxe Double) first, then add physical rooms.</p>
            </div>

            <!-- Upcoming Confirmed Reservations -->
            <div v-if="confirmedBookings.length" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-200 font-extrabold text-sm">Upcoming Reservations</div>
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Guest</th>
                            <th class="px-4 py-3">Room</th>
                            <th class="px-4 py-3">Check-In</th>
                            <th class="px-4 py-3">Check-Out</th>
                            <th class="px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="b in confirmedBookings" :key="b.id">
                            <td class="px-4 py-3 font-bold">{{ b.guest_name }} ({{ b.guest_phone }})</td>
                            <td class="px-4 py-3">Room {{ b.room?.room_number }}</td>
                            <td class="px-4 py-3">{{ formatDate(b.check_in_date) }}</td>
                            <td class="px-4 py-3">{{ formatDate(b.check_out_date) }}</td>
                            <td class="px-4 py-3">
                                <button @click="checkInBooking(b)" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold">
                                    Check In Now
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BOOKING / CHECK-IN MODAL -->
        <div v-if="bookingRoom" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <form @submit.prevent="submitBooking" class="bg-white rounded-2xl max-w-md w-full p-5 shadow-xl space-y-3">
                <h3 class="font-extrabold text-lg">Room {{ bookingRoom.room_number }} — Guest Stay</h3>
                <input v-model="bookingForm.guest_name" type="text" required placeholder="Guest Full Name" class="w-full px-3 py-2 rounded-xl border text-sm" />
                <div class="grid grid-cols-2 gap-2">
                    <input v-model="bookingForm.guest_phone" type="text" required placeholder="Phone / WhatsApp" class="px-3 py-2 rounded-xl border text-sm" />
                    <input v-model="bookingForm.guest_id_number" type="text" placeholder="Passport / ID #" class="px-3 py-2 rounded-xl border text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Check-in</label>
                        <input v-model="bookingForm.check_in_date" type="date" required class="w-full px-3 py-2 rounded-xl border text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Check-out</label>
                        <input v-model="bookingForm.check_out_date" type="date" required class="w-full px-3 py-2 rounded-xl border text-sm" />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" v-model="bookingForm.check_in_now" />
                    <span>Check in guest immediately (Open Folio)</span>
                </label>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="bookingRoom = null" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold">Confirm</button>
                </div>
            </form>
        </div>

        <!-- ROOM TYPE MODAL -->
        <div v-if="showRoomTypeModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <form @submit.prevent="submitRoomType" class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-xl space-y-3">
                <h3 class="font-extrabold text-lg">New Room Type</h3>
                <input v-model="roomTypeForm.name" type="text" required placeholder="e.g. Deluxe Double" class="w-full px-3 py-2 rounded-xl border text-sm" />
                <input v-model.number="roomTypeForm.base_price_ugx" type="number" min="0" required placeholder="Nightly Rate (UGX)" class="w-full px-3 py-2 rounded-xl border text-sm" />
                <input v-model.number="roomTypeForm.capacity" type="number" min="1" required placeholder="Max Guests" class="w-full px-3 py-2 rounded-xl border text-sm" />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showRoomTypeModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-bold">Save</button>
                </div>
            </form>
        </div>

        <!-- ADD ROOM MODAL -->
        <div v-if="showRoomModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <form @submit.prevent="submitRoom" class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-xl space-y-3">
                <h3 class="font-extrabold text-lg">Add Physical Room</h3>
                <select v-model="roomForm.room_type_id" required class="w-full px-3 py-2 rounded-xl border text-sm">
                    <option v-for="rt in roomTypes" :key="rt.id" :value="rt.id">{{ rt.name }} ({{ formatUgx(rt.base_price_ugx) }})</option>
                </select>
                <input v-model="roomForm.room_number" type="text" required placeholder="Room # (e.g. 101, Villa 3)" class="w-full px-3 py-2 rounded-xl border text-sm" />
                <input v-model="roomForm.floor" type="text" placeholder="Wing / Floor (optional)" class="w-full px-3 py-2 rounded-xl border text-sm" />
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showRoomModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-bold">Add Room</button>
                </div>
            </form>
        </div>
    </HospitalityLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import HospitalityLayout from '../../Layouts/HospitalityLayout.vue';
import { appUrl } from '../../utils/url';

const props = defineProps({
    rooms: Array,
    roomTypes: Array,
    bookings: Array,
    roomLimit: Number,
    canFolio: Boolean,
});

const page = usePage();
const isAdminOrManager = computed(() => ['admin', 'manager', 'superadmin'].includes(page.props.auth?.user?.role));
const confirmedBookings = computed(() => (props.bookings || []).filter(b => b.status === 'confirmed'));

const showRoomTypeModal = ref(false);
const showRoomModal = ref(false);
const bookingRoom = ref(null);

const roomTypeForm = reactive({ name: '', base_price_ugx: '', capacity: 2 });
const roomForm = reactive({ room_type_id: props.roomTypes?.[0]?.id || '', room_number: '', floor: '' });
const bookingForm = reactive({
    room_id: '',
    guest_name: '',
    guest_phone: '',
    guest_id_number: '',
    check_in_date: new Date().toISOString().slice(0, 10),
    check_out_date: new Date(Date.now() + 86400000).toISOString().slice(0, 10),
    check_in_now: true,
});

const formatUgx = (val) => 'UGX ' + Math.round(Number(val || 0)).toLocaleString();
const formatDate = (d) => d ? String(d).slice(0, 10) : '';
const folioBalance = (folio) => Math.max(0, Number(folio.total_charges_ugx) - Number(folio.total_paid_ugx));

const roomCardClass = (status) => {
    switch (status) {
        case 'occupied': return 'bg-blue-50 border-blue-400 text-blue-950';
        case 'cleaning': return 'bg-yellow-50 border-yellow-400 text-yellow-950';
        case 'reserved': return 'bg-purple-50 border-purple-400 text-purple-950';
        case 'maintenance': return 'bg-slate-100 border-slate-400 text-slate-700';
        default: return 'bg-emerald-50 border-emerald-400 text-emerald-950';
    }
};

const openBookingModal = (room) => {
    bookingRoom.value = room;
    bookingForm.room_id = room.id;
};

const submitBooking = () => {
    router.post(appUrl('/hotel/bookings'), bookingForm, {
        onSuccess: () => { bookingRoom.value = null; bookingForm.guest_name = ''; bookingForm.guest_phone = ''; },
    });
};

const markClean = async (room) => {
    await axios.patch(`/hotel/rooms/${room.id}/status`, { status: 'available' });
    router.reload({ only: ['rooms'] });
};

const checkInBooking = (b) => router.post(appUrl(`/hotel/bookings/${b.id}/check-in`));
const checkOut = (b) => router.post(appUrl(`/hotel/bookings/${b.id}/check-out`));

const submitRoomType = () => router.post(appUrl('/hotel/room-types'), roomTypeForm, { onSuccess: () => { showRoomTypeModal.value = false; roomTypeForm.name = ''; } });
const submitRoom = () => router.post(appUrl('/hotel/rooms'), roomForm, { onSuccess: () => { showRoomModal.value = false; roomForm.room_number = ''; } });
</script>

