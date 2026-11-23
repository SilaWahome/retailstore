<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Product;
use App\Models\Currency;
use App\Models\IoTecTransaction;
use App\Models\Sale;
use Illuminate\Http\Request;

// 1. Authenticate as an admin or cashier
$user = User::first();
if (!$user) {
    echo "No user found\n";
    exit(1);
}
auth()->login($user);

// 2. Find or create a test product with stock
$product = Product::where('tenant_id', $user->tenant_id)->where('quantity', '>', 5)->first();
if (!$product) {
    echo "No product with sufficient stock found\n";
    exit(1);
}

$baseCurrency = Currency::where('tenant_id', $user->tenant_id)->where('is_base', true)->first() ?: Currency::first();

echo "Testing POS STK Checkout for User #{$user->id}, Product #{$product->id} ({$product->name}, Price: {$product->price})\n";

// 3. Create checkout request
$controller = app(\App\Http\Controllers\POSController::class);

$cartData = json_encode([
    ['product_id' => $product->id, 'qty' => 1]
]);

$req = Request::create('/pos/checkout', 'POST', [
    'customer_name' => 'Test Mobile Client',
    'customer_phone' => '0771234567',
    'mobile_network' => 'MTN',
    'discount' => 0,
    'tax_rate' => 0,
    'payment_method' => 'mobile',
    'currency_code' => $baseCurrency->code,
    'amount_paid' => $product->price + 500,
    'cart' => $cartData,
]);
$req->headers->set('X-Requested-With', 'XMLHttpRequest');
$req->headers->set('Accept', 'application/json');

$response = $controller->checkout($req);
$responseData = json_decode($response->getContent(), true);

echo "Checkout Response Status: " . $response->getStatusCode() . "\n";
echo "Response Success: " . ($responseData['success'] ? 'YES' : 'NO') . "\n";
echo "Is Mobile: " . (!empty($responseData['is_mobile']) ? 'YES' : 'NO') . "\n";
echo "Is Demo: " . (!empty($responseData['is_demo']) ? 'YES' : 'NO') . "\n";

if (empty($responseData['success'])) {
    echo "Error: " . ($responseData['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

$saleId = $responseData['sale']['id'];
$txId = $responseData['iotec_transaction']['id'];
echo "Created Sale ID: #{$saleId}\n";
echo "Created IoTecTransaction ID: #{$txId}\n";

$tx = IoTecTransaction::find($txId);
echo "Initial Transaction Status: {$tx->status}\n";

// 4. Test Simulate STK Push Approval
$simReq = Request::create('/pos/stk-simulate/' . $txId, 'POST', ['action' => 'complete']);
$simReq->headers->set('X-Requested-With', 'XMLHttpRequest');
$simResponse = $controller->simulateStk($tx, $simReq);
$simData = json_decode($simResponse->getContent(), true);

echo "Simulate Response Status: {$simData['status']}\n";

$updatedSale = Sale::find($saleId);
echo "Sale Payment Status After Approval: {$updatedSale->payment_status}\n";

// 5. Test Cancel & Stock Restoration
$stockBefore = $product->fresh()->quantity;
$cartData2 = json_encode([['product_id' => $product->id, 'qty' => 2]]);
$req2 = Request::create('/pos/checkout', 'POST', [
    'customer_name' => 'Cancel Test Client',
    'customer_phone' => '0701234567',
    'mobile_network' => 'Airtel',
    'discount' => 0,
    'tax_rate' => 0,
    'payment_method' => 'mobile',
    'currency_code' => $baseCurrency->code,
    'amount_paid' => ($product->price * 2) + 500,
    'cart' => $cartData2,
]);
$req2->headers->set('X-Requested-With', 'XMLHttpRequest');
$req2->headers->set('Accept', 'application/json');
$res2 = $controller->checkout($req2);
$data2 = json_decode($res2->getContent(), true);
$tx2 = IoTecTransaction::find($data2['iotec_transaction']['id']);
$stockAfterCheckout = $product->fresh()->quantity;
echo "Stock Decremented: " . ($stockBefore - $stockAfterCheckout === 2 ? 'YES' : 'NO') . "\n";

$cancelRes = $controller->cancelStk($tx2);
$stockAfterCancel = $product->fresh()->quantity;
echo "Stock Restored on Cancel: " . ($stockAfterCancel === $stockBefore ? 'YES' : 'NO') . "\n";
echo "Cancelled Status: " . $tx2->fresh()->status . "\n";

if ($updatedSale->payment_status === 'paid' && $simData['status'] === 'complete' && $stockAfterCancel === $stockBefore) {
    echo "✅ ALL STK PUSH POS INTEGRATION CHECKS (APPROVAL & CANCEL/RESTORE) PASSED PERFECTLY!\n";
} else {
    echo "❌ Checks failed!\n";
    exit(1);
}
