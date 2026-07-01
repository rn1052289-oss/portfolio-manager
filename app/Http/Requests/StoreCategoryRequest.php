<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'name')
                    ->where(fn($query) => $query->where('user_id', auth()->id())),
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'カテゴリ名を入力してください。',
            'name.string' => 'カテゴリ名は文字で入力してください。',
            'name.max' => 'カテゴリ名は50文字以内で入力してください。',
            'name.unique' => '同じカテゴリ名がすでに登録されています。',
            'description.string' => '説明は文字で入力してください。',
            'description.max' => '説明は500文字以内で入力してください。',
        ];
    }
}