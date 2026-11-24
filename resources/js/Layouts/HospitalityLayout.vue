<template>
    <div
        class="min-h-screen flex bg-slate-50 text-slate-900"
        @touchstart.passive="onTouchStart"
        @touchend.passive="onTouchEnd"
    >
        <!-- Mobile Backdrop -->
        <div
            v-if="mobileOpen"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-[1px] z-40 lg:hidden print:hidden"
            @click="mobileOpen = false"
        ></div>

        <!-- Independent Hotel & Hospitality Sidebar -->
        <aside
            class="print:hidden fixed lg:sticky top-0 left-0 z-50 h-screen w-72 lg:w-64 shrink-0 bg-slate-900 text-slate-100 flex flex-col border-r border-slate-800 transition-transform duration-200 lg:translate-x-0 overscroll-contain"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Brand Header -->
            <div class="h-16 px-4 flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white font-extrabold flex items-center justify-center text-lg shadow-sm shrink-0">
                        {{ (tenant?.name || 'H').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-sm leading-tight text-white truncate">
                            {{ tenant?.name || 'Hospitality' }}
                        </div>
                        <div class="text-[11px] font-medium text-slate-400 truncate">
                            {{ tenant?.plan_name || 'Plan' }} • <span class="uppercase">{{ user?.role }}</span>
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    class="lg:hidden text-slate-400 hover:text-white p-1"
                    @click="mobileOpen = false"
                >
                    ✕
                </button>
            </div>

            <!-- Grouped Submenu Navigation -->
            <nav class="flex-1 overflow-y-auto overscroll-contain px-3 py-4 space-y-4" @click="handleNavClick">
                <!-- Hotel & Front Desk -->
                <div v-if="canSeeRoomsNav">
                    <button
                        type="button"
                        @click.stop="openGroups.hotel = !openGroups.hotel"
                        class="w-full min-h-[40px] flex items-center justify-between px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 hover:text-slate-200 active:scale-[0.98] touch-manipulation"
                    >
                        <span>Hotel & Front Desk</span>
                        <span>{{ openGroups.hotel ? '▾' : '▸' }}</span>
                    </button>
                    <div v-show="openGroups.hotel" class="mt-1 space-y-1">
                        <Link
                            v-if="hasFeature('room_booking')"
                            :href="appUrl('/hotel/rooms')"
                            class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-semibold transition active:scale-[0.98] touch-manipulation"
                            :class="isActive('/hotel') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                        >
                            <span>🛏️</span>
                            <span>Rooms & Folios</span>
                        </Link>
                        <a
                            v-else
                            :href="appUrl('/support')"
                            class="min-h-[44px] flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 active:scale-[0.98] touch-manipulation"
                            title="Upgrade to Hotel Starter (UGX 170k) to unlock Room Bookings"
                        >
                            <span>🛏️ Rooms</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300">Pro</span>
                        </a>
                    </div>
                </div>

                <!-- Restaurant & Kitchen (POS / KOT) -->
                <div v-if="canSeeTables || canSeeKds || isAdminOrManager">
                    <button
                        type="button"
                        @click.stop="openGroups.restaurant = !openGroups.restaurant"
                        class="w-full min-h-[40px] flex items-center justify-between px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 hover:text-slate-200 active:scale-[0.98] touch-manipulation"
                    >
                        <span>Restaurant & KOT</span>
                        <span>{{ openGroups.restaurant ? '▾' : '▸' }}</span>
                    </button>
                    <div v-show="openGroups.restaurant" class="mt-1 space-y-1">
                        <Link
                            v-if="canSeeTables"
                            :href="appUrl('/restaurant/tables')"
                            class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-semibold transition active:scale-[0.98] touch-manipulation"
                            :class="isActive('/restaurant/tables') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                        >
                            <span>🍽️</span>
                            <span>Floor & Table POS</span>
                        </Link>

                        <Link
                            v-if="canSeeKds"
                            :href="appUrl('/restaurant/kds')"
                            class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-semibold transition active:scale-[0.98] touch-manipulation"
                            :class="isActive('/restaurant/kds') ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                        >
                            <span>🍳</span>
                            <span>Kitchen (KDS / KOT)</span>
                        </Link>

                        <Link
                            v-if="isAdminOrManager"
                            :href="appUrl('/restaurant/menu-setup')"
                            class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-semibold transition active:scale-[0.98] touch-manipulation"
                            :class="isActive('/restaurant/menu-setup') ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                        >
                            <span>📋</span>
                            <span>Menu & BOM Setup</span>
                        </Link>
                    </div>
                </div>

                <!-- Back Office & Reports (Owner / Manager / Accountant) -->
                <div v-if="canViewReports || isAdminOrManager">
                    <button
                        type="button"
                        @click.stop="openGroups.backoffice = !openGroups.backoffice"
                        class="w-full min-h-[40px] flex items-center justify-between px-2.5 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 hover:text-slate-200 active:scale-[0.98] touch-manipulation"
                    >
                        <span>Back Office & Reports</span>
                        <span>{{ openGroups.backoffice ? '▾' : '▸' }}</span>
                    </button>
                    <div v-show="openGroups.backoffice" class="mt-1 space-y-1">
                        <a v-if="isAdminOrManager" :href="appUrl('/sales')" class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white active:scale-[0.98] touch-manipulation transition">
                            <span>📊</span><span>Sales Overview</span>
                        </a>
                        <a v-if="canViewReports" :href="appUrl('/reports')" class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white active:scale-[0.98] touch-manipulation transition">
                            <span>📈</span><span>Reports & Exports</span>
                        </a>
                        <a v-if="isAdminOrManager" :href="appUrl('/products')" class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white active:scale-[0.98] touch-manipulation transition">
                            <span>📦</span><span>Stock & Products</span>
                        </a>
                        <a v-if="canViewReports" :href="appUrl('/purchase-orders')" class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white active:scale-[0.98] touch-manipulation transition">
                            <span>🧾</span><span>Purchase Orders</span>
                        </a>
                        <a v-if="canViewReports" :href="appUrl('/expenses')" class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white active:scale-[0.98] touch-manipulation transition">
                            <span>💸</span><span>Daily Expenses</span>
                        </a>
                        <a v-if="isAdmin" :href="appUrl('/users')" class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white active:scale-[0.98] touch-manipulation transition">
                            <span>👥</span><span>Staff & Users</span>
                        </a>
                        <a v-if="isAdmin" :href="appUrl('/branches')" class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white active:scale-[0.98] touch-manipulation transition">
                            <span>🏢</span><span>Branches</span>
                        </a>
                        <a v-if="isAdmin" :href="appUrl('/settings')" class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white active:scale-[0.98] touch-manipulation transition">
                            <span>⚙️</span><span>Settings</span>
                        </a>
                        <a :href="appUrl('/support')" class="min-h-[44px] flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white active:scale-[0.98] touch-manipulation transition">
                            <span>💬</span><span>Support & Billing</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-3 border-t border-slate-800 flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <div class="text-xs font-semibold text-white truncate">{{ user?.name }}</div>
                    <div class="text-[11px] text-slate-400 uppercase">{{ user?.role }}</div>
                </div>
                <a
                    :href="appUrl('/logout')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 transition shrink-0"
                >
                    Sign Out
                </a>
            </div>
        </aside>

        <!-- Main Workspace -->
        <div class="flex-1 min-w-0 flex flex-col">
            <!-- Slim Mobile / Context Topbar -->
            <header class="print:hidden bg-white border-b border-slate-200 px-4 lg:px-6 h-14 flex items-center justify-between sticky top-0 z-30 lg:hidden">
                <button
                    type="button"
                    class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold"
                    @click="mobileOpen = true"
                >
                    ☰ Menu
                </button>
                <div class="font-bold text-sm text-slate-900 truncate">{{ tenant?.name || 'Hospitality' }}</div>
                <a :href="appUrl('/logout')" class="text-xs font-semibold text-slate-600">Sign Out</a>
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
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { appUrl } from '../utils/url';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const tenant = computed(() => page.props.auth?.tenant);
const flash = computed(() => page.props.flash);

const mobileOpen = ref(false);
const openGroups = reactive({
    hotel: true,
    restaurant: true,
    backoffice: false,
});

let touchStartX = 0;
let touchStartY = 0;

const onTouchStart = (e) => {
    const t = e.changedTouches?.[0];
    if (!t) return;
    touchStartX = t.clientX;
    touchStartY = t.clientY;
};

const onTouchEnd = (e) => {
    const t = e.changedTouches?.[0];
    if (!t) return;
    const dx = t.clientX - touchStartX;
    const dy = Math.abs(t.clientY - touchStartY);
    if (dy > 50) return; // ignore vertical scroll
    // Swipe right from left edge opens drawer
    if (!mobileOpen.value && touchStartX < 36 && dx > 55) {
        mobileOpen.value = true;
    } else if (mobileOpen.value && dx < -55) {
        // Swipe left closes drawer
        mobileOpen.value = false;
    }
};

const handleNavClick = (e) => {
    if (e.target.closest('a')) {
        mobileOpen.value = false;
    }
};

const hasFeature = (key) => (tenant.value?.features || []).includes(key);

const isAdmin = computed(() => ['admin', 'superadmin'].includes(user.value?.role));
const isAdminOrManager = computed(() => ['admin', 'manager', 'superadmin'].includes(user.value?.role));
const canViewReports = computed(() => ['admin', 'manager', 'accountant', 'superadmin'].includes(user.value?.role));
const canSeeTables = computed(() => ['admin', 'manager', 'cashier', 'receptionist', 'superadmin'].includes(user.value?.role) && hasFeature('table_management'));
const canSeeKds = computed(() => ['admin', 'manager', 'kitchen', 'superadmin'].includes(user.value?.role) && hasFeature('kds_display'));
const canSeeRoomsNav = computed(() => ['admin', 'manager', 'receptionist', 'superadmin'].includes(user.value?.role));

const isActive = (prefix) => page.url.includes(prefix);
</script>
