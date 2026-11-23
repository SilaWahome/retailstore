<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SupportTicket;
use App\Models\IoTecTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SaaSController extends Controller
{
    public function index()
    {
        // 1. SaaS Aggregate Analytics (All Tenants)
        $totalTenants = Tenant::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalProducts = Product::withoutGlobalScopes()->count();
        $grossSales = Sale::withoutGlobalScopes()->sum('total');

        // 2. Tenants detailed overview with aggregates
        $tenantsList = Tenant::withCount(['users', 'products', 'sales'])
            ->get()
            ->map(function ($tenant) {
                $tenant->total_sales_amount = Sale::withoutGlobalScopes()->where('tenant_id', $tenant->id)->sum('total');
                return $tenant;
            });

        // 3. All Admin Users list
        $adminsList = User::where('role', 'admin')
            ->with('tenant')
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Support tickets submitted by clients
        $ticketsList = SupportTicket::with(['tenant', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 5. Chart Metrics
        $packageDistribution = Tenant::selectRaw('subscription_package, count(*) as count')
            ->groupBy('subscription_package')
            ->get();

        $ticketStatusDistribution = SupportTicket::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        // 6. Soft Deleted Sales Audit Log (Validity: 1 Year)
        $softDeletedSales = Sale::onlyTrashed()
            ->withoutGlobalScopes()
            ->with(['tenant', 'branch', 'user', 'deletedByUser', 'items.product'])
            ->orderBy('deleted_at', 'desc')
            ->paginate(15, ['*'], 'deleted_page');

        $totalDeletedSalesCount = Sale::onlyTrashed()->withoutGlobalScopes()->count();
        $totalDeletedSalesAmount = Sale::onlyTrashed()->withoutGlobalScopes()->sum('total');

        // 7. ioTec Mobile Money Metrics (UGX)
        $totalIotecCollections = (float) IoTecTransaction::where('type', 'collection')
            ->where('status', 'complete')
            ->sum('amount');

        $totalIotecSaasFees = (float) IoTecTransaction::where('type', 'disbursement')
            ->where('status', 'complete')
            ->sum('fee');

        $totalIotecDisbursements = (float) IoTecTransaction::where('type', 'disbursement')
            ->where('status', 'complete')
            ->sum('amount');

        $iotecTransactions = IoTecTransaction::with(['tenant', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'iotec_page');

        return view('saas.analytics', compact(
            'totalTenants',
            'totalAdmins',
            'totalProducts',
            'grossSales',
            'tenantsList',
            'adminsList',
            'ticketsList',
            'packageDistribution',
            'ticketStatusDistribution',
            'softDeletedSales',
            'totalDeletedSalesCount',
            'totalDeletedSalesAmount',
            'totalIotecCollections',
            'totalIotecSaasFees',
            'totalIotecDisbursements',
            'iotecTransactions'
        ));
    }

    /**
     * Print receipt for a soft-deleted sale.
     */
    public function printDeletedReceipt($id)
    {
        $sale = Sale::onlyTrashed()
            ->withoutGlobalScopes()
            ->with(['tenant', 'branch', 'user', 'deletedByUser', 'items.product'])
            ->findOrFail($id);

        return view('saas.receipt_deleted', compact('sale'));
    }

    /**
     * Instant 1-Year Subscription Renewal for client.
     */
    public function renewSubscription(Request $request, Tenant $tenant)
    {
        // Standard validity is 1 year
        $baseDate = ($tenant->subscription_ends_at && Carbon::parse($tenant->subscription_ends_at)->isFuture())
            ? Carbon::parse($tenant->subscription_ends_at)
            : now();

        $newEndsAt = $baseDate->copy()->addYear();

        $tenant->update([
            'subscription_status' => 'active',
            'subscription_ends_at' => $newEndsAt,
        ]);

        return redirect()->route('saas')->with('success', "Tenant '{$tenant->name}' renewed instantly! Subscription is ACTIVE until " . $newEndsAt->format('Y-m-d') . ".");
    }

    /**
     * Update subscription package or custom date.
     */
    public function updateSubscription(Request $request, Tenant $tenant)
    {
        $request->validate([
            'subscription_package' => 'required|string|in:basic,premium,enterprise',
            'subscription_ends_at' => 'required|date',
        ]);

        $endsAt = Carbon::parse($request->subscription_ends_at);
        $status = $endsAt->isFuture() ? 'active' : 'expired';

        $tenant->update([
            'subscription_package' => $request->subscription_package,
            'subscription_ends_at' => $request->subscription_ends_at,
            'subscription_status' => $status,
        ]);

        return redirect()->route('saas')->with('success', "Subscription package updated for {$tenant->name}. Status: " . strtoupper($status));
    }

    /**
     * Toggle Tenant status between Active and Suspended.
     */
    public function toggleStatus(Tenant $tenant)
    {
        if ($tenant->subscription_status === 'active') {
            $newStatus = 'suspended';
            $tenant->update(['subscription_status' => $newStatus]);
        } else {
            // Activating an expired or suspended tenant:
            // Ensure subscription_ends_at is valid (defaults to 1 year if expired or missing)
            $endsAt = $tenant->subscription_ends_at;
            if (!$endsAt || Carbon::parse($endsAt)->isPast()) {
                $endsAt = now()->addYear();
            }

            $newStatus = 'active';
            $tenant->update([
                'subscription_status' => 'active',
                'subscription_ends_at' => $endsAt,
            ]);
        }

        return redirect()->route('saas')->with('success', "Tenant '{$tenant->name}' status changed to " . strtoupper($newStatus) . ".");
    }

    public function resolveTicket(SupportTicket $ticket)
    {
        $ticket->update(['status' => 'resolved']);
        return redirect()->route('saas')->with('success', "Support ticket #{$ticket->id} resolved.");
    }

    public function impersonate(Tenant $tenant)
    {
        $superadminId = auth()->id();

        // Find tenant admin user or staff user
        $tenantUser = User::where('tenant_id', $tenant->id)
            ->where('role', 'admin')
            ->first();

        if (!$tenantUser) {
            $tenantUser = User::where('tenant_id', $tenant->id)->first();
        }

        if (!$tenantUser) {
            return back()->withErrors(['error' => "No user accounts registered for {$tenant->name}."]);
        }

        // Store impersonation metadata in session
        session([
            'impersonating_from_saas' => true,
            'original_superadmin_id' => $superadminId,
            'impersonated_tenant_name' => $tenant->name,
        ]);

        \Illuminate\Support\Facades\Auth::login($tenantUser);

        return redirect('/')->with('success', "Logged in as {$tenantUser->name} ({$tenant->name}).");
    }

    public function stopImpersonation()
    {
        $originalSuperadminId = session('original_superadmin_id');

        if ($originalSuperadminId) {
            $superadmin = User::find($originalSuperadminId);
            if ($superadmin && $superadmin->role === 'superadmin') {
                \Illuminate\Support\Facades\Auth::login($superadmin);
            }
        }

        session()->forget(['impersonating_from_saas', 'original_superadmin_id', 'impersonated_tenant_name']);

        return redirect()->route('saas')->with('success', 'Returned to SaaS Superadmin Dashboard.');
    }
}
