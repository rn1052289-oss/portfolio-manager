<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Payment;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePaymentRequest extends FormRequest
{
    /**
     * 認証済みユーザーからのリクエストだけ許可する。
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
        $userId = (int) $this->user()?->getAuthIdentifier();

        return [
            'project_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists(Project::class, 'id')
                    ->where('user_id', $userId),
            ],

            'amount' => [
                'bail',
                'required',
                'integer',
                'min:50',
                'max:99999999',
            ],

            'description' => [
                'nullable',
                'string',
                'max:500',
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
            'project_id.required' => '案件を指定してください。',
            'project_id.integer' => '案件IDは整数で指定してください。',
            'project_id.exists' => '指定された案件が存在しないか、このユーザーの案件ではありません。',

            'amount.required' => '決済金額を入力してください。',
            'amount.integer' => '決済金額は整数で入力してください。',
            'amount.min' => '決済金額は50円以上で入力してください。',
            'amount.max' => '決済金額が上限を超えています。',

            'description.string' => '説明は文字列で入力してください。',
            'description.max' => '説明は500文字以内で入力してください。',

            'idempotency_key.required' => 'Idempotency-Keyヘッダーを指定してください。',
            'idempotency_key.uuid' => 'Idempotency-KeyはUUID形式で指定してください。',
        ];
    }
}
