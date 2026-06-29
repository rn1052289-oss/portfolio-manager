<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = auth()->user()
            ->projects()
            ->latest()
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:not_started,in_progress,completed,paused'],
            'budget' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        auth()->user()
            ->projects()
            ->create($validated);

        return redirect()
            ->route('projects.index')
            ->with('success', '案件を登録しました。');
    }

    public function show(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        return redirect()
            ->route('projects.index')
            ->with('success', '案件更新処理の仮ルートです。');
    }

    public function destroy(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        return redirect()
            ->route('projects.index')
            ->with('success', '案件削除処理の仮ルートです。');
    }
}