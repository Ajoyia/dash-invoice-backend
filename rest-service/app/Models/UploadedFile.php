<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UploadedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileable_id',
        'fileable_type',
        'type',
        'storage_name',
        'storage_size',
        'viewable_name',
    ];

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}
