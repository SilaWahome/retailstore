<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tablesToCheck = [
    'currencies', 'products', 'purchase_orders', 'purchase_order_items',
    'sales', 'sale_items', 'inventory_logs', 'quotations', 'quotation_items',
    'expenses', 'subscriptions', 'support_tickets', 'attendances', 'categories', 'branches'
];

foreach ($tablesToCheck as $tbl) {
    echo $tbl . ': ' . (Illuminate\Support\Facades\Schema::hasTable($tbl) ? 'EXISTS' : 'MISSING') . "\n";
}
