<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role === 'superadmin' || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json(['message' => 'Unauthorized for your role.'], 403);
        }

        abort(403, 'You do not have permission to access this module.');
    }
}

