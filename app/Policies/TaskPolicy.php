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
}