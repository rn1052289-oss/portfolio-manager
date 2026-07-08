<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class ReorderTasksRequest extends FormRequest
{
    /**
     * この並び替えリクエストを実行できるか判定する。
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (!$project instanceof Project) {
            return false;
        }

        return $this->user()?->can('update', $project) ?? false;
    }

    /**
     * タスク並び替えで受け付ける入力値を検証する。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'task_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'task_ids.*' => [
                'required',
                'integer',
                'distinct:strict',
                'exists:tasks,id',
            ],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義する。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'task_ids.required' => 'タスクの並び順が送信されていません。',
            'task_ids.array' => 'タスクの並び順は配列形式で送信してください。',
            'task_ids.min' => '並び替え対象のタスクを1件以上指定してください。',

            'task_ids.*.required' => 'タスクIDが不足しています。',
            'task_ids.*.integer' => 'タスクIDは整数で指定してください。',
            'task_ids.*.distinct' => '同じタスクIDを重複して指定することはできません。',
            'task_ids.*.exists' => '存在しないタスクが含まれています。',
        ];
    }
}