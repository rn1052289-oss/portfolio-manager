<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Payment;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmPaymentRequest extends FormRequest
{
    /**
     * 認証済みユーザーからのリクエストだけ許可する。
     *
     * Paymentの所有権はPaymentPolicyで別途確認する。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * HTTPヘッダーのIdempotency-Keyを
     * バリデーション対象へ追加する。
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method' => [
                'bail',
                'required',
                'string',
                'starts_with:pm_',
                'max:100',
            ],

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
            'payment_method.required' => '支払い方法を指定してください。',
            'payment_method.string' => '支払い方法は文字列で指定してください。',
            'payment_method.starts_with' => '支払い方法はStripe PaymentMethod形式で指定してください。',
            'payment_method.max' => '支払い方法の値が長すぎます。',

            'idempotency_key.required' => 'Idempotency-Keyヘッダーを指定してください。',
            'idempotency_key.uuid' => 'Idempotency-KeyはUUID形式で指定してください。',
        ];
    }
}
