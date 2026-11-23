<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryLog;
use App\Models\Expense;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Tenant
        $tenant = Tenant::create([
            'name' => 'Luxeshop',
            'business_name' => 'Luxeshop LLC',
            'phone' => '+1 555-589-3746',
            'location' => 'Fifth Avenue, New York, USA',
            'subscription_status' => 'active',
            'subscription_package' => 'premium',
            'subscription_ends_at' => now()->addDays(30),
        ]);

        // 2. Create Users
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Luxeshop Admin',
            'email' => 'admin@luxeshop.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $cashier = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Luxeshop Cashier',
            'email' => 'cashier@luxeshop.com',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        User::create([
            'tenant_id' => null,
            'name' => 'Luxeshop Superadmin',
            'email' => 'superadmin@luxeshop.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
        ]);

        // 3. Create Currencies
        $usd = Currency::create([
            'tenant_id' => $tenant->id,
            'code' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1.0000,
            'is_base' => true,
        ]);

        $eur = Currency::create([
            'tenant_id' => $tenant->id,
            'code' => 'EUR',
            'symbol' => '€',
            'exchange_rate' => 0.9200,
            'is_base' => false,
        ]);

        $kes = Currency::create([
            'tenant_id' => $tenant->id,
            'code' => 'KES',
            'symbol' => 'KSh',
            'exchange_rate' => 130.0000,
            'is_base' => false,
        ]);

        $ugx = Currency::create([
            'tenant_id' => $tenant->id,
            'code' => 'UGX',
            'symbol' => 'USh',
            'exchange_rate' => 3700.0000,
            'is_base' => false,
        ]);

        // 3.5 Create Categories
        foreach (['Bags', 'Eyewear', 'Shoes', 'Fragrances', 'Apparel', 'Accessories'] as $catName) {
            \App\Models\Category::create([
                'tenant_id' => $tenant->id,
                'name' => $catName,
                'description' => $catName . ' product catalog category.',
            ]);
        }

        // 4. Create Products
        $bag = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Gucci Luxury Handbag',
            'category' => 'Bags',
            'sku' => 'GUC-BAG-01',
            'price' => 890.00,
            'cost' => 450.00,
            'quantity' => 15,
            'low_stock_threshold' => 5,
        ]);

        $glasses = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Prada Sunglasses',
            'category' => 'Eyewear',
            'sku' => 'PRA-SUN-02',
            'price' => 299.00,
            'cost' => 150.00,
            'quantity' => 3, // Low stock limit!
            'low_stock_threshold' => 5,
        ]);

        $shoes = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Balenciaga Sneakers',
            'category' => 'Shoes',
            'sku' => 'BAL-SNE-03',
            'price' => 750.00,
            'cost' => 380.00,
            'quantity' => 8,
            'low_stock_threshold' => 5,
        ]);

        $perfume = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Chanel No.5 Perfume',
            'category' => 'Fragrances',
            'sku' => 'CHA-PER-04',
            'price' => 145.00,
            'cost' => 80.00,
            'quantity' => 25,
            'low_stock_threshold' => 5,
        ]);

        // Log initial stocks
        foreach ([$bag, $glasses, $shoes, $perfume] as $p) {
            InventoryLog::create([
                'tenant_id' => $tenant->id,
                'product_id' => $p->id,
                'change_amount' => $p->quantity,
                'type' => 'adjustment',
                'description' => 'Initial stock seeding.',
                'created_at' => Carbon::now()->subDays(5),
            ]);
        }

        // 5. Create Sales
        // Sale 1: 3 days ago (Admin user)
        $sale1 = Sale::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'customer_name' => 'Alice Smith',
            'subtotal' => 890.00,
            'discount' => 50.00,
            'tax' => 42.00, // (890 - 50) * 0.05
            'total' => 882.00,
            'currency_code' => 'USD',
            'exchange_rate' => 1.0000,
            'amount_paid' => 900.00,
            'change_amount' => 18.00,
            'payment_method' => 'card',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        SaleItem::create([
            'sale_id' => $sale1->id,
            'product_id' => $bag->id,
            'quantity' => 1,
            'price' => 890.00,
            'total' => 890.00,
        ]);
        $bag->decrement('quantity', 1);
        InventoryLog::create([
            'tenant_id' => $tenant->id,
            'product_id' => $bag->id,
            'change_amount' => -1,
            'type' => 'sale',
            'description' => 'Sold via checkout. Sale ID: #' . $sale1->id,
            'created_at' => Carbon::now()->subDays(3),
        ]);

        // Sale 2: Today (Cashier user) in EUR
        $sub2 = 750.00 + 299.00;
        $tot2 = $sub2;
        $sale2 = Sale::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'customer_name' => 'Robert Dow',
            'subtotal' => $sub2,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => $tot2,
            'currency_code' => 'EUR',
            'exchange_rate' => 0.9200,
            'amount_paid' => 1000.00, // Paid in EUR
            'change_amount' => 34.92, // (1000 - 1049 * 0.92) = (1000 - 965.08)
            'payment_method' => 'cash',
            'created_at' => Carbon::now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale2->id,
            'product_id' => $shoes->id,
            'quantity' => 1,
            'price' => 750.00,
            'total' => 750.00,
        ]);
        $shoes->decrement('quantity', 1);
        InventoryLog::create([
            'tenant_id' => $tenant->id,
            'product_id' => $shoes->id,
            'change_amount' => -1,
            'type' => 'sale',
            'description' => 'Sold via checkout. Sale ID: #' . $sale2->id,
            'created_at' => Carbon::now(),
        ]);

        SaleItem::create([
            'sale_id' => $sale2->id,
            'product_id' => $glasses->id,
            'quantity' => 1,
            'price' => 299.00,
            'total' => 299.00,
        ]);
        $glasses->decrement('quantity', 1);
        InventoryLog::create([
            'tenant_id' => $tenant->id,
            'product_id' => $glasses->id,
            'change_amount' => -1,
            'type' => 'sale',
            'description' => 'Sold via checkout. Sale ID: #' . $sale2->id,
            'created_at' => Carbon::now(),
        ]);

        // 6. Create Expenses
        Expense::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'amount' => 850.00,
            'category' => 'rent',
            'description' => 'Monthly boutique rent payment.',
            'expense_date' => Carbon::now()->subDays(10),
        ]);

        Expense::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'amount' => 120.00,
            'category' => 'utilities',
            'description' => 'Boutique High-Speed Fiber Internet.',
            'expense_date' => Carbon::now(),
        ]);

        Expense::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'amount' => 35.00,
            'category' => 'supplies',
            'description' => 'Store coffee supplies.',
            'expense_date' => Carbon::now(),
        ]);

        // 7. Create Quotation
        $quote = Quotation::create([
            'tenant_id' => $tenant->id,
            'provider_name' => 'Luxury Designer Perfumes Corp',
            'quote_number' => 'QT-SEED-01',
            'status' => 'pending',
            'total_amount' => 900.00,
            'valid_until' => Carbon::now()->addDays(30),
        ]);

        QuotationItem::create([
            'quotation_id' => $quote->id,
            'product_name' => 'Designer Rose Perfume Essence (50ml)',
            'quantity' => 10,
            'price' => 90.00,
        ]);

        // 8. Create Mock Attendances
        // 5 days ago: present
        \App\Models\Attendance::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'date' => Carbon::now()->subDays(4)->toDateString(),
            'check_in' => '08:30:00',
            'check_out' => '17:00:00',
            'status' => 'present',
        ]);
        // 4 days ago: late
        \App\Models\Attendance::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'date' => Carbon::now()->subDays(3)->toDateString(),
            'check_in' => '09:15:00',
            'check_out' => '17:00:00',
            'status' => 'late',
        ]);
        // 3 days ago: present
        \App\Models\Attendance::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'date' => Carbon::now()->subDays(2)->toDateString(),
            'check_in' => '08:45:00',
            'check_out' => '17:00:00',
            'status' => 'present',
        ]);
        // 2 days ago: late
        \App\Models\Attendance::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'date' => Carbon::now()->subDays(1)->toDateString(),
            'check_in' => '10:00:00',
            'check_out' => '17:00:00',
            'status' => 'late',
        ]);
        // Today: present
        \App\Models\Attendance::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'date' => Carbon::now()->toDateString(),
            'check_in' => '08:15:00',
            'check_out' => null, // Not checked out yet!
            'status' => 'present',
        ]);

        // Admin attendances
        \App\Models\Attendance::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'date' => Carbon::now()->subDays(2)->toDateString(),
            'check_in' => '08:50:00',
            'check_out' => '17:30:00',
            'status' => 'present',
        ]);
        \App\Models\Attendance::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'date' => Carbon::now()->subDays(1)->toDateString(),
            'check_in' => '08:55:00',
            'check_out' => '17:00:00',
            'status' => 'present',
        ]);
    }
}
