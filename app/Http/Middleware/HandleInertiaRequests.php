<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'inertia';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $tenant = $user?->tenant?->loadMissing('plan.features');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ] : null,
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'business_name' => $tenant->business_name,
                    'location' => $tenant->location,
                    'phone' => $tenant->phone,
                    'business_type' => $tenant->business_type,
                    'tax_rate' => (float) ($tenant->tax_rate ?? 0),
                    'plan_name' => $tenant->plan?->name,
                    'plan_slug' => $tenant->plan?->slug,
                    'room_limit' => $tenant->plan?->room_limit,
                    'features' => $tenant->plan?->features->pluck('feature_key')->values()->all() ?? [],
                ] : null,
                'impersonating' => (bool) $request->session()->get('impersonating_from_saas', false),
                'impersonated_tenant_name' => $request->session()->get('impersonated_tenant_name'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}

