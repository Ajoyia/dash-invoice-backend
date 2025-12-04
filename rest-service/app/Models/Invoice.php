<?php

namespace App\Models;

use App\Models\Concerns\InvoiceScopes;
use App\Models\Concerns\InvoiceRelations;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, InvoiceScopes, InvoiceRelations, HasUuid;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}