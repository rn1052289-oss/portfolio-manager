<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Payment;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCheckoutPaymentRequest extends FormRequest
{
    /**
     * ログイン済みユーザーからのリクエストだけ許可する。
     *
     * 案件の所有権はControllerからProjectPolicyで確認する。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => [
                'bail',
                'required',
                'uuid',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'idempotency_key.required' => '決済処理用の識別キーがありません。画面を再読み込みしてください。',
            'idempotency_key.uuid' => '決済処理用の識別キーが正しくありません。画面を再読み込みしてください。',
        ];
    }
}
