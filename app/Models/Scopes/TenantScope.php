<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    protected static $preventLoop = false;

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (session()->has('tenant_id')) {
            $builder->where($model->getTable() . '.tenant_id', session('tenant_id'));
            return;
        }

        if (static::$preventLoop) {
            return;
        }

        static::$preventLoop = true;

        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->tenant_id) {
                session(['tenant_id' => $user->tenant_id]);
                $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
            }
        }

        static::$preventLoop = false;
    }
}
