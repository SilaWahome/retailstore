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
        if (static::$preventLoop) {
            return;
        }

        static::$preventLoop = true;

        try {
            if (!Auth::check()) {
                return;
            }

            if (session()->has('tenant_id')) {
                $builder->where($model->getTable() . '.tenant_id', session('tenant_id'));
                return;
            }

            $user = Auth::user();
            if ($user && $user->tenant_id) {
                session(['tenant_id' => $user->tenant_id]);
                $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
            }
        } finally {
            static::$preventLoop = false;
        }
    }
}
