<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * このリクエストを実行できるユーザーかを判定します。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 案件登録時のバリデーションルールです。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:not_started,in_progress,completed,paused'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id);
                }),
            ],
            'budget' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
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
            'title' => '案件名',
            'client_name' => 'クライアント名',
            'description' => '案件概要',
            'status' => 'ステータス',
            'category_id' => 'カテゴリ',
            'budget' => '予算',
            'start_date' => '開始日',
            'deadline' => '納期',
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
            'deadline.after_or_equal' => '納期は開始日以降の日付を指定してください。',
            'status.in' => 'ステータスの値が正しくありません。',
            'category_id.exists' => '選択されたカテゴリが正しくありません。',
            'category_id.integer' => 'カテゴリの値が正しくありません。',
            'budget.min' => '予算は0円以上で入力してください。',
        ];
    }
}