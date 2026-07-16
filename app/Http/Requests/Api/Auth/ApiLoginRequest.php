<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class ApiLoginRequest extends FormRequest
{
    /**
     * 未ログインユーザーからのリクエストを許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
            ],
            'device_name' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '正しいメールアドレス形式で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'password.required' => 'パスワードを入力してください。',
            'password.max' => 'パスワードは255文字以内で入力してください。',
            'device_name.required' => '端末名を入力してください。',
            'device_name.max' => '端末名は100文字以内で入力してください。',
        ];
    }
}
