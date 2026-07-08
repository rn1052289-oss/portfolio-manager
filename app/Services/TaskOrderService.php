<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskOrderService
{
    /**
     * 指定案件に紐づくタスクの並び順を更新する。
     *
     * @param  array<int, int>  $taskIds
     *
     * @throws ValidationException
     */
    public function reorder(
        Project $project,
        User $user,
        array $taskIds
    ): void {
        DB::transaction(function () use ($project, $user, $taskIds): void {
            $tasks = $project->tasks()
                ->where('user_id', $user->id)
                ->select('id', 'sort_order')
                ->lockForUpdate()
                ->get();

            $storedTaskIds = $tasks
                ->pluck('id')
                ->all();

            $normalizedStoredTaskIds = $storedTaskIds;
            $normalizedRequestedTaskIds = $taskIds;

            sort($normalizedStoredTaskIds);
            sort($normalizedRequestedTaskIds);

            if ($normalizedStoredTaskIds !== $normalizedRequestedTaskIds) {
                throw ValidationException::withMessages([
                    'task_ids' => [
                        'タスクの並び順が不正です。',
                    ],
                ]);
            }

            $tasksById = $tasks->keyBy('id');

            foreach ($taskIds as $index => $taskId) {
                $tasksById->get($taskId)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });
    }
}