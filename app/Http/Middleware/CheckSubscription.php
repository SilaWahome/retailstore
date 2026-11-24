<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Superadmins bypass subscription checks
            if ($user->role === 'superadmin') {
                return $next($request);
            }
            
            $tenant = $user->tenant;
            if ($tenant) {
                // Ensure fresh tenant record from DB
                $tenant->refresh();

                // If subscription has ended and status is active, mark as expired
                if ($tenant->subscription_ends_at && \Carbon\Carbon::parse($tenant->subscription_ends_at)->isPast()) {
                    if ($tenant->subscription_status === 'active' || $tenant->subscription_status === 'trial') {
                        $tenant->update(['subscription_status' => 'expired']);
                        $tenant->refresh();
                    }
                }
                
                // Block access if not active/trial
                if (! in_array($tenant->subscription_status, ['active', 'trial'])) {
                    if (! $request->routeIs('support.*') && ! $request->routeIs('billing.*') && ! $request->routeIs('logout')) {
                        return redirect()->route('support.index')->with('error', 'Your store subscription is currently suspended or expired. Pay your pending bill below or submit a support ticket to restore access.');
                    }
                }
            }
        }

        return $next($request);
    }
}
