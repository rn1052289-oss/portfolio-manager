<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * プロフィール画面を表示する
     */
    public function show(): View
    {
        $user = auth()->user();

        $totalProjectsCount = $user
            ->projects()
            ->count();

        $completedProjectsCount = $user
            ->projects()
            ->where('status', 'completed')
            ->count();

        $inProgressProjectsCount = $user
            ->projects()
            ->where('status', 'in_progress')
            ->count();

        $incompleteTasksCount = $user
            ->tasks()
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->count();

        $recentProjects = $user
            ->projects()
            ->select([
                'id',
                'title',
                'client_name',
                'status',
                'updated_at',
            ])
            ->latest('updated_at')
            ->limit(3)
            ->get();

        return view('profile.show', compact(
            'user',
            'totalProjectsCount',
            'completedProjectsCount',
            'inProgressProjectsCount',
            'incompleteTasksCount',
            'recentProjects'
        ));
    }
}