<?php

namespace App\Http\Controllers\v1\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\CentralChangePasswordRequest;
use App\Traits\ResponseMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class CentralPasswordController extends Controller
{
    use ResponseMessage;

    public function changePassword(
        CentralChangePasswordRequest $request
    ): JsonResponse {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse(
                'Unauthenticated.',
                401
            );
        }

        $data = $request->validated();

        if (! Hash::check($data['current_password'], $user->password)) {
            return $this->errorResponse(
                'Current password is incorrect.',
                422
            );
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        return $this->successResponse(
            null,
            'Password changed successfully.'
        );
    }
}
