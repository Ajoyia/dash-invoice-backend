<?php

namespace App\Http\Middleware;

use App\Constants;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PermissionHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        try {
            if ($token === null) {
                return response()->json(['message' => 'Please provide correct token'], 401);
            }

            $token_response = (array) JWT::decode($token, new Key(config('session.JWT_KEY'), 'HS256'));
            /* if (isset($token_response["user_id"]) == false) {
                return response()->json(['message' => 'Token is invalid!'], 419); //commenting it out a token can exist without a user id
            } */
            // Set auth user id
            if (! empty($token_response['user_id'])) {
                $request->request->add(['auth_user_id' => $token_response['user_id']]);
            }
            // Set auth user id
            if (! empty($token_response['roles'])) {
                $request->request->add(['auth_user_roles' => $token_response['roles']]);
            }
            // Get permission constants
            $constant_permissions = Constants::PERMISSIONS;
            $available_permissions = [];
            // Map token permissions to current users available permission
            foreach (($token_response['scopes']?->dental_twin ?? []) as $scope) {
                foreach ($scope as $permission_index) {
                    if (! isset($constant_permissions[$permission_index])) {
                        continue;
                    }
                    $available_permissions[] = $constant_permissions[$permission_index];
                }
            }
            // Set auth user permissions
            if (! empty($available_permissions)) {
                $request->request->add(['auth_user_permissions' => $available_permissions]);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => 'Token is invalid or expired. '.$e->getMessage()], 419);
        }

        return $next($request);
    }
}
