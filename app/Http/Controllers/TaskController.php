<?php

namespace App\Http\Controllers;

use App\Models\Project;

class TaskController extends Controller
{
    /**
     * 指定案件に紐づくタスク一覧を表示する
     */
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()
            ->where('user_id', auth()->id())
            ->orderBy('sort_order')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        $totalTasks = $tasks->count();

        $completedTasks = $tasks
            ->where('status', 'completed')
            ->count();

        $remainingTasks = $totalTasks - $completedTasks;

        $progressRate = $totalTasks > 0
            ? (int) round(($completedTasks / $totalTasks) * 100)
            : 0;

        return view('tasks.index', compact(
            'project',
            'tasks',
            'totalTasks',
            'completedTasks',
            'remainingTasks',
            'progressRate',
        ));
    }
}