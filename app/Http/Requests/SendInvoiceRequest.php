<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendInvoiceRequest extends FormRequest
{
    /**
     * このリクエストを実行できるユーザーか判定します。
     *
     * 請求書を送信できるかどうかはInvoicePolicyで判定し、
     * ここではログイン状態だけを確認します。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * バリデーション前に入力値を整えます。
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'recipient_email' => is_string(
                $this->input('recipient_email')
            )
                ? trim($this->input('recipient_email'))
                : $this->input('recipient_email'),

            'subject' => is_string($this->input('subject'))
                ? trim($this->input('subject'))
                : $this->input('subject'),

            'body' => is_string($this->input('body'))
                ? trim($this->input('body'))
                : $this->input('body'),
        ]);
    }

    /**
     * 請求書メール送信時のバリデーションルールです。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
            ],

            'subject' => [
                'required',
                'string',
                'max:255',
            ],

            'body' => [
                'required',
                'string',
                'max:5000',
            ],

            /*
             * 以下はシステム側で管理する値です。
             *
             * URLの請求書情報や送信結果をブラウザから
             * 書き換えられないように拒否します。
             */
            'invoice_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'status' => ['prohibited'],
            'sent_at' => ['prohibited'],
            'last_sent_to' => ['prohibited'],
            'send_count' => ['prohibited'],
            'pdf_path' => ['prohibited'],
        ];
    }

    /**
     * バリデーションエラー時に表示する項目名です。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'recipient_email' => '送信先メールアドレス',
            'subject' => '件名',
            'body' => '本文',
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
            'recipient_email.required' => '送信先メールアドレスを入力してください。',
            'recipient_email.string' => '送信先メールアドレスを文字列で入力してください。',
            'recipient_email.email' => '送信先メールアドレスの形式が正しくありません。',
            'recipient_email.max' => '送信先メールアドレスは255文字以内で入力してください。',

            'subject.required' => '件名を入力してください。',
            'subject.string' => '件名を文字列で入力してください。',
            'subject.max' => '件名は255文字以内で入力してください。',

            'body.required' => '本文を入力してください。',
            'body.string' => '本文を文字列で入力してください。',
            'body.max' => '本文は5000文字以内で入力してください。',

            '*.prohibited' => 'この項目はフォームから指定できません。',
        ];
    }
}
