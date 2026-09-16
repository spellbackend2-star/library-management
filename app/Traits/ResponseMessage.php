<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ResponseMessage
{
    /**
     * Success response.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success.',
        int $code = 200,
        ?array $meta = null
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Error response.
     */
    protected function errorResponse(
        string $message = 'Something went wrong.',
        int $code = 400,
        mixed $data = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
