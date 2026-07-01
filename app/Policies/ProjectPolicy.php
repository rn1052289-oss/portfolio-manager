<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * 案件詳細を閲覧できるか判定します。
     */
    public function view(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    /**
     * 案件を更新できるか判定します。
     */
    public function update(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    /**
     * 案件を削除できるか判定します。
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }
}