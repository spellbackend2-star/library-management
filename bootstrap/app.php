<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */

    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })

    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    */

    ->withExceptions(function (Exceptions $exceptions): void {

        // Return JSON for API requests.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool =>
                $request->is('api/*') || $request->expectsJson()
        );

        // Model not found.
        $exceptions->render(function (
            ModelNotFoundException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getModel()
                    ? class_basename($e->getModel()) . ' ID not found.'
                    : 'Requested resource not found.',
            ], 404);
        });

        // Validation errors.
        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        });

        // Authentication errors.
        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        });

        // Authorization errors.
        $exceptions->render(function (
            AuthorizationException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ], 403);
        });

        // Endpoint not found.
        $exceptions->render(function (
            NotFoundHttpException $e,
            Request $request
        ) {
            // A model exception may be wrapped in a route exception.
            $previous = $e->getPrevious();

            while ($previous !== null) {
                if ($previous instanceof ModelNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => $previous->getModel()
                            ? class_basename($previous->getModel()) . ' ID not found.'
                            : 'Requested resource not found.',
                    ], 404);
                }

                $previous = $previous->getPrevious();
            }

            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found.',
            ], 404);
        });

        // HTTP method not allowed.
        $exceptions->render(function (
            MethodNotAllowedHttpException $e,
            Request $request
        ) {
            return response()->json([
                'success' => false,
                'message' => 'HTTP method not allowed.',
            ], 405);
        });

        // Duplicate database records.
        $exceptions->render(function (
            QueryException $e,
            Request $request
        ) {
            if (($e->errorInfo[1] ?? null) !== 1062) {
                return null;
            }

            if (str_contains(
                $e->getMessage(),
                'booking_seats_show_seat_id_is_active_unique'
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seat already booked.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Duplicate record already exists.',
            ], 409);
        });

        // Unexpected exceptions.
        $exceptions->render(function (
            \Throwable $e,
            Request $request
        ) {
            if (
                ! $request->is('api/*')
                && ! $request->expectsJson()
            ) {
                return null;
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Server Error',
            ], 500);
        });
    })

    ->create();
