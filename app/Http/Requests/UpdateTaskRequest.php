<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * このリクエストを実行できるか判定する。
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task !== null
            && $this->user() !== null
            && $this->user()->can('update', $task);
    }

    /**
     * タスク更新時のバリデーションルール。
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'memo' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'status' => [
                'required',
                Rule::in([
                    'not_started',
                    'in_progress',
                    'completed',
                ]),
            ],

            'priority' => [
                'required',
                Rule::in([
                    'high',
                    'medium',
                    'low',
                ]),
            ],

            'due_date' => [
                'nullable',
                'date',
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージ。
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タスク名を入力してください。',
            'title.string' => 'タスク名は文字列で入力してください。',
            'title.max' => 'タスク名は255文字以内で入力してください。',

            'memo.string' => 'メモは文字列で入力してください。',
            'memo.max' => 'メモは2000文字以内で入力してください。',

            'status.required' => 'ステータスを選択してください。',
            'status.in' => '正しいステータスを選択してください。',

            'priority.required' => '優先度を選択してください。',
            'priority.in' => '正しい優先度を選択してください。',

            'due_date.date' => '期限は正しい日付形式で入力してください。',
        ];
    }

    /**
     * バリデーション項目名。
     */
    public function attributes(): array
    {
        return [
            'title' => 'タスク名',
            'memo' => 'メモ',
            'status' => 'ステータス',
            'priority' => '優先度',
            'due_date' => '期限',
        ];
    }
}