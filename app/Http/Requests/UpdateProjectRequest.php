<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * このリクエストを実行できるユーザーかを判定します。
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * 案件更新時のバリデーションルールです。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],

            'cover_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'status' => ['required', 'in:not_started,in_progress,completed,paused'],
            'budget' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],

            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id);
                }),
            ],

            'tag_ids' => ['nullable', 'array'],

            'tag_ids.*' => [
                'integer',
                Rule::exists('tags', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id);
                }),
            ],
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
            'cover_image' => '案件画像',
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

            'cover_image.image' => '案件画像には有効な画像ファイルを指定してください。',
            'cover_image.mimes' => '案件画像はJPG、JPEG、PNG、WebP形式で指定してください。',
            'cover_image.max' => '案件画像は5MB以下で指定してください。',

            'tag_ids.array' => 'タグの形式が正しくありません。',
            'tag_ids.*.integer' => 'タグの値が正しくありません。',
            'tag_ids.*.exists' => '選択されたタグが見つからないか、使用できないタグです。',
        ];
    }
}