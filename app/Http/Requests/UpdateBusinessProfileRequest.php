<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBusinessProfileRequest extends FormRequest
{
    /**
     * 事業者プロフィール更新フォーム専用のエラーバッグです。
     *
     * 同じプロフィール画面にあるアカウント情報や
     * パスワード変更のエラーと混ざらないようにします。
     */
    protected $errorBag = 'updateBusinessProfile';

    /**
     * ログインユーザーだけ更新を許可します。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 事業者プロフィールのバリデーションルールです。
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'business_name' => [
                'required',
                'string',
                'max:255',
            ],
            'representative_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'postal_code' => [
                'required',
                'string',
                'max:20',
                'regex:/\A\d{3}-?\d{4}\z/',
            ],
            'address' => [
                'required',
                'string',
                'max:1000',
            ],
            'invoice_registration_number' => [
                'nullable',
                'string',
                'regex:/\AT\d{13}\z/',
            ],
            'bank_name' => [
                'required',
                'string',
                'max:255',
            ],
            'bank_branch_name' => [
                'required',
                'string',
                'max:255',
            ],
            'bank_account_type' => [
                'required',
                Rule::in([
                    '普通',
                    '当座',
                    '貯蓄',
                ]),
            ],
            'bank_account_number' => [
                'required',
                'string',
                'regex:/\A\d{1,20}\z/',
            ],
            'bank_account_holder' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * 入力値をバリデーション前に整形します。
     */
    protected function prepareForValidation(): void
    {
        $registrationNumber = $this->input(
            'invoice_registration_number'
        );

        if (is_string($registrationNumber)) {
            /*
             * 小文字のt、空白、ハイフンを含む入力でも、
             * T+13桁の形式へ整えられるようにします。
             */
            $registrationNumber = strtoupper(
                preg_replace(
                    '/[\s\-]+/',
                    '',
                    trim($registrationNumber)
                ) ?? ''
            );
        }

        $bankAccountNumber = $this->input(
            'bank_account_number'
        );

        if (is_string($bankAccountNumber)) {
            /*
             * 口座番号内の空白とハイフンを取り除きます。
             */
            $bankAccountNumber = preg_replace(
                '/[\s\-]+/',
                '',
                trim($bankAccountNumber)
            ) ?? '';
        }

        $this->merge([
            'business_name' => $this->nullableTrimmedString(
                'business_name'
            ),
            'representative_name' => $this->nullableTrimmedString(
                'representative_name'
            ),
            'email' => $this->nullableTrimmedString('email'),
            'phone' => $this->nullableTrimmedString('phone'),
            'postal_code' => $this->nullableTrimmedString(
                'postal_code'
            ),
            'address' => $this->nullableTrimmedString('address'),
            'invoice_registration_number' => $registrationNumber !== ''
                ? $registrationNumber
                : null,
            'bank_name' => $this->nullableTrimmedString(
                'bank_name'
            ),
            'bank_branch_name' => $this->nullableTrimmedString(
                'bank_branch_name'
            ),
            'bank_account_type' => $this->nullableTrimmedString(
                'bank_account_type'
            ),
            'bank_account_number' => $bankAccountNumber !== ''
                ? $bankAccountNumber
                : null,
            'bank_account_holder' => $this->nullableTrimmedString(
                'bank_account_holder'
            ),
        ]);
    }

    /**
     * バリデーションメッセージです。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'postal_code.regex' => '郵便番号は123-4567または1234567の形式で入力してください。',
            'invoice_registration_number.regex' => '登録番号はTから始まる13桁の数字で入力してください。',
            'bank_account_type.in' => '口座種別を選択してください。',
            'bank_account_number.regex' => '口座番号は数字だけで入力してください。',
        ];
    }

    /**
     * 入力項目の日本語名です。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'business_name' => '屋号・会社名',
            'representative_name' => '代表者名',
            'email' => '事業用メールアドレス',
            'phone' => '電話番号',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'invoice_registration_number' => '適格請求書発行事業者登録番号',
            'bank_name' => '金融機関名',
            'bank_branch_name' => '支店名',
            'bank_account_type' => '口座種別',
            'bank_account_number' => '口座番号',
            'bank_account_holder' => '口座名義',
        ];
    }

    /**
     * 文字列の前後空白を削除し、
     * 空文字の場合はnullへ変換します。
     */
    private function nullableTrimmedString(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }
}
