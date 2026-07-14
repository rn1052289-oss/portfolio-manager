<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectImageService;
use App\Services\ProjectSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProjectController extends Controller
{
    public function index(
        Request $request,
        ProjectSearchService $projectSearchService
    ) {
        $user = $request->user();
        $userId = $user->id;

        $categories = $user
            ->categories()
            ->orderBy('name')
            ->get();

        $tags = $user
            ->tags()
            ->orderBy('name')
            ->get();

        /*
         * 一覧画面とCSV出力で検索条件がずれないように、
         * 検索・絞り込み・並び替えはServiceへまとめる。
         *
         * Request全体ではなく、必要な条件だけを渡す。
         */
        $query = $projectSearchService->build(
            $user,
            $request->only([
                'keyword',
                'category_id',
                'tag_id',
                'status',
                'sort',
            ])
        );

        /*
         * リレーション読み込みとタスク件数は、
         * 一覧画面で必要な表示データなのでController側で追加する。
         */
        $query
            ->with([
                'category',
                'tags' => fn($query) => $query->orderBy('name'),
            ])
            ->withCount([
                'tasks' => fn($taskQuery) => $taskQuery
                    ->where('user_id', $userId),
            ]);

        $projects = $query
            ->paginate(10)
            ->withQueryString();

        return view('projects.index', compact(
            'projects',
            'categories',
            'tags',
        ));
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

        return view('projects.create', compact(
            'categories',
            'tags',
        ));
    }

    public function store(
        StoreProjectRequest $request,
        ProjectImageService $projectImageService
    ) {
        $validated = $request->validated();

        $tagIds = $validated['tag_ids'] ?? [];

        /*
         * tag_idsは中間テーブル用、
         * cover_imageはUploadedFileなので
         * projectsテーブルへ直接保存しない。
         */
        unset(
            $validated['tag_ids'],
            $validated['cover_image']
        );

        $storedImagePath = null;

        try {
            if ($request->hasFile('cover_image')) {
                /** @var \Illuminate\Http\UploadedFile $coverImage */
                $coverImage = $request->file('cover_image');

                $storedImagePath = $projectImageService
                    ->store($coverImage);

                $validated['cover_image_path'] = $storedImagePath;
            }

            DB::transaction(function () use ($validated, $tagIds) {
                $project = auth()->user()
                    ->projects()
                    ->create($validated);

                $project->tags()->sync($tagIds);
            });
        } catch (Throwable $exception) {
            /*
             * DB処理に失敗してもStorageは自動rollbackされない。
             * 先に保存した新画像を補償処理として削除する。
             */
            if ($storedImagePath !== null) {
                $projectImageService
                    ->delete($storedImagePath);
            }

            throw $exception;
        }

        return redirect()
            ->route('projects.index')
            ->with('success', '案件を登録しました。');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $userId = auth()->id();

        $project->load([
            'category',
            'tags' => fn($query) => $query->orderBy('name'),
        ]);

        $project->loadCount([
            'tasks as total_tasks_count' => fn($query) => $query
                ->where('user_id', $userId),

            'tasks as not_started_tasks_count' => fn($query) => $query
                ->where('user_id', $userId)
                ->where('status', Task::STATUS_NOT_STARTED),

            'tasks as in_progress_tasks_count' => fn($query) => $query
                ->where('user_id', $userId)
                ->where('status', Task::STATUS_IN_PROGRESS),

            'tasks as completed_tasks_count' => fn($query) => $query
                ->where('user_id', $userId)
                ->where('status', Task::STATUS_COMPLETED),
        ]);

        $totalTasks = $project->total_tasks_count;
        $notStartedTasks = $project->not_started_tasks_count;
        $inProgressTasks = $project->in_progress_tasks_count;
        $completedTasks = $project->completed_tasks_count;

        $progressRate = $totalTasks > 0
            ? (int) round(($completedTasks / $totalTasks) * 100)
            : 0;

        $relatedTasks = $project->tasks()
            ->where('user_id', $userId)
            ->orderBy('sort_order')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('projects.show', compact(
            'project',
            'totalTasks',
            'notStartedTasks',
            'inProgressTasks',
            'completedTasks',
            'progressRate',
            'relatedTasks',
        ));
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

        return view('projects.edit', compact(
            'project',
            'categories',
            'tags',
        ));
    }

    public function update(
        UpdateProjectRequest $request,
        Project $project,
        ProjectImageService $projectImageService
    ) {
        $this->authorize('update', $project);

        $validated = $request->validated();

        $tagIds = $validated['tag_ids'] ?? [];

        /*
         * tag_idsは中間テーブル用、
         * cover_imageはUploadedFileなので
         * projectsテーブルへ直接保存しない。
         */
        unset(
            $validated['tag_ids'],
            $validated['cover_image']
        );

        /*
         * DB更新前の画像パスを保持する。
         *
         * DB更新成功後に旧画像を削除するため、
         * $project->update()より前に取得しておく。
         */
        $oldImagePath = $project->cover_image_path;

        /*
         * 今回新しく保存した画像のパス。
         *
         * DB更新に失敗した場合は、
         * この画像をcleanupする。
         */
        $storedImagePath = null;

        try {
            if ($request->hasFile('cover_image')) {
                /** @var \Illuminate\Http\UploadedFile $coverImage */
                $coverImage = $request->file('cover_image');

                $storedImagePath = $projectImageService
                    ->store($coverImage);

                $validated['cover_image_path'] = $storedImagePath;
            }

            DB::transaction(function () use ($project, $validated, $tagIds) {
                $project->update($validated);

                $project->tags()->sync($tagIds);
            });
        } catch (Throwable $exception) {
            /*
             * Storage操作はDBトランザクションで
             * 自動rollbackされない。
             *
             * DB更新に失敗した場合は、
             * 先に保存した新画像だけを削除する。
             *
             * 旧画像はまだ削除していないため維持される。
             */
            if ($storedImagePath !== null) {
                $projectImageService
                    ->delete($storedImagePath);
            }

            throw $exception;
        }

        /*
         * DB更新・タグ同期がすべて成功したあとで
         * 古い画像を削除する。
         *
         * 削除失敗時はProjectImageService内で
         * warningログを記録し、案件更新自体は成功扱いにする。
         */
        if (
            $storedImagePath !== null
            && $oldImagePath !== null
            && $oldImagePath !== $storedImagePath
        ) {
            $projectImageService
                ->delete($oldImagePath);
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', '案件を更新しました。');
    }

    public function destroy(
        Project $project,
        ProjectImageService $projectImageService
    ) {
        $this->authorize('delete', $project);

        /*
         * 案件削除後にStorageから画像を削除するため、
         * DBレコードが残っているうちに画像パスを保持する。
         */
        $imagePath = $project->cover_image_path;

        /*
         * DB削除が失敗した場合は例外が発生し、
         * 後続の画像削除処理へ進まない。
         */
        DB::transaction(function () use ($project) {
            $project->delete();
        });

        /*
         * DB削除が成功したあとで画像を削除する。
         *
         * 画像削除に失敗した場合は、
         * ProjectImageService内でwarningログを記録する。
         * 案件削除自体は成功扱いとする。
         */
        if ($imagePath !== null) {
            $projectImageService->delete($imagePath);
        }

        return redirect()
            ->route('projects.index')
            ->with('success', '案件を削除しました。');
    }
}