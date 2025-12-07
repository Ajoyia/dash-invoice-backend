<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\CompanyRelations;
use App\Models\Concerns\CompanyScopes;
use App\Models\Concerns\CompanyBusinessLogic;

class Company extends Model
{
    use HasFactory, SoftDeletes, HasUuid, CompanyRelations, CompanyScopes, CompanyBusinessLogic;

    protected $casts = ['stripe_subscription_object' => 'array'];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected $appends = ['display_name'];

    public function getDisplayNameAttribute(): string
    {
        return ($this->company_number ?? '') . ' ' . ($this->company_name ?? '');
    }
}
