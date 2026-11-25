<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Currency;
use App\Models\SubscriptionPlan;
use App\Models\DiningArea;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $currencies = Currency::orderBy('code')->get();
        $baseCurrency = $currencies->where('is_base', true)->first() ?: $currencies->first();
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price_ugx')->get();
        return view('settings.index', compact('tenant', 'currencies', 'baseCurrency', 'plans'));
    }

    public function receiptSimulator()
    {
        $tenant = auth()->user()->tenant;
        $currencies = Currency::where('tenant_id', auth()->user()->tenant_id)->get();
        $baseCurrency = $currencies->where('is_base', true)->first() ?: $currencies->first();
        return view('settings.receipt_simulator', compact('tenant', 'baseCurrency'));
    }

    public function update(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('settings.index');
        }

        $tenant = auth()->user()->tenant;

        $request->validate([
            'name' => 'required|string|max:191',
            'business_name' => 'nullable|string|max:191',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:191',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,gif,webp|max:2048',
        ]);

        $logoPath = $tenant->logo;
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        $tenant->update([
            'name' => $request->name,
            'business_name' => $request->business_name,
            'phone' => $request->phone,
            'location' => $request->location,
            'tax_rate' => (float)$request->tax_rate,
            'logo' => $logoPath,
        ]);

        return redirect()->route('settings.index')->with('success', 'Shop settings updated successfully!');
    }

    public function addCurrency(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('settings.index');
        }

        $request->validate([
            'code' => 'required|string|max:10',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0.0001',
        ]);

        $code = strtoupper($request->code);

        // Check if currency already exists for this tenant
        if (Currency::where('code', $code)->exists()) {
            return back()->withErrors(['code' => 'This currency code is already registered.'])->withInput();
        }

        Currency::create([
            'code' => $code,
            'symbol' => $request->symbol,
            'exchange_rate' => $request->exchange_rate,
            'is_base' => false,
        ]);

        return redirect()->route('settings.index')->with('success', 'Currency ' . $code . ' added successfully.');
    }

    public function deleteCurrency(Currency $currency)
    {
        if ($currency->is_base) {
            return back()->withErrors(['error' => 'The base currency cannot be deleted.']);
        }

        $currency->delete();
        return redirect()->route('settings.index')->with('success', 'Currency deleted.');
    }

    public function setBaseCurrency(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('settings.index');
        }

        $tenantId = auth()->user()->tenant_id;

        // If user submitted a new code and symbol directly (or from preset)
        if ($request->filled('code') && $request->filled('symbol')) {
            $code = strtoupper(trim($request->code));
            $symbol = trim($request->symbol);

            DB::beginTransaction();
            try {
                // Check if this currency code already exists for this tenant
                $existing = Currency::where('tenant_id', $tenantId)->where('code', $code)->first();

                if ($existing) {
                    Currency::where('tenant_id', $tenantId)->where('is_base', true)->update(['is_base' => false]);
                    $existing->update([
                        'symbol' => $symbol,
                        'exchange_rate' => 1.0000,
                        'is_base' => true,
                    ]);
                    $targetCurrency = $existing;
                } else {
                    // Update current base currency or create new
                    $currentBase = Currency::where('tenant_id', $tenantId)->where('is_base', true)->first();
                    if ($currentBase) {
                        $currentBase->update([
                            'code' => $code,
                            'symbol' => $symbol,
                            'exchange_rate' => 1.0000,
                        ]);
                        $targetCurrency = $currentBase;
                    } else {
                        $targetCurrency = Currency::create([
                            'tenant_id' => $tenantId,
                            'code' => $code,
                            'symbol' => $symbol,
                            'exchange_rate' => 1.0000,
                            'is_base' => true,
                        ]);
                    }
                }

                DB::commit();
                session(['active_currency_id' => $targetCurrency->id]);

                return redirect()->route('settings.index')->with('success', "Store display currency successfully changed to {$code} ({$symbol})!");
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to update currency: ' . $e->getMessage()]);
            }
        }

        // Otherwise if user selected by existing currency_id
        $request->validate([
            'currency_id' => 'required|exists:currencies,id',
        ]);

        DB::beginTransaction();
        try {
            Currency::where('tenant_id', $tenantId)->where('is_base', true)->update(['is_base' => false]);
            $currency = Currency::where('tenant_id', $tenantId)->findOrFail($request->currency_id);
            $currency->update([
                'is_base' => true,
                'exchange_rate' => 1.0000
            ]);

            DB::commit();
            session(['active_currency_id' => $currency->id]);

            return redirect()->route('settings.index')->with('success', 'Active display currency set to: ' . $currency->code . ' (' . $currency->symbol . ')');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update active currency.']);
        }
    }

    public function updateCurrency(Request $request, Currency $currency)
    {
        if ($currency->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($request->isMethod('get')) {
            return redirect()->route('settings.index');
        }

        $request->validate([
            'code' => 'required|string|max:10',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0.0001',
        ]);

        $code = strtoupper(trim($request->code));

        $currency->update([
            'code' => $code,
            'symbol' => trim($request->symbol),
            'exchange_rate' => $currency->is_base ? 1.0000 : (float)$request->exchange_rate,
        ]);

        return redirect()->route('settings.index')->with('success', "Currency {$code} updated successfully.");
    }

    /**
     * Switch the active display currency for the current session.
     * Stores the currency ID in the session so all views pick it up instantly.
     */
    public function switchCurrency(Request $request)
    {
        if ($request->isMethod('get') && !$request->has('currency_id')) {
            return redirect()->back();
        }

        $request->validate([
            'currency_id' => 'required|exists:currencies,id',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $currency = Currency::where('id', $request->currency_id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        session(['active_currency_id' => $currency->id]);

        return redirect()->back()->with('success', 'Display currency switched to ' . $currency->code . ' (' . $currency->symbol . ')');
    }

    /**
     * Switch the tenant's business mode / setup (Retail Basic POS, Restaurant, Simple Hotel, Hotel Pro).
     */
    public function switchBusinessMode(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $tenant = auth()->user()->tenant;
        $plan = SubscriptionPlan::where('is_active', true)->findOrFail($request->plan_id);

        $tenant->update([
            'business_type' => $plan->business_type,
            'current_plan_id' => $plan->id,
        ]);

        if (in_array($plan->business_type, ['restaurant', 'hotel']) && !DiningArea::where('tenant_id', $tenant->id)->exists()) {
            DiningArea::create([
                'tenant_id' => $tenant->id,
                'name' => 'Main Dining',
                'width_m' => 15.00,
                'length_m' => 12.00,
                'sort_order' => 1,
            ]);
        }

        if ($plan->business_type === 'hotel' && !RoomType::where('tenant_id', $tenant->id)->exists()) {
            RoomType::create([
                'tenant_id' => $tenant->id,
                'name' => 'Standard Room',
                'base_price_ugx' => 80000,
                'capacity' => 2,
                'description' => 'Default starter room category.',
            ]);
        }

        if ($plan->business_type === 'hotel') {
            return redirect()->route('hotel.rooms')->with('success', "Switched to {$plan->name}! Your Hotel setup is ready.");
        }

        if ($plan->business_type === 'restaurant') {
            return redirect()->route('restaurant.tables')->with('success', "Switched to {$plan->name}! Your Restaurant POS is ready.");
        }

        return redirect()->route('settings.index')->with('success', "Switched to {$plan->name} (Basic Retail POS).");
    }
}
