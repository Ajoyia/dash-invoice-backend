<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait CompanyScopes
{
    public function scopeFilter(Builder $query, array $filters)
    {
        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $likeOperator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

            $query->where(function ($q) use ($search, $likeOperator) {
                $q->where('company_name', $likeOperator, '%'.$search.'%')
                    ->orWhere('company_number', $likeOperator, '%'.$search.'%')
                    ->orWhere('city', $likeOperator, '%'.$search.'%')
                    ->orWhere('country', $likeOperator, '%'.$search.'%')
                    ->orWhere('vat_id', $likeOperator, '%'.$search.'%');
            });
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        });
    }
}
