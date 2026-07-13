<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * 顧客を更新できるユーザーか判定します。
     */
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        if (!$customer instanceof Customer) {
            return false;
        }

        return $this->user()?->can(
            'update',
            $customer
        ) ?? false;
    }

    /**
     * 顧客更新時のバリデーションルールです。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Customer $customer */
        $customer = $this->route('customer');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('customers', 'name')
                    ->where(function ($query) {
                        $query->where(
                            'user_id',
                            $this->user()->id
                        );
                    })
                    ->ignore($customer),
            ],

            'contact_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'memo' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * エラー表示に使用する項目名です。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '顧客名',
            'contact_name' => '担当者名',
            'email' => 'メールアドレス',
            'phone' => '電話番号',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'memo' => 'メモ',
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
            'name.required' => '顧客名を入力してください。',
            'name.unique' => '同じ名前の顧客がすでに登録されています。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'memo.max' => 'メモは2000文字以内で入力してください。',
        ];
    }
}