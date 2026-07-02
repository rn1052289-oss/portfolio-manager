<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    /**
     * ログインユーザーだけがタグを登録できる
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * タグ登録のバリデーションルール
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tags', 'name')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                }),
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