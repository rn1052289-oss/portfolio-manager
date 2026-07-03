<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $categories = auth()->user()
            ->categories()
            ->orderBy('name')
            ->get();

        $tags = auth()->user()
            ->tags()
            ->orderBy('name')
            ->get();

        $query = auth()->user()
            ->projects()
            ->with([
                'category',
                'tags' => fn($query) => $query->orderBy('name'),
            ]);

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

        if ($request->filled('tag_id')) {
            $tagId = (int) $request->input('tag_id');

            if ($tags->contains('id', $tagId)) {
                $query->whereHas('tags', function ($tagQuery) use ($tagId) {
                    $tagQuery->where('tags.id', $tagId);
                });
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

        return view('projects.index', compact('projects', 'categories', 'tags'));
    }

    public function create()
    {
        $categories = auth()->user()
            ->categories()
            ->orderBy('name')
            ->get();

        $tags = auth()->user()
            ->tags()
            ->orderBy('name')
            ->get();

        return view('projects.create', compact('categories', 'tags'));
    }

    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();

        $tagIds = $validated['tag_ids'] ?? [];

        unset($validated['tag_ids']);

        DB::transaction(function () use ($validated, $tagIds) {
            $project = auth()->user()
                ->projects()
                ->create($validated);

            $project->tags()->sync($tagIds);
        });

        return redirect()
            ->route('projects.index')
            ->with('success', '案件を登録しました。');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'category',
            'tags' => fn($query) => $query->orderBy('name'),
        ]);

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        $categories = auth()->user()
            ->categories()
            ->orderBy('name')
            ->get();

        $tags = auth()->user()
            ->tags()
            ->orderBy('name')
            ->get();

        $project->load('tags');

        return view('projects.edit', compact('project', 'categories', 'tags'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validated();

        $tagIds = $validated['tag_ids'] ?? [];

        unset($validated['tag_ids']);

        DB::transaction(function () use ($project, $validated, $tagIds) {
            $project->update($validated);

            $project->tags()->sync($tagIds);
        });

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