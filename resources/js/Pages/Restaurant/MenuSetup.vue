<template>
    <HospitalityLayout>
        <div class="p-4 lg:p-6 max-w-7xl mx-auto space-y-6">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">Menu, Modifiers, Recipe BOM & Printers</h1>
                <p class="text-sm text-slate-500">Configure dishes, kitchen stations, ingredient recipes, and ESC/POS printers.</p>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 border-b border-slate-200 pb-2 overflow-x-auto">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    @click="activeTab = t.key"
                    class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap"
                    :class="activeTab === t.key ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200'"
                >
                    {{ t.label }}
                </button>
            </div>

            <!-- TAB 1: MENU ITEMS -->
            <div v-if="activeTab === 'menu'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <form @submit.prevent="submitMenuItem" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3 h-fit">
                    <h2 class="font-extrabold text-base">Add Menu Item</h2>
                    <input v-model="itemForm.name" type="text" required placeholder="Dish / Drink Name" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                    <select v-model="itemForm.category_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                        <option value="">-- Category --</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <input v-model.number="itemForm.price_ugx" type="number" min="0" required placeholder="Price (UGX)" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                    <select v-model="itemForm.station" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                        <option value="kitchen">Kitchen Station</option>
                        <option value="bar" :disabled="!canMultiStation">Bar Station {{ !canMultiStation ? '(Pro)' : '' }}</option>
                        <option value="pastry" :disabled="!canMultiStation">Pastry Station {{ !canMultiStation ? '(Pro)' : '' }}</option>
                    </select>
                    <div v-if="modifierGroups.length" class="space-y-1">
                        <div class="text-xs font-bold text-slate-600">Modifier Groups</div>
                        <label v-for="g in modifierGroups" :key="g.id" class="flex items-center gap-2 text-xs">
                            <input type="checkbox" :value="g.id" v-model="itemForm.modifier_group_ids" />
                            <span>{{ g.name }}</span>
                        </label>
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-sm hover:bg-emerald-700">Save Dish</button>
                </form>

                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3">Station</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Modifiers</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in menuItems" :key="m.id">
                                <td class="px-4 py-3 font-bold">{{ m.name }}</td>
                                <td class="px-4 py-3 uppercase text-xs font-semibold">{{ m.station }}</td>
                                <td class="px-4 py-3 font-semibold text-emerald-700">UGX {{ Number(m.price_ugx).toLocaleString() }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ m.modifier_groups?.map(g => g.name).join(', ') || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: MODIFIERS -->
            <div v-if="activeTab === 'modifiers'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <form @submit.prevent="submitModifierGroup" class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3 h-fit">
                    <h2 class="font-extrabold text-base">New Modifier Group</h2>
                    <input v-model="modForm.name" type="text" required placeholder="e.g. Choice of Side" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm" />
                    <div class="space-y-2">
                        <div class="text-xs font-bold text-slate-600">Options</div>
                        <div v-for="(opt, i) in modForm.options" :key="i" class="flex gap-2">
                            <input v-model="opt.name" type="text" required placeholder="Option name" class="flex-1 px-2.5 py-1.5 rounded-lg border text-xs" />
                            <input v-model.number="opt.extra_price" type="number" min="0" placeholder="+UGX" class="w-24 px-2.5 py-1.5 rounded-lg border text-xs" />
                        </div>
                        <button type="button" @click="modForm.options.push({ name: '', extra_price: 0 })" class="text-xs font-bold text-emerald-700">+ Add Option</button>
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-sm">Save Modifier Group</button>
                </form>

                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-for="g in modifierGroups" :key="g.id" class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                        <h3 class="font-extrabold text-sm mb-2">{{ g.name }}</h3>
                        <ul class="space-y-1 text-xs text-slate-600">
                            <li v-for="o in g.modifiers" :key="o.id" class="flex justify-between">
                                <span>{{ o.name }}</span>
                                <span class="font-semibold">+UGX {{ Number(o.extra_price).toLocaleString() }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- TAB 3: RECIPE BOM -->
            <div v-if="activeTab === 'bom'">
                <div v-if="!canRecipeBom" class="bg-amber-50 border border-amber-200 p-6 rounded-2xl text-amber-900">
                    <div class="font-extrabold text-base">Pro Feature — Upgrade Required</div>
                    <p class="text-sm mt-1">Recipe & Ingredient BOM Inventory is included in Hotel Starter (UGX 170,000/mo) and Hotel & Resort Pro (UGX 250,000/mo).</p>
                </div>
                <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <form @submit.prevent="submitIngredient" class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                        <h2 class="font-extrabold text-base">Add Raw Ingredient</h2>
                        <input v-model="ingForm.name" type="text" required placeholder="e.g. Ground Beef" class="w-full px-3 py-2 rounded-xl border text-sm" />
                        <div class="grid grid-cols-2 gap-2">
                            <input v-model="ingForm.unit" type="text" required placeholder="Unit (g, ml, pcs)" class="px-3 py-2 rounded-xl border text-sm" />
                            <input v-model.number="ingForm.stock_qty" type="number" step="0.01" required placeholder="Initial Stock" class="px-3 py-2 rounded-xl border text-sm" />
                        </div>
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-sm">Save Ingredient</button>
                    </form>

                    <form @submit.prevent="submitRecipe" class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3">
                        <h2 class="font-extrabold text-base">Link Ingredient to Dish (BOM)</h2>
                        <select v-model="recipeForm.menu_item_id" required class="w-full px-3 py-2 rounded-xl border text-sm">
                            <option value="">-- Select Dish --</option>
                            <option v-for="m in menuItems" :key="m.id" :value="m.id">{{ m.name }}</option>
                        </select>
                        <select v-model="recipeForm.ingredient_id" required class="w-full px-3 py-2 rounded-xl border text-sm">
                            <option value="">-- Select Ingredient --</option>
                            <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">{{ ing.name }} ({{ ing.stock_qty }} {{ ing.unit }})</option>
                        </select>
                        <input v-model.number="recipeForm.quantity_required" type="number" step="0.001" required placeholder="Qty deducted per dish" class="w-full px-3 py-2 rounded-xl border text-sm" />
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 text-white font-bold text-sm">Link to Recipe</button>
                    </form>
                </div>
            </div>

            <!-- TAB 4: STATION PRINTERS -->
            <div v-if="activeTab === 'printers'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <form
                    v-for="st in ['kitchen', 'bar', 'pastry']"
                    :key="st"
                    @submit.prevent="savePrinter(st)"
                    class="bg-white p-5 rounded-2xl border border-slate-200 space-y-3"
                >
                    <h3 class="font-extrabold text-base uppercase">{{ st }} Printer</h3>
                    <input v-model="printerForms[st].printer_name" type="text" required placeholder="Printer Name" class="w-full px-3 py-2 rounded-xl border text-sm" />
                    <input v-model="printerForms[st].printer_ip" type="text" placeholder="LAN IP (e.g. 192.168.1.50)" class="w-full px-3 py-2 rounded-xl border text-sm" />
                    <input v-model.number="printerForms[st].printer_port" type="number" placeholder="Port (9100)" class="w-full px-3 py-2 rounded-xl border text-sm" />
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs">Save {{ st }} Printer</button>
                </form>
            </div>
        </div>
    </HospitalityLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import HospitalityLayout from '../../Layouts/HospitalityLayout.vue';
import { appUrl } from '../../utils/url';

const props = defineProps({
    categories: Array,
    menuItems: Array,
    modifierGroups: Array,
    ingredients: Array,
    stationPrinters: Array,
    canRecipeBom: Boolean,
    canMultiStation: Boolean,
});

const activeTab = ref('menu');
const tabs = [
    { key: 'menu', label: 'Menu Items' },
    { key: 'modifiers', label: 'Modifiers & Add-ons' },
    { key: 'bom', label: 'Ingredients & Recipe BOM' },
    { key: 'printers', label: 'KOT Network Printers' },
];

const itemForm = reactive({ name: '', category_id: '', price_ugx: '', station: 'kitchen', modifier_group_ids: [] });
const modForm = reactive({ name: '', min_selections: 0, max_selections: 1, options: [{ name: '', extra_price: 0 }] });
const ingForm = reactive({ name: '', unit: 'g', stock_qty: 0 });
const recipeForm = reactive({ menu_item_id: '', ingredient_id: '', quantity_required: '' });

const printerForms = reactive({
    kitchen: { printer_name: 'Kitchen Thermal', printer_ip: '', printer_port: 9100, is_active: true },
    bar: { printer_name: 'Bar Thermal', printer_ip: '', printer_port: 9100, is_active: true },
    pastry: { printer_name: 'Pastry Thermal', printer_ip: '', printer_port: 9100, is_active: true },
});

(props.stationPrinters || []).forEach(p => {
    if (printerForms[p.station]) {
        printerForms[p.station] = { ...p };
    }
});

const submitMenuItem = () => router.post(appUrl('/restaurant/menu-items'), itemForm, { onSuccess: () => { itemForm.name = ''; itemForm.price_ugx = ''; } });
const submitModifierGroup = () => router.post(appUrl('/restaurant/modifier-groups'), modForm, { onSuccess: () => { modForm.name = ''; modForm.options = [{ name: '', extra_price: 0 }]; } });
const submitIngredient = () => router.post(appUrl('/restaurant/ingredients'), ingForm, { onSuccess: () => { ingForm.name = ''; ingForm.stock_qty = 0; } });
const submitRecipe = () => router.post(appUrl('/restaurant/recipes'), recipeForm, { onSuccess: () => { recipeForm.quantity_required = ''; } });
const savePrinter = (st) => router.post(appUrl('/restaurant/station-printers'), { station: st, ...printerForms[st] });
</script>

