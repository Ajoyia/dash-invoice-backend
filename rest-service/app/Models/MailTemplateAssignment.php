<?php

namespace App\Models;

use App\Helpers\UUIDGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailTemplateAssignment extends Model
{
    use HasFactory;

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

    protected $fillable = ['module', 'mail_template_id', 'cc', 'bcc', 'sender_mail', 'reminder_hours'];
}
