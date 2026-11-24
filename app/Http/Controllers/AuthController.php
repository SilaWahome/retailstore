<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        $plans = \App\Models\SubscriptionPlan::with('features')
            ->where('is_active', true)
            ->orderBy('price_ugx')
            ->get();

        return view('auth.register', compact('plans'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'shop_name' => 'required|string|max:191',
            'business_name' => 'nullable|string|max:191',
            'business_type' => 'required|in:retail,restaurant,hotel',
            'plan_id' => 'required|exists:subscription_plans,id',
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:191',
        ]);

        $plan = \App\Models\SubscriptionPlan::findOrFail($request->plan_id);
        if ($plan->business_type !== $request->business_type) {
            return back()->withErrors(['plan_id' => 'Selected plan does not match the chosen business type.'])->withInput();
        }

        DB::beginTransaction();
        try {
            // Create tenant
            $tenant = Tenant::create([
                'name' => $request->shop_name,
                'business_name' => $request->business_name,
                'phone' => $request->phone,
                'location' => $request->location,
                'business_type' => $request->business_type,
                'current_plan_id' => $plan->id,
                'subscription_status' => 'trial',
                'subscription_package' => $plan->slug,
                'subscription_ends_at' => Carbon::now()->addDays(14),
            ]);

            // Create admin user
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'admin',
            ]);

            // Create base currency for tenant
            Currency::create([
                'tenant_id' => $tenant->id,
                'code' => strtoupper($request->currency_code),
                'symbol' => $request->currency_symbol,
                'exchange_rate' => 1.0000,
                'is_base' => true,
            ]);

            // Create default Main Branch for new tenant
            $branch = \App\Models\Branch::create([
                'tenant_id' => $tenant->id,
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'address' => $request->location ?? 'Headquarters',
                'phone' => $request->phone,
                'is_active' => true,
            ]);

            // Link admin user to the default branch
            $user->update(['branch_id' => $branch->id]);

            DB::commit();

            Auth::login($user);

            return redirect('/')->with('success', 'Registration successful! Welcome to Luxeshop.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()])->withInput();
        }
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        $sliderProducts = \App\Models\Product::withoutGlobalScopes()
            ->with(['tenant.currencies' => fn ($q) => $q->where('is_base', true)])
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->where('is_active', true)
            ->latest()
            ->take(8)
            ->get();

        return view('auth.login', compact('sliderProducts'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Security Lockout: Too many failed login attempts. Please wait {$seconds} seconds before trying again.",
            ])->onlyInput('email');
        }

        $request->session()->forget([
            'tenant_id',
            'impersonating_from_saas',
            'original_superadmin_id',
            'impersonated_tenant_name',
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            $request->session()->forget('url.intended');
            if (Auth::user()?->tenant_id) {
                session(['tenant_id' => Auth::user()->tenant_id]);
            }
            return redirect('/');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showPasswordChangeForm()
    {
        return view('auth.password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password you entered is incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->back()->with('success', 'Your password has been changed successfully!');
    }

    public function toggleAttendance(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->toTimeString();

        // Check if user has checked in today already
        $attendance = \App\Models\Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            // Check-in
            // Determine status: if check-in is after 09:00:00, they are late
            $status = Carbon::now()->hour >= 9 ? 'late' : 'present';
            
            \App\Models\Attendance::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'date' => $today,
                'check_in' => $nowTime,
                'status' => $status,
            ]);

            return redirect()->back()->with('success', 'Checked in successfully at ' . $nowTime . ' (Status: ' . ucfirst($status) . ').');
        } elseif (!$attendance->check_out) {
            // Check-out
            $attendance->update([
                'check_out' => $nowTime,
            ]);
            return redirect()->back()->with('success', 'Checked out successfully at ' . $nowTime . '.');
        } else {
            return redirect()->back()->withErrors(['error' => 'You have already checked in and checked out for today.']);
        }
    }
}
