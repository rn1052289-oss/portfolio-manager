<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $categories = auth()->user()
            ->categories()
            ->orderBy('name')
            ->get();

        $query = auth()->user()
            ->projects()
            ->with('category');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('client_name', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');

            if ($categories->contains('id', $categoryId)) {
                $query->where('category_id', $categoryId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sort = $request->input('sort', 'latest');

        match ($sort) {
            'deadline_asc' => $query
                ->orderByRaw('deadline IS NULL')
                ->orderBy('deadline', 'asc')
                ->orderBy('created_at', 'desc'),

            'deadline_desc' => $query
                ->orderByRaw('deadline IS NULL')
                ->orderBy('deadline', 'desc')
                ->orderBy('created_at', 'desc'),

            'oldest' => $query->orderBy('created_at', 'asc'),

            'budget_desc' => $query
                ->orderByRaw('budget IS NULL')
                ->orderBy('budget', 'desc')
                ->orderBy('created_at', 'desc'),

            'budget_asc' => $query
                ->orderByRaw('budget IS NULL')
                ->orderBy('budget', 'asc')
                ->orderBy('created_at', 'desc'),

            default => $query->orderBy('created_at', 'desc'),
        };

        $projects = $query->paginate(10)->appends($request->query());

        return view('projects.index', compact('projects', 'categories'));
    }

    public function create()
    {
        $categories = auth()->user()
            ->categories()
            ->orderBy('name')
            ->get();

        return view('projects.create', compact('categories'));
    }

    public function store(StoreProjectRequest $request)
    {
        auth()->user()
            ->projects()
            ->create($request->validated());

        return redirect()
            ->route('projects.index')
            ->with('success', '案件を登録しました。');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load('category');

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        $categories = auth()->user()
            ->categories()
            ->orderBy('name')
            ->get();

        return view('projects.edit', compact('project', 'categories'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return redirect()
            ->route('projects.show', $project)
            ->with('success', '案件を更新しました。');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', '案件を削除しました。');
    }
}