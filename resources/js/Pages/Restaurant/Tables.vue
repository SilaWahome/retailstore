<template>
    <HospitalityLayout>
        <!-- VIEW 1: FLOOR PLAN -->
        <div v-if="!activeTable" class="p-4 lg:p-6 max-w-7xl mx-auto">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Floor Plan & Tables</h1>
                    <p class="text-sm text-slate-500">Tap any table to open or manage an order.</p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="bg-white border border-slate-200 rounded-xl p-1 flex items-center">
                        <button
                            @click="viewMode = '3d'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="viewMode === '3d' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                        >
                            3D Floor
                        </button>
                        <button
                            @click="viewMode = 'grid'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                            :class="viewMode === 'grid' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                        >
                            Grid View
                        </button>
                    </div>
                    <button
                        v-if="isAdminOrManager"
                        @click="showAreaModal = true"
                        class="px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                    >
                        + Area
                    </button>
                    <button
                        v-if="isAdminOrManager && areas.length > 0"
                        @click="showTableModal = true"
                        class="px-3.5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 shadow-sm"
                    >
                        + Add Table
                    </button>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-600 mb-5">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Available</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500"></span> Occupied (In Prep)</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-sky-600"></span> Bill Printed</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-400"></span> Cleaning</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-purple-500"></span> Reserved</span>
            </div>

            <!-- Area Tabs -->
            <div v-if="areas.length > 0" class="flex gap-2 overflow-x-auto pb-2 mb-6">
                <button
                    v-for="area in areas"
                    :key="area.id"
                    @click="selectedAreaId = area.id"
                    class="px-4 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition"
                    :class="selectedAreaId === area.id ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
                >
                    {{ area.name }} ({{ area.tables.length }})
                </button>
            </div>

            <div v-else class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                <p class="text-slate-600 font-semibold mb-2">No dining areas configured yet.</p>
                <p class="text-sm text-slate-400 mb-4">Create your first dining area (e.g. Main Dining, Terrace, Bar) to add tables.</p>
                <button
                    v-if="isAdminOrManager"
                    @click="showAreaModal = true"
                    class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold text-sm hover:bg-emerald-700"
                >
                    Create First Dining Area
                </button>
            </div>

            <!-- 3D Floor Plan View -->
            <FloorPlan3D
                v-if="currentArea && viewMode === '3d'"
                :area="currentArea"
                :can-edit="isAdminOrManager"
                @select-table="openTable"
                @layout-saved="router.reload({ only: ['areas'] })"
            />

            <!-- Table Grid View -->
            <div v-if="currentArea && viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <button
                    v-for="table in currentArea.tables"
                    :key="table.id"
                    @click="openTable(table)"
                    class="text-left p-5 rounded-2xl border-2 transition active:scale-95 shadow-sm flex flex-col justify-between min-h-[140px]"
                    :class="tableCardClass(table)"
                >
                    <div class="flex items-start justify-between">
                        <span class="text-xl font-extrabold">{{ table.table_number }}</span>
                        <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded-full bg-white/80">
                            {{ tableDisplayStatus(table) }}
                        </span>
                    </div>

                    <div class="mt-4">
                        <div class="text-xs opacity-80 font-medium">
                            Seats: {{ table.seating_capacity }}
                            <span v-if="table.current_order"> • Guests: {{ table.current_order.guest_count }}</span>
                        </div>
                        <div v-if="table.current_order" class="text-base font-extrabold mt-1">
                            {{ formatUgx(table.current_order.total_amount_ugx) }}
                        </div>
                        <div v-else class="text-xs font-semibold mt-1 opacity-75">
                            Tap to start order
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <!-- VIEW 2: 3-COLUMN LIONPOS ORDER TERMINAL -->
        <div v-else class="flex flex-col lg:flex-row h-[calc(100vh-4rem)] overflow-hidden print:hidden">
            <!-- Col 1: Categories Sidebar -->
            <aside class="w-full lg:w-52 bg-white border-b lg:border-b-0 lg:border-r border-slate-200 p-3 flex lg:flex-col gap-1.5 overflow-x-auto lg:overflow-y-auto shrink-0">
                <button
                    @click="activeTable = null"
                    class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm text-left shrink-0 lg:mb-2"
                >
                    ← Floor Plan
                </button>
                <button
                    @click="selectedCategoryId = null"
                    class="px-3.5 py-3 rounded-xl text-sm font-bold text-left whitespace-nowrap transition"
                    :class="selectedCategoryId === null ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                >
                    All Menu Items
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategoryId = cat.id"
                    class="px-3.5 py-3 rounded-xl text-sm font-bold text-left whitespace-nowrap transition"
                    :class="selectedCategoryId === cat.id ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                >
                    {{ cat.name }}
                </button>
            </aside>

            <!-- Col 2: Menu Item Grid -->
            <section class="flex-1 p-4 overflow-y-auto bg-slate-50">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search product, dish, or drink..."
                        class="w-full max-w-md px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    />
                    <div class="text-xs font-semibold text-slate-500 hidden sm:block">
                        {{ filteredMenuItems.length }} items
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5">
                    <button
                        v-for="item in filteredMenuItems"
                        :key="item.id"
                        @click="handleMenuItemClick(item)"
                        class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm hover:border-emerald-500 active:scale-95 transition text-left flex flex-col justify-between min-h-[104px]"
                    >
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-bold text-slate-900 text-sm leading-snug">{{ item.name }}</span>
                                <span
                                    class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 shrink-0"
                                >
                                    {{ item.station }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="font-extrabold text-emerald-700 text-sm">{{ formatUgx(item.price_ugx) }}</span>
                            <span v-if="item.modifier_groups?.length" class="text-[10px] font-semibold text-amber-600">
                                + Modifiers
                            </span>
                        </div>
                    </button>
                </div>
            </section>

            <!-- Col 3: Active Ticket Sidebar -->
            <aside class="w-full lg:w-96 bg-white border-t lg:border-t-0 lg:border-l border-slate-200 flex flex-col shrink-0">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="font-extrabold text-lg text-slate-900">Table {{ activeTable.table_number }}</h2>
                        <p class="text-xs text-slate-500">
                            {{ activeOrder ? `Order #${activeOrder.id} • ${activeOrder.status.toUpperCase()}` : 'New Open Ticket' }}
                        </p>
                    </div>
                    <button
                        v-if="activeTable.status === 'cleaning'"
                        @click="markTableAvailable(activeTable)"
                        class="px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-bold"
                    >
                        Mark Clean
                    </button>
                </div>

                <!-- Ticket Lines -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <!-- Fired Items (Already in DB) -->
                    <div v-if="activeOrder?.items?.length">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Fired to Kitchen</div>
                        <div class="space-y-2">
                            <div
                                v-for="line in activeOrder.items"
                                :key="'fired-' + line.id"
                                class="flex justify-between items-start text-sm bg-slate-50 p-2.5 rounded-xl"
                            >
                                <div>
                                    <div class="font-semibold text-slate-800">
                                        {{ line.quantity }}x {{ line.item_name }}
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 ml-1">
                                            {{ line.status }}
                                        </span>
                                    </div>
                                    <div v-if="line.modifiers?.length" class="text-xs text-slate-500">
                                        + {{ line.modifiers.map(m => m.name).join(', ') }}
                                    </div>
                                    <div v-if="line.special_notes" class="text-xs text-rose-600 font-semibold">
                                        Note: {{ line.special_notes }}
                                    </div>
                                </div>
                                <div class="font-bold text-slate-900 whitespace-nowrap">
                                    {{ formatUgx((Number(line.unit_price) + Number(line.modifiers_total)) * line.quantity) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Items (Not Yet Fired) -->
                    <div v-if="pendingCart.length">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 mb-2">Pending (Ready to Fire)</div>
                        <div class="space-y-2">
                            <div
                                v-for="(line, idx) in pendingCart"
                                :key="'pend-' + idx"
                                class="flex justify-between items-start text-sm bg-emerald-50/60 border border-emerald-200 p-2.5 rounded-xl"
                            >
                                <div class="flex-1 pr-2">
                                    <div class="font-bold text-slate-900">{{ line.quantity }}x {{ line.name }}</div>
                                    <div v-if="line.modifiers?.length" class="text-xs text-slate-600">
                                        + {{ line.modifiers.map(m => m.name).join(', ') }}
                                    </div>
                                    <div v-if="line.special_notes" class="text-xs text-rose-600 font-semibold">
                                        Note: {{ line.special_notes }}
                                    </div>
                                    <div class="flex items-center gap-2.5 mt-2">
                                        <button @click="decrementPending(idx)" class="w-9 h-9 rounded-lg bg-white border border-slate-300 text-sm font-extrabold active:scale-95 touch-manipulation">-</button>
                                        <span class="text-xs font-bold">Seat {{ line.seat_number }}</span>
                                        <button @click="line.quantity++" class="w-9 h-9 rounded-lg bg-white border border-slate-300 text-sm font-extrabold active:scale-95 touch-manipulation">+</button>
                                    </div>
                                </div>
                                <div class="font-bold text-slate-900 whitespace-nowrap">
                                    {{ formatUgx(lineTotal(line)) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!activeOrder?.items?.length && !pendingCart.length" class="text-center py-10 text-slate-400 text-sm">
                        Tap menu items on the left to add them to this ticket.
                    </div>
                </div>

                <!-- Totals & Action Tray -->
                <div class="p-4 border-t border-slate-200 bg-slate-50 space-y-3">
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal</span>
                            <span class="font-semibold">{{ formatUgx(combinedSubtotal) }}</span>
                        </div>
                        <div v-if="taxRate > 0" class="flex justify-between text-slate-600">
                            <span>Tax ({{ taxRate }}%)</span>
                            <span class="font-semibold">{{ formatUgx(combinedTax) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-extrabold text-slate-900 pt-1 border-t border-slate-200">
                            <span>Total</span>
                            <span>{{ formatUgx(combinedTotal) }}</span>
                        </div>
                    </div>

                    <div v-if="pendingCart.length > 0" class="grid grid-cols-1 gap-2">
                        <button
                            @click="fireOrder(false)"
                            :disabled="isSubmitting"
                            class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] touch-manipulation text-white font-extrabold text-sm shadow-sm disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Sending...' : 'SEND TO KITCHEN (KOT)' }}
                        </button>
                        <button
                            @click="fireOrder(true)"
                            :disabled="isSubmitting"
                            class="w-full py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 active:scale-[0.99] touch-manipulation text-white font-extrabold text-xs shadow-sm disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Adding...' : 'ADD DIRECT / NO KITCHEN (DRINKS & BAR)' }}
                        </button>
                    </div>

                    <div v-if="activeOrder" class="grid grid-cols-3 gap-2">
                        <button
                            @click="showSplitModal = true"
                            class="py-2.5 rounded-xl bg-white border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-100"
                        >
                            Split Bill
                        </button>
                        <button
                            @click="printCustomerBill"
                            class="py-2.5 rounded-xl bg-sky-600 text-white font-bold text-xs hover:bg-sky-700"
                        >
                            Print Bill
                        </button>
                        <button
                            @click="showSettleModal = true"
                            class="py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800"
                        >
                            Settle / Pay
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        <!-- PRINTABLE THERMAL SLIP (KOT or Customer Bill) -->
        <div v-if="printSlipText" class="hidden print:block p-4 font-mono text-xs whitespace-pre-wrap">
            {{ printSlipText }}
        </div>

        <!-- MODIFIER MODAL -->
        <div v-if="modifierItem" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-5 shadow-xl space-y-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-extrabold text-lg text-slate-900">{{ modifierItem.name }}</h3>
                        <p class="text-sm text-emerald-700 font-bold">{{ formatUgx(modifierItem.price_ugx) }}</p>
                    </div>
                    <button @click="modifierItem = null" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>

                <div v-for="group in modifierItem.modifier_groups" :key="group.id" class="space-y-2">
                    <div class="text-xs font-bold uppercase text-slate-500">{{ group.name }}</div>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-for="mod in group.modifiers"
                            :key="mod.id"
                            @click="toggleModifier(mod)"
                            class="p-2.5 rounded-xl border text-xs font-semibold text-left transition"
                            :class="isModifierSelected(mod) ? 'border-emerald-600 bg-emerald-50 text-emerald-900' : 'border-slate-200 hover:bg-slate-50'"
                        >
                            <div>{{ mod.name }}</div>
                            <div v-if="Number(mod.extra_price) > 0" class="text-[11px] text-emerald-700">
                                +{{ formatUgx(mod.extra_price) }}
                            </div>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Seat #</label>
                        <input v-model.number="itemSeatNumber" type="number" min="1" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Kitchen Note</label>
                        <input v-model="itemNotes" type="text" placeholder="e.g. No onions" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                    </div>
                </div>

                <button
                    @click="confirmModifierItem"
                    class="w-full py-3 rounded-xl bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-700"
                >
                    Add to Ticket
                </button>
            </div>
        </div>

        <!-- SETTLE / CHARGE TO ROOM MODAL -->
        <div v-if="showSettleModal && activeOrder" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-5 shadow-xl space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="font-extrabold text-lg">Settle Order #{{ activeOrder.id }}</h3>
                    <button @click="showSettleModal = false" class="text-slate-400 font-bold">✕</button>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl flex justify-between items-center">
                    <span class="text-sm font-semibold text-slate-600">Amount Due</span>
                    <span class="text-xl font-extrabold text-slate-900">{{ formatUgx(activeOrder.total_amount_ugx) }}</span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="method in ['cash', 'mobile_money', 'card']"
                        :key="method"
                        @click="settleMethod = method"
                        class="py-3 rounded-xl border font-bold text-sm capitalize"
                        :class="settleMethod === method ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-700'"
                    >
                        {{ method.replace('_', ' ') }}
                    </button>
                    <button
                        v-if="checkedInRooms.length > 0"
                        @click="settleMethod = 'room_folio'"
                        class="py-3 rounded-xl border font-bold text-sm"
                        :class="settleMethod === 'room_folio' ? 'border-blue-600 bg-blue-600 text-white' : 'border-blue-200 bg-blue-50 text-blue-700'"
                    >
                        Charge to Room
                    </button>
                </div>

                <div v-if="settleMethod === 'room_folio'" class="space-y-2">
                    <label class="block text-xs font-bold text-slate-600">Select Checked-In Room</label>
                    <select v-model="selectedRoomId" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
                        <option value="">-- Choose Room --</option>
                        <option v-for="r in checkedInRooms" :key="r.room_id" :value="r.room_id">
                            Room {{ r.room_number }} — {{ r.guest_name }}
                        </option>
                    </select>
                </div>

                <button
                    @click="submitSettle"
                    :disabled="isSubmitting || (settleMethod === 'room_folio' && !selectedRoomId)"
                    class="w-full py-3.5 rounded-xl bg-emerald-600 text-white font-extrabold text-sm hover:bg-emerald-700 disabled:opacity-50"
                >
                    {{ settleMethod === 'room_folio' ? 'Post Charge to Room Folio' : 'Confirm Payment & Close Table' }}
                </button>
            </div>
        </div>

        <!-- SPLIT BILL MODAL -->
        <div v-if="showSplitModal && activeOrder" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-xl space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="font-extrabold text-lg">Split Bill</h3>
                    <button @click="showSplitModal = false" class="text-slate-400 font-bold">✕</button>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Split Equally Across Guests</label>
                    <input v-model.number="splitGuests" type="number" min="2" max="20" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                </div>
                <div class="bg-slate-50 p-4 rounded-xl text-center">
                    <div class="text-xs text-slate-500">Per Guest ({{ splitGuests }} guests)</div>
                    <div class="text-2xl font-extrabold text-emerald-700 mt-1">
                        {{ formatUgx(Number(activeOrder.total_amount_ugx) / Math.max(1, splitGuests)) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ADD AREA MODAL -->
        <div v-if="showAreaModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <form @submit.prevent="createArea" class="bg-white rounded-2xl max-w-md w-full p-5 shadow-xl space-y-4">
                <h3 class="font-extrabold text-lg">New Dining Area</h3>
                <input v-model="newArea.name" type="text" required placeholder="e.g. Main Dining, Terrace, Bar" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm" />
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Floor Width (m)</label>
                        <input v-model.number="newArea.width_m" type="number" step="0.5" min="4" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Floor Length (m)</label>
                        <input v-model.number="newArea.length_m" type="number" step="0.5" min="4" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                    </div>
                </div>

                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                    <input type="checkbox" v-model="newArea.is_main_area" class="w-4 h-4 rounded text-emerald-600" />
                    <div>
                        <div class="text-sm font-bold text-slate-900">Is this the Main Area?</div>
                        <div class="text-xs text-slate-500">Automatically places a Cashier / Service Counter on this floor.</div>
                    </div>
                </label>

                <div v-if="newArea.is_main_area" class="grid grid-cols-2 gap-2 p-3 rounded-xl bg-indigo-50/60 border border-indigo-200">
                    <div>
                        <label class="block text-xs font-bold text-indigo-900 mb-1">Counter Width (m)</label>
                        <input v-model.number="newArea.counter_width_m" type="number" step="0.2" min="0.5" required class="w-full px-3 py-2 rounded-xl border border-indigo-200 bg-white text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-indigo-900 mb-1">Counter Depth (m)</label>
                        <input v-model.number="newArea.counter_depth_m" type="number" step="0.2" min="0.5" required class="w-full px-3 py-2 rounded-xl border border-indigo-200 bg-white text-sm" />
                    </div>
                    <div class="col-span-2 text-xs font-semibold text-indigo-800">
                        Counter Footprint: <strong>{{ (newArea.counter_width_m * newArea.counter_depth_m).toFixed(1) }} m²</strong>
                        • Remaining for Dining: <strong>{{ Math.max(0, (newArea.width_m * newArea.length_m) - (newArea.counter_width_m * newArea.counter_depth_m)).toFixed(1) }} m²</strong>
                    </div>
                </div>

                <div class="text-xs font-semibold text-slate-500">
                    Total Floor Space: <strong>{{ (newArea.width_m * newArea.length_m).toFixed(1) }} m²</strong>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showAreaModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold">Save Area</button>
                </div>
            </form>
        </div>

        <!-- ADD TABLE MODAL -->
        <div v-if="showTableModal" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <form @submit.prevent="createTable" class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-xl space-y-4">
                <h3 class="font-extrabold text-lg">Add Table</h3>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Area</label>
                    <select v-model="newTable.area_id" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Table Number / Name</label>
                    <input v-model="newTable.table_number" type="text" required placeholder="e.g. T-01" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Table Type / Style</label>
                    <select v-model="newTable.shape" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold">
                        <option value="square">Square / Rectangular Table</option>
                        <option value="rounded">Rounded-Corner Table</option>
                        <option value="round">Round Table</option>
                        <option value="high_table">High Table (Always Decorated)</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                    <input type="checkbox" :checked="newTable.shape === 'high_table' || newTable.has_floral" :disabled="newTable.shape === 'high_table'" @change="newTable.has_floral = $event.target.checked" />
                    <span>Decorate with Floral Centerpiece</span>
                </label>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Seating Capacity</label>
                    <input v-model.number="newTable.seating_capacity" type="number" min="1" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showTableModal = false" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold">Save Table</button>
                </div>
            </form>
        </div>
    </HospitalityLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import HospitalityLayout from '../../Layouts/HospitalityLayout.vue';
import FloorPlan3D from '../../Components/FloorPlan3D.vue';
import { appUrl } from '../../utils/url';

const props = defineProps({
    areas: Array,
    categories: Array,
    menuItems: Array,
    checkedInRooms: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const taxRate = computed(() => Number(page.props.auth?.tenant?.tax_rate || 0));
const isAdminOrManager = computed(() => ['admin', 'manager', 'superadmin'].includes(user.value?.role));

const selectedAreaId = ref(props.areas?.[0]?.id || null);
const currentArea = computed(() => props.areas.find(a => a.id === selectedAreaId.value) || props.areas?.[0] || null);
const viewMode = ref('3d');

const activeTable = ref(null);
const activeOrder = ref(null);
const pendingCart = ref([]);
const selectedCategoryId = ref(null);
const searchQuery = ref('');
const isSubmitting = ref(false);
const printSlipText = ref('');

// Modals
const modifierItem = ref(null);
const selectedModifiers = ref([]);
const itemSeatNumber = ref(1);
const itemNotes = ref('');

const showSettleModal = ref(false);
const settleMethod = ref('cash');
const selectedRoomId = ref('');

const showSplitModal = ref(false);
const splitGuests = ref(2);

const showAreaModal = ref(false);
const newArea = ref({
    name: '',
    width_m: 15,
    length_m: 12.33,
    is_main_area: true,
    counter_width_m: 4.0,
    counter_depth_m: 1.2,
});
const showTableModal = ref(false);
const newTable = ref({ area_id: props.areas?.[0]?.id || '', table_number: '', seating_capacity: 4, shape: 'square', has_floral: false });

const formatUgx = (val) => 'UGX ' + Math.round(Number(val || 0)).toLocaleString();

const filteredMenuItems = computed(() => {
    return props.menuItems.filter(item => {
        const catOk = !selectedCategoryId.value || item.category_id === selectedCategoryId.value;
        const qOk = !searchQuery.value || item.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        return catOk && qOk;
    });
});

const tableDisplayStatus = (table) => {
    if (table.current_order?.status === 'bill_printed') return 'Bill Printed';
    return table.status;
};

const tableCardClass = (table) => {
    if (table.current_order?.status === 'bill_printed') {
        return 'bg-sky-50 border-sky-400 text-sky-900';
    }
    switch (table.status) {
        case 'occupied': return 'bg-amber-50 border-amber-400 text-amber-900';
        case 'cleaning': return 'bg-yellow-50 border-yellow-400 text-yellow-900';
        case 'reserved': return 'bg-purple-50 border-purple-400 text-purple-900';
        default: return 'bg-emerald-50 border-emerald-400 text-emerald-900';
    }
};

const openTable = (table) => {
    activeTable.value = table;
    activeOrder.value = table.current_order || null;
    pendingCart.value = [];
};

const handleMenuItemClick = (item) => {
    if (item.modifier_groups && item.modifier_groups.length > 0) {
        modifierItem.value = item;
        selectedModifiers.value = [];
        itemSeatNumber.value = 1;
        itemNotes.value = '';
    } else {
        addPendingItem(item, [], 1, '');
    }
};

const isModifierSelected = (mod) => selectedModifiers.value.some(m => m.id === mod.id);

const toggleModifier = (mod) => {
    const idx = selectedModifiers.value.findIndex(m => m.id === mod.id);
    if (idx >= 0) selectedModifiers.value.splice(idx, 1);
    else selectedModifiers.value.push(mod);
};

const confirmModifierItem = () => {
    addPendingItem(modifierItem.value, [...selectedModifiers.value], itemSeatNumber.value, itemNotes.value);
    modifierItem.value = null;
};

const addPendingItem = (item, modifiers, seatNumber, notes) => {
    const modTotal = modifiers.reduce((s, m) => s + Number(m.extra_price || 0), 0);
    pendingCart.value.push({
        menu_item_id: item.id,
        name: item.name,
        unit_price: Number(item.price_ugx),
        modifiers_total: modTotal,
        modifiers: modifiers.map(m => ({ id: m.id, name: m.name, extra_price: Number(m.extra_price || 0) })),
        quantity: 1,
        seat_number: seatNumber || 1,
        special_notes: notes || '',
    });
};

const decrementPending = (idx) => {
    if (pendingCart.value[idx].quantity > 1) pendingCart.value[idx].quantity--;
    else pendingCart.value.splice(idx, 1);
};

const lineTotal = (line) => (line.unit_price + line.modifiers_total) * line.quantity;

const pendingSubtotal = computed(() => pendingCart.value.reduce((s, l) => s + lineTotal(l), 0));
const combinedSubtotal = computed(() => Number(activeOrder.value?.subtotal_ugx || 0) + pendingSubtotal.value);
const combinedTax = computed(() => Math.round(combinedSubtotal.value * (taxRate.value / 100)));
const combinedTotal = computed(() => combinedSubtotal.value + combinedTax.value);

const fireOrder = async (directOnly = false) => {
    isSubmitting.value = true;
    try {
        const { data } = await axios.post('/restaurant/orders/fire', {
            table_id: activeTable.value.id,
            order_id: activeOrder.value?.id || null,
            direct_only: directOnly,
            items: pendingCart.value,
        });
        activeOrder.value = data.order;
        pendingCart.value = [];

        if (!directOnly && data.print_results?.length && !data.print_results[0].sent && data.print_results[0].slip) {
            printSlipText.value = data.print_results.map(r => r.slip).join('\n\n');
            setTimeout(() => window.print(), 150);
        }
        router.reload({ only: ['areas'] });
    } finally {
        isSubmitting.value = false;
    }
};

const printCustomerBill = async () => {
    if (!activeOrder.value) return;
    const { data } = await axios.post(`/restaurant/orders/${activeOrder.value.id}/print-bill`);
    activeOrder.value = data.order;

    const t = page.props.auth?.tenant || {};
    const u = page.props.auth?.user || {};
    const order = activeOrder.value;
    const dateStr = new Date().toLocaleString();

    const itemsRows = (order.items || []).map(i => {
        const unit = Number(i.unit_price) + Number(i.modifiers_total || 0);
        const lineTotal = unit * Number(i.quantity);
        return `<tr style="border-bottom:1px solid #eee;">
            <td style="padding:3px 0;"><strong>${i.item_name}</strong></td>
            <td style="text-align:center;padding:3px 0;">${i.quantity}</td>
            <td style="text-align:right;padding:3px 0;">${formatUgx(unit)}</td>
            <td style="text-align:right;padding:3px 0;">${formatUgx(lineTotal)}</td>
        </tr>`;
    }).join('');

    const receiptHtml = `<!DOCTYPE html><html><head><title>Receipt #${order.id}</title><style>
        @page { size: 58mm auto; margin: 0; }
        html, body { width: 58mm; margin: 0; padding: 0; background: #fff; color: #000; font-family: 'Courier New', Courier, monospace, sans-serif; font-size: 8pt; line-height: 1.25; }
        .wrap { width: 54mm; margin: 0 auto; padding: 2mm; }
        h2 { font-size: 10.5pt; font-weight: 800; text-transform: uppercase; margin: 0; text-align: center; }
        p { font-size: 7.5pt; margin: 1px 0; text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; font-size: 7.5pt; margin: 6px 0; }
        th, td { padding: 2px 0; word-break: break-word; }
    </style></head><body><div class="wrap">
        <div style="text-align:center;border:1.5px solid #d97706;color:#b45309;background:#fffbeb;padding:4px;border-radius:4px;font-weight:800;font-size:8pt;margin-bottom:6px;">
            ${order.status === 'paid' ? 'PAID' : 'PENDING'}
        </div>
        <div style="border-bottom:1px dashed #999;padding-bottom:6px;margin-bottom:6px;">
            <h2>${t.name || 'Luxeshop'}</h2>
            ${t.business_name ? `<p>${t.business_name}</p>` : ''}
            <p>${t.location || 'Main Store Location'}</p>
            <p>Tel: ${t.phone || ''}</p>
        </div>
        <div style="font-size:7.5pt;margin-bottom:6px;">
            <div style="display:flex;justify-content:space-between;"><span>Receipt No:</span><strong>#${order.id}</strong></div>
            <div style="display:flex;justify-content:space-between;"><span>Table:</span><strong>T-${activeTable.value.table_number}</strong></div>
            <div style="display:flex;justify-content:space-between;"><span>Date & Time:</span><span>${dateStr}</span></div>
            <div style="display:flex;justify-content:space-between;"><span>Server:</span><span>${u.name || 'Staff'}</span></div>
        </div>
        <table>
            <thead>
                <tr style="border-top:1px solid #000;border-bottom:1px solid #000;">
                    <th style="text-align:left;">Item</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Price</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>${itemsRows}</tbody>
        </table>
        <div style="border-top:1px dashed #999;padding-top:5px;font-size:8pt;">
            <div style="display:flex;justify-content:space-between;"><span>Subtotal:</span><span>${formatUgx(order.subtotal_ugx)}</span></div>
            ${Number(order.tax_ugx) > 0 ? `<div style="display:flex;justify-content:space-between;"><span>Tax:</span><span>${formatUgx(order.tax_ugx)}</span></div>` : ''}
            <div style="display:flex;justify-content:space-between;font-weight:800;font-size:9.5pt;border-top:1px solid #000;padding-top:3px;margin-top:3px;">
                <span>TOTAL DUE:</span><span>${formatUgx(order.total_amount_ugx)}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-weight:600;padding-top:2px;">
                <span>${order.status === 'paid' ? 'Paid' : 'Pending'}:</span><span>${formatUgx(order.total_amount_ugx)}</span>
            </div>
        </div>
        <div style="text-align:center;margin-top:8px;border-top:1px solid #ccc;padding-top:5px;font-size:7pt;color:#555;">
            <p style="font-weight:600;">Thank you for your business!</p>
            <p>Please keep this receipt for records.</p>
        </div>
    </div></body></html>`;

    const oldFrame = document.getElementById('pos-print-frame');
    if (oldFrame) oldFrame.remove();
    const iframe = document.createElement('iframe');
    iframe.id = 'pos-print-frame';
    iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    document.body.appendChild(iframe);
    iframe.contentWindow.document.open();
    iframe.contentWindow.document.write(receiptHtml);
    iframe.contentWindow.document.close();
    iframe.contentWindow.addEventListener('afterprint', () => setTimeout(() => iframe.remove(), 300), { once: true });
    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }, 80);

    router.reload({ only: ['areas'] });
};

const submitSettle = async () => {
    isSubmitting.value = true;
    try {
        if (settleMethod.value === 'room_folio') {
            const { data } = await axios.post('/hotel/charge-to-room', {
                order_id: activeOrder.value.id,
                room_id: selectedRoomId.value,
            });
            alert(data.message);
        } else {
            await axios.post(`/restaurant/orders/${activeOrder.value.id}/settle`, {
                payment_method: settleMethod.value,
                paid_amount_ugx: activeOrder.value.total_amount_ugx,
                release_table: true,
            });
        }
        showSettleModal.value = false;
        activeTable.value = null;
        activeOrder.value = null;
        router.reload({ only: ['areas'] });
    } finally {
        isSubmitting.value = false;
    }
};

const markTableAvailable = async (table) => {
    await axios.patch(`/restaurant/tables/${table.id}/status`, { status: 'available' });
    activeTable.value = null;
    router.reload({ only: ['areas'] });
};

const createArea = () => {
    router.post(appUrl('/restaurant/areas'), newArea.value, {
        onSuccess: () => { showAreaModal.value = false; newArea.value.name = ''; },
    });
};

const createTable = () => {
    router.post(appUrl('/restaurant/tables'), newTable.value, {
        onSuccess: () => { showTableModal.value = false; newTable.value.table_number = ''; },
    });
};
</script>

