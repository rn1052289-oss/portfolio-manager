<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ApiLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

final class ApiTokenController extends Controller
{
    /**
     * このAPIトークンに許可する操作。
     *
     * @var array<int, string>
     */
    private const TOKEN_ABILITIES = [
        'payments:create',
        'payments:read',
        'payments:confirm',
    ];

    /**
     * ログイン情報を確認し、Sanctum APIトークンを発行する。
     */
    public function store(ApiLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $email = (string) $validated['email'];
        $password = (string) $validated['password'];
        $deviceName = (string) $validated['device_name'];

        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'メールアドレスまたはパスワードが正しくありません。',
            ], 422);
        }

        /*
         * 同じ端末名で以前に発行したトークンを削除し、
         * 不要なトークンが増え続けることを防ぐ。
         */
        $user->tokens()
            ->where('name', $deviceName)
            ->delete();

        $token = $user->createToken(
            $deviceName,
            self::TOKEN_ABILITIES
        );

        return response()->json([
            'message' => 'ログインしました。',
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'abilities' => self::TOKEN_ABILITIES,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }
}
