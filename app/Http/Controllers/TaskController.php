<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Models\Project;
use App\Models\Task;

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
            ->where('status', Task::STATUS_COMPLETED)
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

    /**
     * 指定案件のタスク登録画面を表示する
     */
    public function create(Project $project)
    {
        $this->authorize('update', $project);

        return view('tasks.create', compact('project'));
    }

    /**
     * 指定案件に新しいタスクを登録する
     */
    public function store(StoreTaskRequest $request, Project $project)
    {
        $validated = $request->validated();

        $nextSortOrder = (int) $project->tasks()
            ->where('user_id', $request->user()->id)
            ->max('sort_order') + 1;

        $project->tasks()->create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'memo' => $validated['memo'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'completed_at' => $validated['status'] === Task::STATUS_COMPLETED
                ? now()
                : null,
            'sort_order' => $nextSortOrder,
        ]);

        return redirect()
            ->route('projects.tasks.index', $project)
            ->with('success', 'タスクを登録しました。');
    }
}