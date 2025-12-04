<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$check_permissions): Response
    {

        //Get request variables
        $available_permissions = $request->get('auth_user_permissions');
        $roles = $request->get('auth_user_roles');
        if (in_array("admin", $roles)) {
            return $next($request);
        }

        if ($roles === null || $available_permissions === null) {
            return response()->json(['message' => 'Invalid token provided!'], 403);
        }

        //Check if the given permission exist in available permission
        foreach ($check_permissions as $permission) {
            if (in_array($permission, $available_permissions))
                return $next($request);
        }
        return response()->json(['message' => 'You do not have enough permissions to access this functionality. Missing Permission:' . $check_permissions[0] ?? ''], 403);
    }
}
