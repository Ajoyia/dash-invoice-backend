<?php

namespace App\Models;

use App\Helpers\UUIDGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = UUIDGenerator::generateUUID();

            if (request()) {
                $model->created_by = request()->get('auth_user_id') ?? null;
            }
        });

        static::updating(function ($model) {
            if (request()) {
                $model->created_by = request()->get('auth_user_id') ?? null;
            }
        });
    }

    use HasFactory;

    protected $fillable = ['key', 'value'];
}
