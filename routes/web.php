<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SaaSController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DisbursementController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\HotelController;
use App\Http\Middleware\CheckSubscription;

// Redirect homepage based on role & business type
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();
    $role = $user->role;
    $tenant = $user->tenant;

    if ($role === 'superadmin') {
        return redirect()->route('saas');
    }

    if ($role === 'kitchen') {
        return redirect()->route('restaurant.kds');
    }

    if ($role === 'receptionist') {
        return redirect()->route('hotel.rooms');
    }

    if ($role === 'accountant') {
        return redirect()->route('reports.index');
    }

    if ($role === 'cashier') {
        if ($tenant && $tenant->isRestaurantOrHotel()) {
            return redirect()->route('restaurant.tables');
        }
        return redirect()->route('cashier.dashboard');
    }

    if (in_array($role, ['admin', 'manager'])) {
        if ($tenant && $tenant->isHotel()) {
            return redirect()->route('hotel.rooms');
        }
        if ($tenant && $tenant->isRestaurantOrHotel()) {
            return redirect()->route('restaurant.tables');
        }
        return redirect()->route('sales');
    }

    return redirect()->route('cashier.dashboard');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Auth Routes
Route::middleware(['auth', CheckSubscription::class])->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/password', [AuthController::class, 'showPasswordChangeForm'])->name('password.change');
    Route::post('/password', [AuthController::class, 'changePassword'])->name('password.update');
    
    Route::post('/attendance/toggle', [AuthController::class, 'toggleAttendance'])->name('attendance.toggle');

    // POS routes (Cashier & Staff — dynamic redirect for Restaurant/Hotel)
    Route::get('/pos', function (\Illuminate\Http\Request $request) {
        $tenant = auth()->user()->tenant;
        if ($tenant && $tenant->isRestaurantOrHotel()) {
            return redirect()->route('restaurant.tables');
        }
        return app(POSController::class)->index($request);
    })->name('pos');
    Route::get('/pos/scan', [POSController::class, 'scanProduct'])->name('pos.scan');
    Route::post('/pos/checkout', [POSController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/stk-status/{transaction}', [POSController::class, 'checkStkStatus'])->name('pos.stk-status');
    Route::post('/pos/stk-cancel/{transaction}', [POSController::class, 'cancelStk'])->name('pos.stk-cancel');
    Route::post('/pos/stk-simulate/{transaction}', [POSController::class, 'simulateStk'])->name('pos.stk-simulate');

    // Cashier Dashboard
    Route::get('/cashier/dashboard', [DashboardController::class, 'cashierDashboard'])->name('cashier.dashboard');

    // Reports & Financial Exports (Admins, Managers & Accountants)
    Route::middleware('can:view-reports')->group(function () {
        Route::get('/reports', [DashboardController::class, 'reports'])->name('reports.index');
        Route::get('/reports/export', [DashboardController::class, 'exportCSV'])->name('reports.export');
        Route::get('/reports/balance-sheet/export', [DashboardController::class, 'exportBalanceSheet'])->name('reports.export-balance-sheet');

        // Purchase Orders (XPOs)
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase_orders.index');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase_orders.store');
        Route::match(['get', 'post'], '/purchase-orders/{po}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase_orders.receive');
        Route::match(['get', 'post'], '/purchase-orders/{po}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase_orders.cancel');
        Route::match(['get', 'post'], '/purchase-orders/{po}/pay-vendor', [PurchaseOrderController::class, 'payVendor'])->name('purchase_orders.pay-vendor');
    });

    // Products & Inventory Management (Admins & Managers)
    Route::middleware('can:manage-inventory')->group(function () {
        Route::get('/sales', [DashboardController::class, 'index'])->name('sales');
        Route::match(['get', 'post'], '/sales/{sale}/settle-debt', [DashboardController::class, 'settleDebt'])->name('sales.settle-debt');
        Route::match(['post', 'delete'], '/sales/{sale}/soft-delete', [DashboardController::class, 'softDelete'])->name('sales.soft-delete');
        Route::match(['post', 'patch'], '/sales/{id}/restore', [DashboardController::class, 'restoreSale'])->name('sales.restore');
        Route::get('/sales/math-challenge', [DashboardController::class, 'generateMathChallenge'])->name('sales.math-challenge');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::match(['get', 'post'], '/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::match(['get', 'post'], '/products/{product}/update-stock', [ProductController::class, 'updateStock'])->name('products.update-stock');
        Route::post('/products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active');
        Route::match(['get', 'post'], '/products/{product}/delete', [ProductController::class, 'destroy'])->name('products.delete');
        
        // Categories Management
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::post('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::match(['get', 'post'], '/categories/{category}/delete', [CategoryController::class, 'destroy'])->name('categories.delete');
    });

    // User Management (Admins only)
    Route::middleware('can:manage-users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::match(['get', 'post'], '/users/{user}/delete', [UserController::class, 'destroy'])->name('users.delete');
    });

    // Quotations (accessible by all roles)
    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
    Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
    Route::match(['get', 'post'], '/quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.status');

    // Daily Expenses
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

    // Branch Switcher (Auth users)
    Route::match(['get', 'post'], '/switch-branch', [BranchController::class, 'switchBranch'])->name('branches.switch');

    // Currency Switcher (Auth users — session-based display currency)
    Route::match(['get', 'post'], '/switch-currency', [SettingController::class, 'switchCurrency'])->name('currency.switch');

    // Settings & Branches (Admins only)
    Route::middleware('can:manage-settings')->group(function () {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::match(['get', 'post'], '/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::match(['get', 'post'], '/branches/{branch}/toggle', [BranchController::class, 'destroy'])->name('branches.toggle');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('/receipt-simulator', [SettingController::class, 'receiptSimulator'])->name('receipt.simulator');
        Route::match(['get', 'post'], '/settings/currency', [SettingController::class, 'addCurrency'])->name('settings.add-currency');
        Route::match(['get', 'post'], '/settings/currency/set-base', [SettingController::class, 'setBaseCurrency'])->name('settings.set-base-currency');
        Route::match(['get', 'post'], '/settings/currency/{currency}/update', [SettingController::class, 'updateCurrency'])->name('settings.update-currency');
        Route::match(['get', 'post'], '/settings/currency/{currency}/delete', [SettingController::class, 'deleteCurrency'])->name('settings.delete-currency');
    });

    // Finances & Mobile Money Withdrawals (Admins & Managers)
    Route::middleware('can:manage-inventory')->group(function () {
        Route::get('/finances/withdrawals', [DisbursementController::class, 'index'])->name('finances.disbursements');
        Route::post('/finances/withdrawals', [DisbursementController::class, 'withdraw'])->name('finances.withdraw');
        Route::get('/finances/withdrawals/{transaction}/status', [DisbursementController::class, 'checkStatus'])->name('finances.check-status');
    });

    // SaaS Superadmin Dashboard
    Route::middleware('can:manage-saas')->group(function () {
        Route::get('/saas', [SaaSController::class, 'index'])->name('saas');
        Route::match(['get', 'post'], '/saas/clients/{tenant}/subscription', [SaaSController::class, 'updateSubscription'])->name('saas.clients.subscription');
        Route::match(['get', 'post'], '/saas/clients/{tenant}/status', [SaaSController::class, 'toggleStatus'])->name('saas.clients.status');
        Route::match(['get', 'post'], '/saas/clients/{tenant}/renew', [SaaSController::class, 'renewSubscription'])->name('saas.clients.renew');
        Route::get('/saas/sales/{sale}/print-deleted-receipt', [SaaSController::class, 'printDeletedReceipt'])->name('saas.sales.print-deleted');
        Route::match(['get', 'post'], '/saas/tickets/{ticket}/resolve', [SaaSController::class, 'resolveTicket'])->name('saas.tickets.resolve');
        Route::match(['get', 'post'], '/saas/impersonate/{tenant}', [SaaSController::class, 'impersonate'])->name('saas.impersonate');
    });

    Route::get('/saas/stop-impersonation', [SaaSController::class, 'stopImpersonation'])->name('saas.stop-impersonation');

    // Support Tickets
    Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');

    // Restaurant Module (Feature-gated + Role-gated)
    Route::prefix('restaurant')->name('restaurant.')->middleware('feature:table_management')->group(function () {
        Route::middleware('role:admin,manager,cashier,receptionist')->group(function () {
            Route::get('/tables', [RestaurantController::class, 'tables'])->name('tables');
            Route::post('/areas', [RestaurantController::class, 'storeArea'])->name('areas.store');
            Route::patch('/areas/{area}', [RestaurantController::class, 'updateArea'])->name('areas.update');
            Route::post('/areas/{area}/layout', [RestaurantController::class, 'saveLayout'])->name('areas.layout');
            Route::post('/zones', [RestaurantController::class, 'storeZone'])->name('zones.store');
            Route::delete('/zones/{zone}', [RestaurantController::class, 'destroyZone'])->name('zones.destroy');
            Route::post('/tables', [RestaurantController::class, 'storeTable'])->name('tables.store');
            Route::patch('/tables/{table}/status', [RestaurantController::class, 'updateTableStatus'])->name('tables.status');
            Route::post('/orders/fire', [RestaurantController::class, 'fireOrder'])->name('orders.fire');
            Route::post('/orders/{order}/print-bill', [RestaurantController::class, 'printBill'])->name('orders.print-bill');
            Route::post('/orders/{order}/settle', [RestaurantController::class, 'settleOrder'])->name('orders.settle');
        });

        Route::middleware('role:admin,manager,kitchen')->group(function () {
            Route::get('/kds', [RestaurantController::class, 'kds'])->name('kds');
            Route::get('/kds/tickets', [RestaurantController::class, 'kdsTickets'])->name('kds.tickets');
            Route::patch('/kds/tickets/{ticket}/status', [RestaurantController::class, 'updateTicketStatus'])->name('kds.status');
        });

        Route::middleware('role:admin,manager')->group(function () {
            Route::get('/menu-setup', [RestaurantController::class, 'menuSetup'])->name('menu-setup');
            Route::post('/menu-items', [RestaurantController::class, 'storeMenuItem'])->name('menu-items.store');
            Route::post('/modifier-groups', [RestaurantController::class, 'storeModifierGroup'])->name('modifier-groups.store');
            Route::post('/station-printers', [RestaurantController::class, 'saveStationPrinter'])->name('station-printers.save');

            Route::middleware('feature:recipe_bom')->group(function () {
                Route::post('/ingredients', [RestaurantController::class, 'storeIngredient'])->name('ingredients.store');
                Route::post('/recipes', [RestaurantController::class, 'storeRecipe'])->name('recipes.store');
            });
        });
    });

    // Hotel & Rooms Module (Feature-gated + Role-gated)
    Route::prefix('hotel')->name('hotel.')->middleware('feature:room_booking')->group(function () {
        Route::middleware('role:admin,manager,receptionist')->group(function () {
            Route::get('/rooms', [HotelController::class, 'rooms'])->name('rooms');
            Route::post('/room-types', [HotelController::class, 'storeRoomType'])->name('room-types.store');
            Route::post('/rooms', [HotelController::class, 'storeRoom'])->name('rooms.store');
            Route::patch('/rooms/{room}/status', [HotelController::class, 'updateRoomStatus'])->name('rooms.status');
            Route::post('/bookings', [HotelController::class, 'storeBooking'])->name('bookings.store');
            Route::post('/bookings/{booking}/check-in', [HotelController::class, 'checkIn'])->name('bookings.check-in');
            Route::post('/bookings/{booking}/check-out', [HotelController::class, 'checkOut'])->name('bookings.check-out');

            Route::middleware('feature:guest_folio')->group(function () {
                Route::get('/bookings/{booking}/folio', [HotelController::class, 'showFolio'])->name('bookings.folio');
                Route::post('/folios/{folio}/charges', [HotelController::class, 'addFolioCharge'])->name('folios.charges');
                Route::post('/folios/{folio}/payments', [HotelController::class, 'addFolioPayment'])->name('folios.payments');
            });
        });

        Route::post('/charge-to-room', [HotelController::class, 'chargeToRoom'])
            ->middleware(['feature:charge_to_room', 'role:admin,manager,cashier,receptionist'])
            ->name('charge-to-room');
    });
});
