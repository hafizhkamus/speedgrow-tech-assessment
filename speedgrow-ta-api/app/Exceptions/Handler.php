<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                return $this->handleApiException($e);
            }
        });
    }

    /**
     * Handle API exceptions
     */
    protected function handleApiException(Throwable $e): JsonResponse
    {
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        if ($e instanceof TokenExpiredException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token has expired'
            ], 401);
        }

        if ($e instanceof TokenInvalidException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token is invalid'
            ], 401);
        }

        if ($e instanceof TokenBlacklistedException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token has been blacklisted'
            ], 401);
        }

        if ($e instanceof JWTException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authorization token not found'
            ], 401);
        }

        // Fallback for other exceptions
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], $this->getStatusCode($e));
    }

    /**
     * Get appropriate status code for exception
     */
    protected function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        return 500;
    }
}
