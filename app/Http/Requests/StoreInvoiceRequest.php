<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreInvoiceRequest extends FormRequest
{
    /**
     * このリクエストを実行できるユーザーか判定します。
     *
     * 請求書作成自体の権限はInvoicePolicyで管理し、
     * ここではログイン状態だけを確認します。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 請求書作成時のバリデーションルールです。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')
                    ->where(function ($query) use ($userId): void {
                        $query->where('user_id', $userId);
                    }),
            ],

            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')
                    ->where(function ($query) use ($userId): void {
                        $query->where('user_id', $userId);
                    }),
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'subtotal' => [
                'required',
                'integer',
                'min:1',
            ],

            'tax_amount' => [
                'required',
                'integer',
                'min:0',
            ],

            'total_amount' => [
                'required',
                'integer',
                'min:1',
            ],

            'issued_at' => [
                'required',
                'date',
            ],

            'due_date' => [
                'required',
                'date',
                'after_or_equal:issued_at',
            ],

            /*
             * 以下の値は、ユーザー入力を禁止します。
             *
             * ControllerまたはService側で安全に生成・設定します。
             */
            'user_id' => ['prohibited'],
            'invoice_number' => ['prohibited'],
            'public_token' => ['prohibited'],
            'currency' => ['prohibited'],
            'status' => ['prohibited'],

            /*
             * 請求先情報はCustomerからコピーするため、
             * フォームから直接受け取りません。
             */
            'billing_name' => ['prohibited'],
            'billing_contact_name' => ['prohibited'],
            'billing_email' => ['prohibited'],
            'billing_phone' => ['prohibited'],
            'billing_postal_code' => ['prohibited'],
            'billing_address' => ['prohibited'],

            /*
             * 発行者情報と振込先はBusinessProfileから取得し、
             * 請求書作成時点のスナップショットとして保存します。
             *
             * ブラウザから直接送信された値は使用しません。
             */
            'issuer_business_name' => ['prohibited'],
            'issuer_representative_name' => ['prohibited'],
            'issuer_email' => ['prohibited'],
            'issuer_phone' => ['prohibited'],
            'issuer_postal_code' => ['prohibited'],
            'issuer_address' => ['prohibited'],
            'issuer_registration_number' => ['prohibited'],
            'issuer_bank_name' => ['prohibited'],
            'issuer_bank_branch_name' => ['prohibited'],
            'issuer_bank_account_type' => ['prohibited'],
            'issuer_bank_account_number' => ['prohibited'],
            'issuer_bank_account_holder' => ['prohibited'],

            /*
             * 取引日・税率・端数処理方法は、
             * Controller側で現在の請求書仕様に基づいて設定します。
             */
            'transaction_date' => ['prohibited'],
            'tax_rate' => ['prohibited'],
            'tax_rounding_method' => ['prohibited'],

            /*
             * システムが管理する値です。
             */
            'sent_at' => ['prohibited'],
            'paid_at' => ['prohibited'],
            'canceled_at' => ['prohibited'],
            'pdf_path' => ['prohibited'],
            'last_sent_to' => ['prohibited'],
            'send_count' => ['prohibited'],
        ];
    }

    /**
     * 通常のルールだけでは検証しにくい、
     * 複数項目間の整合性を確認します。
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateAmounts($validator);
            $this->validateProjectCustomerCombination($validator);
        });
    }

    /**
     * 小計、消費税、合計金額の整合性を確認します。
     */
    private function validateAmounts(Validator $validator): void
    {
        if (
            $validator->errors()->has('subtotal')
            || $validator->errors()->has('tax_amount')
            || $validator->errors()->has('total_amount')
        ) {
            return;
        }

        $subtotal = (int) $this->input('subtotal');
        $taxAmount = (int) $this->input('tax_amount');
        $totalAmount = (int) $this->input('total_amount');

        if ($totalAmount !== $subtotal + $taxAmount) {
            $validator->errors()->add(
                'total_amount',
                '合計金額は、小計と消費税の合計と一致させてください。'
            );
        }
    }

    /**
     * 案件に顧客が設定されている場合、
     * 選択された顧客と一致するか確認します。
     */
    private function validateProjectCustomerCombination(
        Validator $validator
    ): void {
        if (
            $validator->errors()->has('project_id')
            || $validator->errors()->has('customer_id')
        ) {
            return;
        }

        $project = Project::query()
            ->select(['id', 'user_id', 'customer_id'])
            ->whereKey($this->integer('project_id'))
            ->where('user_id', $this->user()->id)
            ->first();

        if ($project === null) {
            return;
        }

        /*
         * 案件に顧客がまだ設定されていない場合は、
         * 請求書作成時に選択された顧客を許可します。
         */
        if ($project->customer_id === null) {
            return;
        }

        if ($project->customer_id !== $this->integer('customer_id')) {
            $validator->errors()->add(
                'customer_id',
                '選択した顧客は、この案件に登録されている顧客と一致しません。'
            );
        }
    }

    /**
     * バリデーションエラー時に表示する項目名です。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'project_id' => '案件',
            'customer_id' => '顧客',
            'title' => '請求書タイトル',
            'description' => '請求内容',
            'subtotal' => '小計',
            'tax_amount' => '消費税',
            'total_amount' => '合計金額',
            'issued_at' => '発行日',
            'due_date' => '支払期限',
        ];
    }

    /**
     * 独自のエラーメッセージです。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.required' => '案件を選択してください。',
            'project_id.integer' => '案件の値が正しくありません。',
            'project_id.exists' => '選択された案件が見つからないか、使用する権限がありません。',

            'customer_id.required' => '顧客を選択してください。',
            'customer_id.integer' => '顧客の値が正しくありません。',
            'customer_id.exists' => '選択された顧客が見つからないか、使用する権限がありません。',

            'subtotal.integer' => '小計は1円単位の整数で入力してください。',
            'subtotal.min' => '小計は1円以上で入力してください。',

            'tax_amount.integer' => '消費税は1円単位の整数で入力してください。',
            'tax_amount.min' => '消費税は0円以上で入力してください。',

            'total_amount.integer' => '合計金額は1円単位の整数で入力してください。',
            'total_amount.min' => '合計金額は1円以上で入力してください。',

            'due_date.after_or_equal' => '支払期限は発行日以降の日付を指定してください。',

            '*.prohibited' => 'この項目はフォームから指定できません。',
        ];
    }
}
