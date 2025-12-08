<?php

namespace App\Utils;

use Illuminate\Http\Request;

class PermissionChecker
{
    public static function isAdmin(Request $request)
    {
        $roles = $request->get('auth_user_roles');

        if (in_array('admin', $roles)) {
            return true;
        }

        return false;
    }
}
