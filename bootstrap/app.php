<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        //
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | JSON Response
        |--------------------------------------------------------------------------
        */

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*') || $request->expectsJson(),
        );


        /*
        |--------------------------------------------------------------------------
        | Model Not Found
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            ModelNotFoundException $e,
            Request $request
        ) {

            return response()->json([
                'success' => false,
                'message' => $e->getModel()
                    ? class_basename($e->getModel()) . ' not found.'
                    : 'Resource not found.',
            ], 404);

        });


        /*
        |--------------------------------------------------------------------------
        | Validation Error
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);

        });


        /*
        |--------------------------------------------------------------------------
        | Authorization
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            AuthorizationException $e,
            Request $request
        ) {

            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ], 403);

        });


        /*
        |--------------------------------------------------------------------------
        | Route Not Found
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            NotFoundHttpException $e,
            Request $request
        ) {

            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found.',
            ], 404);

        });


        /*
        |--------------------------------------------------------------------------
        | Method Not Allowed
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            MethodNotAllowedHttpException $e,
            Request $request
        ) {

            return response()->json([
                'success' => false,
                'message' => 'HTTP method not allowed.',
            ], 405);

        });


        /*
        |--------------------------------------------------------------------------
        | Duplicate Database Error
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            QueryException $e,
            Request $request
        ) {

            if (($e->errorInfo[1] ?? null) == 1062) {

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
            }

            return null;

        });


        /*
        |--------------------------------------------------------------------------
        | Global Exception
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Throwable $e,
            Request $request
        ) {

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Server Error',
            ], 500);

        });

    })

    ->create();