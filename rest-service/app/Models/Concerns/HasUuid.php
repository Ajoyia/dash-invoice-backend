<?php

namespace App\Models\Concerns;

use App\Helpers\UUIDGenerator;

trait HasUuid
{
    public static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = UUIDGenerator::generateUUID();
            }
        });
    }
}
