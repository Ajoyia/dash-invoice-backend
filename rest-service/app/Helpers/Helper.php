<?php

namespace App\Helpers;

use App\Models\UploadedFile;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Helper
{

    public static function getCompanyId($token)
    {
        $tokenResponse = (array) JWT::decode($token, new Key(
            config('session.JWT_KEY'),
            'HS256'
        ));
        return $tokenResponse['company_id'];
    }

    public static function isForeignKey($table, $column)
    {
        $schemaName = config('database.connections.mysql.database'); // Database name
        $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = ?
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_COLUMN_NAME IS NOT NULL", [$schemaName, $table, $column]);

        return !empty($foreignKeys);
    }

    public static function checkPermission($permission, $request)
    {
        $userPermissions = $request->get('auth_user_permissions') ?? [];
        if (in_array($permission, $userPermissions)) {
            return true;
        }
        return false;
    }

    public static function toCamelCase($string)
    {
        // make lowercase, replace spaces with underscores
        $string = strtolower(str_replace(' ', '_', $string));

        // convert to camelCase
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }


    public static function saveAttachment($file, $model, $type = null)
    {
        $originalName = $file['name'];
        $extension = $file['extension'];
        $id = $file['id'];
       $uploaded_file = new UploadedFile();
        if ($type) {
            $uploaded_file->type = $type;
        }
        $uploaded_file->storage_name = $id;
        $uploaded_file->viewable_name = $originalName;
        $uploaded_file->storage_size = $extension;
        $uploaded_file->fileable()->associate($model);
        $uploaded_file->save();
    }

    public static function removeAttachment($model, $type = null)
    {
        $uploadedFile = UploadedFile::where('fileable_id', $model->id)->where('fileable_type', get_class($model))
            ->when($type, fn($q) => $q->where('type', $type))->first();

        if ($uploadedFile)
            $uploadedFile->delete();
    }
}