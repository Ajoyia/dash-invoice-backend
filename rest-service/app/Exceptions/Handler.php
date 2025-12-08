<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        // Handle Validation errors
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }

        // Handle Model not found (404)
        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
            ], 404);
        }

        // Generic fallback (500)
        return response()->json([
            'success' => false,
            'message' => config('app.debug')
                ? $exception->getMessage()   // dev
                : 'Something went wrong',    // prod
        ], 500);
    }
}
