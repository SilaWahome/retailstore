<template>
    <HospitalityLayout>
        <div class="p-4 lg:p-6 max-w-7xl mx-auto">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900">Kitchen Display System (KDS)</h1>
                    <p class="text-sm text-slate-500">Live KOT tickets • Auto-refreshes every 5 seconds</p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-for="st in ['all', 'kitchen', 'bar', 'pastry']"
                        :key="st"
                        @click="stationFilter = st"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold uppercase transition"
                        :class="stationFilter === st ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'"
                    >
                        {{ st }}
                    </button>
                </div>
            </div>

            <div v-if="filteredTickets.length === 0" class="bg-white rounded-2xl border border-dashed border-slate-300 p-16 text-center">
                <p class="text-slate-500 font-semibold">No active kitchen tickets right now.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="ticket in filteredTickets"
                    :key="ticket.id"
                    class="bg-white rounded-2xl border-2 shadow-sm flex flex-col justify-between overflow-hidden"
                    :class="ticketBorderClass(ticket.status)"
                >
                    <div>
                        <!-- Header -->
                        <div class="px-4 py-3 flex items-center justify-between border-b" :class="ticketHeaderClass(ticket.status)">
                            <div>
                                <div class="font-extrabold text-base">
                                    {{ ticket.table ? `Table ${ticket.table.table_number}` : 'Takeaway' }}
                                </div>
                                <div class="text-xs font-semibold opacity-80">
                                    KOT #{{ ticket.id }} • {{ ticket.server?.name || 'Staff' }}
                                </div>
                            </div>
                            <span class="text-[11px] font-extrabold uppercase px-2 py-1 rounded-lg bg-white/80 text-slate-800">
                                {{ ticket.station }}
                            </span>
                        </div>

                        <!-- Items -->
                        <div class="p-4 space-y-3">
                            <div v-for="item in ticket.items" :key="item.id" class="border-b border-slate-100 pb-2 last:border-0 last:pb-0">
                                <div class="font-extrabold text-base text-slate-900">
                                    {{ item.quantity }}x {{ item.item_name }}
                                </div>
                                <div v-if="item.modifiers_summary" class="text-xs font-semibold text-slate-600 mt-0.5">
                                    + {{ item.modifiers_summary }}
                                </div>
                                <div v-if="item.special_notes" class="text-xs font-extrabold text-rose-600 bg-rose-50 px-2 py-1 rounded mt-1 uppercase">
                                    ** {{ item.special_notes }} **
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="p-3 bg-slate-50 border-t border-slate-200 flex gap-2">
                        <button
                            v-if="ticket.status === 'pending'"
                            @click="setStatus(ticket, 'in_prep')"
                            class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs"
                        >
                            Start Prep
                        </button>
                        <button
                            v-if="ticket.status === 'in_prep' || ticket.status === 'pending'"
                            @click="setStatus(ticket, 'ready')"
                            class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs"
                        >
                            Mark Ready
                        </button>
                        <button
                            v-if="ticket.status === 'ready'"
                            @click="setStatus(ticket, 'served')"
                            class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs"
                        >
                            Bump / Served
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </HospitalityLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import HospitalityLayout from '../../Layouts/HospitalityLayout.vue';

const props = defineProps({
    initialTickets: Array,
});

const tickets = ref(props.initialTickets || []);
const stationFilter = ref('all');
let pollTimer = null;

const filteredTickets = computed(() => {
    if (stationFilter.value === 'all') return tickets.value;
    return tickets.value.filter(t => t.station === stationFilter.value);
});

const ticketBorderClass = (status) => {
    if (status === 'ready') return 'border-emerald-500';
    if (status === 'in_prep') return 'border-amber-400';
    return 'border-rose-400';
};

const ticketHeaderClass = (status) => {
    if (status === 'ready') return 'bg-emerald-50 text-emerald-900 border-emerald-200';
    if (status === 'in_prep') return 'bg-amber-50 text-amber-900 border-amber-200';
    return 'bg-rose-50 text-rose-900 border-rose-200';
};

const fetchTickets = async () => {
    try {
        const { data } = await axios.get('/restaurant/kds/tickets');
        tickets.value = data.tickets;
    } catch (_) {}
};

const setStatus = async (ticket, status) => {
    await axios.patch(`/restaurant/kds/tickets/${ticket.id}/status`, { status });
    await fetchTickets();
};

onMounted(() => {
    pollTimer = setInterval(fetchTickets, 5000);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

