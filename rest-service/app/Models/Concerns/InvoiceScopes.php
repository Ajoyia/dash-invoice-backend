<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait InvoiceScopes
{
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $likeOperator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where('invoice_number', $likeOperator, '%'.$search.'%');
        });
    }
}
