<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ResetPasswordWithOtp;
use App\Actions\SendPasswordResetOtp;
use App\Actions\VerifyPasswordResetOtp;
use App\Exceptions\PasswordResetOtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ResetPasswordWithOtpRequest;
use App\Http\Requests\Api\V1\SendPasswordResetOtpRequest;
use App\Http\Requests\Api\V1\VerifyPasswordResetOtpRequest;
use Illuminate\Http\JsonResponse;

final class PasswordResetController extends Controller
{
    public function sendOtp(
        SendPasswordResetOtpRequest $request,
        SendPasswordResetOtp $action,
    ): JsonResponse {
        $action->handle($request->string('email')->value());

        return response()->json([
            'message' => __('A verification code was sent to your email.'),
        ]);
    }

    public function verifyOtp(
        VerifyPasswordResetOtpRequest $request,
        VerifyPasswordResetOtp $action,
    ): JsonResponse {
        try {
            $resetToken = $action->handle(
                $request->string('email')->value(),
                $request->string('otp')->value(),
            );
        } catch (PasswordResetOtpException $exception) {
            return response()->json([
                'error' => [
                    'code' => $exception->errorCode,
                    'message' => $exception->getMessage(),
                ],
            ], $exception->status);
        }

        return response()->json([
            'data' => [
                'reset_token' => $resetToken,
            ],
            'message' => __('Verification code accepted.'),
        ]);
    }

    public function reset(
        ResetPasswordWithOtpRequest $request,
        ResetPasswordWithOtp $action,
    ): JsonResponse {
        try {
            $action->handle(
                $request->string('email')->value(),
                $request->string('reset_token')->value(),
                $request->string('password')->value(),
            );
        } catch (PasswordResetOtpException $exception) {
            return response()->json([
                'error' => [
                    'code' => $exception->errorCode,
                    'message' => $exception->getMessage(),
                ],
            ], $exception->status);
        }

        return response()->json([
            'message' => __('Password reset successful.'),
        ]);
    }
}
