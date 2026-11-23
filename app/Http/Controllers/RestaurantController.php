<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Category;
use App\Models\DiningArea;
use App\Models\DiningTable;
use App\Models\DiningZone;
use App\Models\Ingredient;
use App\Models\KitchenTicket;
use App\Models\KitchenTicketItem;
use App\Models\MenuItem;
use App\Models\MenuModifier;
use App\Models\MenuModifierGroup;
use App\Models\MenuRecipe;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\StationPrinter;
use App\Services\EscPosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RestaurantController extends Controller
{
    public function tables()
    {
        $tenant = auth()->user()->tenant;

        $areas = DiningArea::with(['tables.currentOrder.items', 'zones'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = Category::orderBy('name')->get();

        $menuItems = MenuItem::with(['category', 'modifierGroups.modifiers'])
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        $checkedInRooms = [];
        if ($tenant && $tenant->canAccess('charge_to_room')) {
            $checkedInRooms = Booking::with('room')
                ->where('status', 'checked_in')
                ->get()
                ->map(fn ($b) => [
                    'booking_id' => $b->id,
                    'room_id' => $b->room_id,
                    'room_number' => $b->room?->room_number,
                    'guest_name' => $b->guest_name,
                ]);
        }

        return Inertia::render('Restaurant/Tables', [
            'areas' => $areas,
            'categories' => $categories,
            'menuItems' => $menuItems,
            'checkedInRooms' => $checkedInRooms,
        ]);
    }

    public function storeArea(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'width_m' => 'nullable|numeric|min:3|max:100',
            'length_m' => 'nullable|numeric|min:3|max:100',
            'is_main_area' => 'nullable|boolean',
            'counter_width_m' => 'nullable|numeric|min:0.5|max:40',
            'counter_depth_m' => 'nullable|numeric|min:0.5|max:40',
        ]);

        $widthM = (float) ($request->width_m ?? 15.0);
        $lengthM = (float) ($request->length_m ?? 12.33);
        $isMain = $request->boolean('is_main_area');

        $area = DiningArea::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
            'width_m' => $widthM,
            'length_m' => $lengthM,
            'sort_order' => $isMain ? 0 : 10,
        ]);

        if ($isMain) {
            $cw = (float) ($request->counter_width_m ?? 4.0);
            $cd = (float) ($request->counter_depth_m ?? 1.2);
            DiningZone::create([
                'tenant_id' => auth()->user()->tenant_id,
                'area_id' => $area->id,
                'name' => 'Main Counter',
                'zone_type' => 'counter',
                'pos_x' => round(min($widthM / 2, max(1.5, $cw / 2 + 0.5)), 2),
                'pos_z' => round(max(1.0, $cd / 2 + 0.5), 2),
                'width_m' => $cw,
                'depth_m' => $cd,
                'color' => '#1e293b',
            ]);
        }

        return back()->with('success', 'Dining area created.');
    }

    public function updateArea(Request $request, DiningArea $area)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:100',
            'width_m' => 'required|numeric|min:3|max:100',
            'length_m' => 'required|numeric|min:3|max:100',
        ]);

        $area->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json(['area' => $area->fresh(['tables', 'zones'])]);
    }

    public function storeTable(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:dining_areas,id',
            'table_number' => 'required|string|max:20',
            'seating_capacity' => 'required|integer|min:1|max:30',
            'shape' => 'nullable|in:square,rounded,round,high_table,rect',
            'has_floral' => 'nullable|boolean',
            'width_m' => 'nullable|numeric|min:0.5|max:8',
            'depth_m' => 'nullable|numeric|min:0.5|max:8',
        ]);

        $area = DiningArea::withCount('tables')->findOrFail($request->area_id);
        $idx = $area->tables_count;
        $cols = max(1, (int) floor(($area->width_m - 2) / 2.5));
        $posX = 2.0 + ($idx % $cols) * 2.5;
        $posZ = 2.5 + floor($idx / $cols) * 2.5;
        $shape = $request->shape ?? 'square';

        DiningTable::create([
            'tenant_id' => auth()->user()->tenant_id,
            'area_id' => $request->area_id,
            'table_number' => $request->table_number,
            'seating_capacity' => $request->seating_capacity,
            'shape' => $shape,
            'has_floral' => $shape === 'high_table' ? true : $request->boolean('has_floral'),
            'pos_x' => min($posX, max(1.5, $area->width_m - 1.5)),
            'pos_z' => min($posZ, max(1.5, $area->length_m - 1.5)),
            'width_m' => $request->width_m ?? 1.2,
            'depth_m' => $request->depth_m ?? 1.2,
            'status' => 'available',
        ]);

        return back()->with('success', 'Table added.');
    }

    public function storeZone(Request $request)
    {
        $data = $request->validate([
            'area_id' => 'required|exists:dining_areas,id',
            'name' => 'required|string|max:100',
            'zone_type' => 'required|in:counter,bar,kitchen,entrance,floral,other',
            'width_m' => 'required|numeric|min:0.4|max:40',
            'depth_m' => 'required|numeric|min:0.4|max:40',
            'pos_x' => 'nullable|numeric|min:0|max:100',
            'pos_z' => 'nullable|numeric|min:0|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $zone = DiningZone::create([
            'tenant_id' => auth()->user()->tenant_id,
            'area_id' => $data['area_id'],
            'name' => $data['name'],
            'zone_type' => $data['zone_type'],
            'pos_x' => $data['pos_x'] ?? 2.0,
            'pos_z' => $data['pos_z'] ?? 1.5,
            'width_m' => $data['width_m'],
            'depth_m' => $data['depth_m'],
            'color' => $data['color'] ?? '#1e293b',
        ]);

        return response()->json(['zone' => $zone]);
    }

    public function destroyZone(DiningZone $zone)
    {
        $zone->delete();

        return response()->json(['deleted' => true]);
    }

    public function saveLayout(Request $request, DiningArea $area)
    {
        $data = $request->validate([
            'width_m' => 'nullable|numeric|min:3|max:100',
            'length_m' => 'nullable|numeric|min:3|max:100',
            'tables' => 'nullable|array',
            'tables.*.id' => 'required|exists:dining_tables,id',
            'tables.*.pos_x' => 'required|numeric|min:0|max:100',
            'tables.*.pos_z' => 'required|numeric|min:0|max:100',
            'tables.*.width_m' => 'nullable|numeric|min:0.5|max:10',
            'tables.*.depth_m' => 'nullable|numeric|min:0.5|max:10',
            'tables.*.rotation_deg' => 'nullable|integer|min:0|max:360',
            'tables.*.shape' => 'nullable|in:square,rounded,round,high_table,rect',
            'tables.*.has_floral' => 'nullable|boolean',
            'zones' => 'nullable|array',
            'zones.*.id' => 'required|exists:dining_zones,id',
            'zones.*.pos_x' => 'required|numeric|min:0|max:100',
            'zones.*.pos_z' => 'required|numeric|min:0|max:100',
            'zones.*.width_m' => 'nullable|numeric|min:0.5|max:40',
            'zones.*.depth_m' => 'nullable|numeric|min:0.5|max:40',
        ]);

        DB::transaction(function () use ($data, $area) {
            if (!empty($data['width_m']) && !empty($data['length_m'])) {
                $area->update([
                    'width_m' => $data['width_m'],
                    'length_m' => $data['length_m'],
                ]);
            }

            foreach ($data['tables'] ?? [] as $t) {
                $shape = $t['shape'] ?? 'square';
                DiningTable::where('id', $t['id'])
                    ->where('area_id', $area->id)
                    ->update([
                        'pos_x' => $t['pos_x'],
                        'pos_z' => $t['pos_z'],
                        'width_m' => $t['width_m'] ?? 1.2,
                        'depth_m' => $t['depth_m'] ?? 1.2,
                        'rotation_deg' => $t['rotation_deg'] ?? 0,
                        'shape' => $shape,
                        'has_floral' => $shape === 'high_table' ? true : (bool) ($t['has_floral'] ?? false),
                    ]);
            }

            foreach ($data['zones'] ?? [] as $z) {
                DiningZone::where('id', $z['id'])
                    ->where('area_id', $area->id)
                    ->update([
                        'pos_x' => $z['pos_x'],
                        'pos_z' => $z['pos_z'],
                        'width_m' => $z['width_m'] ?? 3.5,
                        'depth_m' => $z['depth_m'] ?? 1.2,
                    ]);
            }
        });

        return response()->json(['saved' => true, 'area' => $area->fresh(['tables.currentOrder.items', 'zones'])]);
    }

    public function updateTableStatus(Request $request, DiningTable $table)
    {
        $request->validate([
            'status' => 'required|in:available,occupied,reserved,cleaning',
        ]);

        $data = ['status' => $request->status];
        if ($request->status === 'available') {
            $data['current_order_id'] = null;
        }
        $table->update($data);

        return response()->json(['table' => $table->fresh()]);
    }

    public function fireOrder(Request $request, EscPosService $escPos)
    {
        $request->validate([
            'table_id' => 'nullable|exists:dining_tables,id',
            'order_id' => 'nullable|exists:restaurant_orders,id',
            'guest_count' => 'nullable|integer|min:1|max:50',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.seat_number' => 'nullable|integer|min:1|max:30',
            'items.*.modifiers' => 'nullable|array',
            'items.*.special_notes' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $tenant = $user->tenant;
        $taxRate = (float) ($tenant->tax_rate ?? 0);
        $multiStation = $tenant->canAccess('multi_station_kot');
        $useBom = $tenant->canAccess('recipe_bom');

        $result = DB::transaction(function () use ($request, $user, $tenant, $taxRate, $multiStation, $useBom, $escPos) {
            $table = $request->table_id ? DiningTable::findOrFail($request->table_id) : null;

            $order = null;
            if ($request->order_id) {
                $order = RestaurantOrder::findOrFail($request->order_id);
            } elseif ($table && $table->current_order_id) {
                $order = RestaurantOrder::find($table->current_order_id);
            }

            if (!$order) {
                $order = RestaurantOrder::create([
                    'tenant_id' => $tenant->id,
                    'table_id' => $table?->id,
                    'server_id' => $user->id,
                    'guest_count' => $request->guest_count ?? 1,
                    'status' => 'open',
                ]);
            }

            $itemsByStation = [];

            foreach ($request->items as $line) {
                $menuItem = MenuItem::with('recipes.ingredient')->findOrFail($line['menu_item_id']);
                $modifiers = $line['modifiers'] ?? [];
                $modifiersTotal = collect($modifiers)->sum(fn ($m) => (float) ($m['extra_price'] ?? 0));

                $orderItem = RestaurantOrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'unit_price' => $menuItem->price_ugx,
                    'modifiers_total' => $modifiersTotal,
                    'quantity' => $line['quantity'],
                    'seat_number' => $line['seat_number'] ?? 1,
                    'modifiers' => $modifiers,
                    'special_notes' => $line['special_notes'] ?? null,
                    'status' => 'fired',
                ]);

                $station = $multiStation ? $menuItem->station : 'kitchen';
                $itemsByStation[$station][] = [
                    'order_item' => $orderItem,
                    'menu_item' => $menuItem,
                    'modifiers_summary' => collect($modifiers)->pluck('name')->implode(', '),
                ];

                // Deduct BOM recipe ingredients if enabled on plan
                if ($useBom && $menuItem->recipes->isNotEmpty()) {
                    foreach ($menuItem->recipes as $recipe) {
                        if ($recipe->ingredient) {
                            $deduct = (float) $recipe->quantity_required * (int) $line['quantity'];
                            $recipe->ingredient->decrement('stock_qty', $deduct);
                        }
                    }
                }
            }

            $order->load('items');
            $order->recalculateTotals($taxRate);

            if ($table) {
                $table->update([
                    'status' => 'occupied',
                    'current_order_id' => $order->id,
                ]);
            }

            $tickets = [];
            $printResults = [];
            foreach ($itemsByStation as $station => $entries) {
                $ticket = KitchenTicket::create([
                    'tenant_id' => $tenant->id,
                    'order_id' => $order->id,
                    'table_id' => $table?->id,
                    'server_id' => $user->id,
                    'station' => $station,
                    'status' => 'pending',
                ]);

                foreach ($entries as $entry) {
                    KitchenTicketItem::create([
                        'ticket_id' => $ticket->id,
                        'order_item_id' => $entry['order_item']->id,
                        'menu_item_id' => $entry['menu_item']->id,
                        'item_name' => $entry['order_item']->item_name,
                        'quantity' => $entry['order_item']->quantity,
                        'modifiers_summary' => $entry['modifiers_summary'] ?: null,
                        'special_notes' => $entry['order_item']->special_notes,
                    ]);
                }

                $printResults[] = $escPos->dispatchKitchenTicket($ticket);
                $tickets[] = $ticket->load('items');
            }

            return [
                'order' => $order->fresh(['items', 'table']),
                'tickets' => $tickets,
                'print_results' => $printResults,
            ];
        });

        return response()->json($result);
    }

    public function printBill(RestaurantOrder $order)
    {
        if ($order->status === 'open') {
            $order->update(['status' => 'bill_printed']);
        }

        return response()->json([
            'order' => $order->fresh(['items', 'table', 'server']),
        ]);
    }

    public function settleOrder(Request $request, RestaurantOrder $order)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,mobile_money,card,split',
            'paid_amount_ugx' => 'required|numeric|min:0',
            'release_table' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $order) {
            $newPaid = (float) $order->paid_amount_ugx + (float) $request->paid_amount_ugx;
            $isFullyPaid = $newPaid >= (float) $order->total_amount_ugx;

            $order->update([
                'paid_amount_ugx' => $newPaid,
                'payment_method' => $request->payment_method,
                'payment_status' => $isFullyPaid ? 'paid' : 'partial',
                'status' => $isFullyPaid ? 'paid' : $order->status,
                'closed_at' => $isFullyPaid ? now() : null,
            ]);

            if ($isFullyPaid && $order->table) {
                $order->table->update([
                    'status' => $request->boolean('release_table', true) ? 'available' : 'cleaning',
                    'current_order_id' => null,
                ]);
            }
        });

        return response()->json(['order' => $order->fresh(['items', 'table'])]);
    }

    public function kds()
    {
        $tickets = KitchenTicket::with(['table', 'server', 'items'])
            ->whereIn('status', ['pending', 'in_prep', 'ready'])
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Restaurant/Kds', [
            'initialTickets' => $tickets,
        ]);
    }

    public function kdsTickets()
    {
        $tickets = KitchenTicket::with(['table', 'server', 'items'])
            ->whereIn('status', ['pending', 'in_prep', 'ready'])
            ->orderBy('created_at')
            ->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function updateTicketStatus(Request $request, KitchenTicket $ticket)
    {
        $request->validate([
            'status' => 'required|in:pending,in_prep,ready,served',
        ]);

        $ticket->update(['status' => $request->status]);

        $itemIds = $ticket->items()->pluck('order_item_id')->filter();
        if ($itemIds->isNotEmpty()) {
            $mapped = match ($request->status) {
                'in_prep' => 'in_prep',
                'ready' => 'ready',
                'served' => 'served',
                default => 'fired',
            };
            RestaurantOrderItem::whereIn('id', $itemIds)->update(['status' => $mapped]);
        }

        return response()->json(['ticket' => $ticket->fresh(['table', 'server', 'items'])]);
    }

    public function menuSetup()
    {
        $tenant = auth()->user()->tenant;

        return Inertia::render('Restaurant/MenuSetup', [
            'categories' => Category::orderBy('name')->get(),
            'menuItems' => MenuItem::with(['category', 'modifierGroups.modifiers', 'recipes.ingredient'])->orderBy('name')->get(),
            'modifierGroups' => MenuModifierGroup::with('modifiers')->orderBy('name')->get(),
            'ingredients' => Ingredient::orderBy('name')->get(),
            'stationPrinters' => StationPrinter::all(),
            'canRecipeBom' => $tenant?->canAccess('recipe_bom') ?? false,
            'canMultiStation' => $tenant?->canAccess('multi_station_kot') ?? false,
        ]);
    }

    public function storeMenuItem(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|exists:categories,id',
            'price_ugx' => 'required|numeric|min:0',
            'station' => 'required|in:kitchen,bar,pastry',
            'color_tag' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'modifier_group_ids' => 'nullable|array',
        ]);

        $item = MenuItem::create([
            'tenant_id' => auth()->user()->tenant_id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'price_ugx' => $data['price_ugx'],
            'station' => $data['station'],
            'color_tag' => $data['color_tag'] ?? null,
            'description' => $data['description'] ?? null,
            'is_available' => true,
        ]);

        if (!empty($data['modifier_group_ids'])) {
            $item->modifierGroups()->sync($data['modifier_group_ids']);
        }

        return back()->with('success', 'Menu item created.');
    }

    public function storeModifierGroup(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'min_selections' => 'required|integer|min:0',
            'max_selections' => 'required|integer|min:1',
            'options' => 'nullable|array',
            'options.*.name' => 'required_with:options|string|max:100',
            'options.*.extra_price' => 'nullable|numeric|min:0',
        ]);

        $group = MenuModifierGroup::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $data['name'],
            'min_selections' => $data['min_selections'],
            'max_selections' => $data['max_selections'],
        ]);

        foreach ($data['options'] ?? [] as $opt) {
            MenuModifier::create([
                'tenant_id' => auth()->user()->tenant_id,
                'group_id' => $group->id,
                'name' => $opt['name'],
                'extra_price' => $opt['extra_price'] ?? 0,
            ]);
        }

        return back()->with('success', 'Modifier group saved.');
    }

    public function storeIngredient(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'unit' => 'required|string|max:30',
            'stock_qty' => 'required|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
        ]);

        Ingredient::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $data['name'],
            'unit' => $data['unit'],
            'stock_qty' => $data['stock_qty'],
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 0,
            'cost_per_unit' => $data['cost_per_unit'] ?? 0,
        ]);

        return back()->with('success', 'Ingredient added.');
    }

    public function storeRecipe(Request $request)
    {
        $data = $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity_required' => 'required|numeric|min:0.001',
        ]);

        MenuRecipe::updateOrCreate(
            [
                'tenant_id' => auth()->user()->tenant_id,
                'menu_item_id' => $data['menu_item_id'],
                'ingredient_id' => $data['ingredient_id'],
            ],
            ['quantity_required' => $data['quantity_required']]
        );

        return back()->with('success', 'Recipe BOM ingredient linked.');
    }

    public function saveStationPrinter(Request $request)
    {
        $data = $request->validate([
            'station' => 'required|in:kitchen,bar,pastry',
            'printer_name' => 'required|string|max:100',
            'printer_ip' => 'nullable|ip',
            'printer_port' => 'nullable|integer|min:1|max:65535',
            'is_active' => 'boolean',
        ]);

        StationPrinter::updateOrCreate(
            [
                'tenant_id' => auth()->user()->tenant_id,
                'station' => $data['station'],
            ],
            [
                'printer_name' => $data['printer_name'],
                'printer_ip' => $data['printer_ip'] ?? null,
                'printer_port' => $data['printer_port'] ?? 9100,
                'is_active' => $data['is_active'] ?? true,
            ]
        );

        return back()->with('success', 'Station printer settings saved.');
    }
}

