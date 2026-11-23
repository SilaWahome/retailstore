<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeature
{
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role === 'superadmin') {
            return $next($request);
        }

        $tenant = $user->tenant;
        if (!$tenant || !$tenant->canAccess($featureKey)) {
            $message = 'Your current plan does not include this feature (' . str_replace('_', ' ', $featureKey) . '). Please upgrade your subscription.';
            if ($request->expectsJson() && !$request->header('X-Inertia')) {
                return response()->json(['message' => $message, 'upgrade_required' => true], 403);
            }

            return redirect()->route('support.index')->with('error', $message);
        }

        return $next($request);
    }
}

