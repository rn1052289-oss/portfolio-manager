<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    /**
     * 顧客を登録できるユーザーか判定します。
     */
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Customer::class
        ) ?? false;
    }

    /**
     * 顧客登録時のバリデーションルールです。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
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
                    }),
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

            'business_card_front' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'business_card_back' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
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
            'business_card_front' => '名刺表面',
            'business_card_back' => '名刺裏面',
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

            'business_card_front.image'
            => '名刺表面には有効な画像ファイルを指定してください。',

            'business_card_front.mimes'
            => '名刺表面はJPG、JPEG、PNG、WebP形式で指定してください。',

            'business_card_front.max'
            => '名刺表面は5MB以下で指定してください。',

            'business_card_back.image'
            => '名刺裏面には有効な画像ファイルを指定してください。',

            'business_card_back.mimes'
            => '名刺裏面はJPG、JPEG、PNG、WebP形式で指定してください。',

            'business_card_back.max'
            => '名刺裏面は5MB以下で指定してください。',
        ];
    }
}