<template>
    <div class="min-h-screen flex flex-col bg-slate-50 text-slate-900">
        <!-- Top Navigation Bar (hidden on print) -->
        <header class="print:hidden bg-white border-b border-slate-200 px-4 lg:px-6 h-16 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white font-extrabold flex items-center justify-center text-lg shadow-sm">
                        L
                    </div>
                    <div>
                        <div class="font-bold text-sm leading-tight text-slate-900">{{ tenant?.name || 'Hospitality POS' }}</div>
                        <div class="text-[11px] font-medium text-slate-500">
                            {{ tenant?.plan_name || 'Plan' }} • <span class="uppercase">{{ user?.role }}</span>
                        </div>
                    </div>
                </div>

                <nav class="hidden md:flex items-center gap-1.5">
                    <!-- Tables & POS (Cashier, Receptionist, Manager, Admin) -->
                    <Link
                        v-if="canSeeTables"
                        :href="appUrl('/restaurant/tables')"
                        class="px-3.5 py-2 rounded-lg text-sm font-semibold transition"
                        :class="isActive('/restaurant/tables') ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        Floor & POS
                    </Link>

                    <!-- Kitchen Display System (Kitchen, Manager, Admin) -->
                    <Link
                        v-if="canSeeKds"
                        :href="appUrl('/restaurant/kds')"
                        class="px-3.5 py-2 rounded-lg text-sm font-semibold transition"
                        :class="isActive('/restaurant/kds') ? 'bg-amber-50 text-amber-700' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        Kitchen (KDS)
                    </Link>

                    <!-- Hotel Rooms (Receptionist, Manager, Admin) -->
                    <template v-if="canSeeRoomsNav">
                        <Link
                            v-if="hasFeature('room_booking')"
                            :href="appUrl('/hotel/rooms')"
                            class="px-3.5 py-2 rounded-lg text-sm font-semibold transition"
                            :class="isActive('/hotel') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100'"
                        >
                            Rooms & Folios
                        </Link>
                        <a
                            v-else
                            :href="appUrl('/support')"
                            class="px-3.5 py-2 rounded-lg text-sm font-medium text-slate-400 hover:bg-slate-100 flex items-center gap-1.5"
                            title="Upgrade to Hotel Starter (UGX 170k) to unlock Room Bookings"
                        >
                            <span>Rooms</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">Pro</span>
                        </a>
                    </template>

                    <!-- Menu & BOM Setup (Admin, Manager) -->
                    <Link
                        v-if="isAdminOrManager"
                        :href="appUrl('/restaurant/menu-setup')"
                        class="px-3.5 py-2 rounded-lg text-sm font-semibold transition"
                        :class="isActive('/restaurant/menu-setup') ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        Menu & BOM
                    </Link>

                    <!-- Back to Classic Admin (Sales, Users, Reports) -->
                    <a
                        v-if="isAdminOrManager"
                        :href="appUrl('/sales')"
                        class="px-3.5 py-2 rounded-lg text-sm font-medium text-slate-500 hover:bg-slate-100"
                    >
                        Admin & Reports
                    </a>
                </nav>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline text-xs font-medium text-slate-500">
                    {{ user?.name }}
                </span>
                <a
                    :href="appUrl('/logout')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                >
                    Sign Out
                </a>
            </div>
        </header>

        <!-- Flash Banners -->
        <div v-if="flash?.success || flash?.error" class="print:hidden px-4 lg:px-6 pt-3">
            <div v-if="flash.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2.5 rounded-xl text-sm font-medium">
                {{ flash.success }}
            </div>
            <div v-if="flash.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-2.5 rounded-xl text-sm font-medium">
                {{ flash.error }}
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { appUrl } from '../utils/url';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const tenant = computed(() => page.props.auth?.tenant);
const flash = computed(() => page.props.flash);

const hasFeature = (key) => (tenant.value?.features || []).includes(key);

const isAdminOrManager = computed(() => ['admin', 'manager', 'superadmin'].includes(user.value?.role));
const canSeeTables = computed(() => ['admin', 'manager', 'cashier', 'receptionist', 'superadmin'].includes(user.value?.role) && hasFeature('table_management'));
const canSeeKds = computed(() => ['admin', 'manager', 'kitchen', 'superadmin'].includes(user.value?.role) && hasFeature('kds_display'));
const canSeeRoomsNav = computed(() => ['admin', 'manager', 'receptionist', 'superadmin'].includes(user.value?.role));

const isActive = (prefix) => page.url.includes(prefix);
</script>

