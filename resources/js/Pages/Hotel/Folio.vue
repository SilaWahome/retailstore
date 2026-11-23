<template>
    <HospitalityLayout>
        <div class="p-4 lg:p-6 max-w-5xl mx-auto space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
                <div>
                    <Link :href="appUrl('/hotel/rooms')" class="text-xs font-bold text-blue-600 hover:underline">← Back to Room Rack</Link>
                    <h1 class="text-2xl font-extrabold text-slate-900 mt-1">
                        Guest Folio — Room {{ booking.room?.room_number }}
                    </h1>
                    <p class="text-sm text-slate-500">
                        {{ booking.guest_name }} • {{ booking.guest_phone }} • {{ booking.status.toUpperCase() }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button @click="printInvoice" class="px-4 py-2.5 rounded-xl bg-white border border-slate-300 text-sm font-bold text-slate-700">
                        Print Final Invoice
                    </button>
                    <button
                        v-if="booking.status === 'checked_in'"
                        @click="checkOut"
                        class="px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-800"
                    >
                        Check Out Guest
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200">
                    <div class="text-xs font-bold text-slate-500 uppercase">Total Charges</div>
                    <div class="text-xl font-extrabold text-slate-900 mt-1">{{ formatUgx(folio.total_charges_ugx) }}</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200">
                    <div class="text-xs font-bold text-slate-500 uppercase">Total Paid</div>
                    <div class="text-xl font-extrabold text-emerald-700 mt-1">{{ formatUgx(folio.total_paid_ugx) }}</div>
                </div>
                <div class="bg-white p-4 rounded-2xl border border-slate-200">
                    <div class="text-xs font-bold text-slate-500 uppercase">Balance Due</div>
                    <div class="text-xl font-extrabold text-rose-600 mt-1">{{ formatUgx(balanceDue) }}</div>
                </div>
            </div>

            <!-- Add Charge & Record Payment (hidden on print) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 print:hidden">
                <form @submit.prevent="submitCharge" class="bg-white p-4 rounded-2xl border border-slate-200 space-y-3">
                    <h2 class="font-extrabold text-sm">Post Extra Charge</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <select v-model="chargeForm.source" class="px-3 py-2 rounded-xl border text-sm">
                            <option value="laundry">Laundry</option>
                            <option value="bar">Bar</option>
                            <option value="extra_bed">Extra Bed</option>
                            <option value="other">Other</option>
                        </select>
                        <input v-model.number="chargeForm.amount_ugx" type="number" min="1" required placeholder="Amount (UGX)" class="px-3 py-2 rounded-xl border text-sm" />
                    </div>
                    <input v-model="chargeForm.description" type="text" required placeholder="Description" class="w-full px-3 py-2 rounded-xl border text-sm" />
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 text-white font-bold text-xs">Add Charge to Folio</button>
                </form>

                <form @submit.prevent="submitPayment" class="bg-white p-4 rounded-2xl border border-slate-200 space-y-3">
                    <h2 class="font-extrabold text-sm">Record Payment</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <select v-model="paymentForm.payment_method" class="px-3 py-2 rounded-xl border text-sm">
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                        <input v-model.number="paymentForm.amount_ugx" type="number" min="1" required placeholder="Amount (UGX)" class="px-3 py-2 rounded-xl border text-sm" />
                    </div>
                    <input v-model="paymentForm.reference" type="text" placeholder="Receipt / MoMo Ref (optional)" class="w-full px-3 py-2 rounded-xl border text-sm" />
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs">Record Payment</button>
                </form>
            </div>

            <!-- Charges Ledger -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-200 font-extrabold text-sm">Itemized Folio Charges</div>
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in folio.charges" :key="c.id">
                            <td class="px-4 py-3 text-xs text-slate-500">{{ String(c.created_at).slice(0, 10) }}</td>
                            <td class="px-4 py-3 uppercase text-xs font-bold">{{ c.source.replace('_', ' ') }}</td>
                            <td class="px-4 py-3">{{ c.description }}</td>
                            <td class="px-4 py-3 text-right font-bold">{{ formatUgx(c.amount_ugx) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </HospitalityLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import HospitalityLayout from '../../Layouts/HospitalityLayout.vue';
import { appUrl } from '../../utils/url';

const props = defineProps({
    booking: Object,
});

const folio = computed(() => props.booking.folio);
const balanceDue = computed(() => Math.max(0, Number(folio.value.total_charges_ugx) - Number(folio.value.total_paid_ugx)));

const chargeForm = reactive({ source: 'laundry', description: '', amount_ugx: '' });
const paymentForm = reactive({ payment_method: 'cash', amount_ugx: '', reference: '' });

const formatUgx = (val) => 'UGX ' + Math.round(Number(val || 0)).toLocaleString();

const submitCharge = () => router.post(appUrl(`/hotel/folios/${folio.value.id}/charges`), chargeForm, {
    onSuccess: () => { chargeForm.description = ''; chargeForm.amount_ugx = ''; },
});

const submitPayment = () => router.post(appUrl(`/hotel/folios/${folio.value.id}/payments`), paymentForm, {
    onSuccess: () => { paymentForm.amount_ugx = ''; paymentForm.reference = ''; },
});

const checkOut = () => router.post(appUrl(`/hotel/bookings/${props.booking.id}/check-out`));
const printInvoice = () => window.print();
</script>

