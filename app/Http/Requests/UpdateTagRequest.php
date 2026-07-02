<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    /**
     * ログインユーザーだけがタグを更新できる
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * タグ更新のバリデーションルール
     */
    public function rules(): array
    {
        $tag = $this->route('tag');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tags', 'name')
                    ->where(function ($query) {
                        return $query->where('user_id', auth()->id());
                    })
                    ->ignore($tag?->id),
            ],
        ];
    }

    /**
     * エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'name.required' => 'タグ名を入力してください。',
            'name.string' => 'タグ名は文字列で入力してください。',
            'name.max' => 'タグ名は50文字以内で入力してください。',
            'name.unique' => 'このタグ名はすでに登録されています。',
        ];
    }
}