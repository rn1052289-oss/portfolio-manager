<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * タスクを更新できるか判定する。
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * タスクを削除できるか判定する。
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}