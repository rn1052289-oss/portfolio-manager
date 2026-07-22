<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessProfileRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * プロフィール画面を表示する
     */
    public function show(): View
    {
        $user = auth()->user();
        $user->load('businessProfile');

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

    /**
     * ログインユーザーの事業者プロフィールを保存します。
     */
    public function updateBusinessProfile(
        UpdateBusinessProfileRequest $request
    ): RedirectResponse {
        $user = $request->user();

        /*
         * 初回保存時は新規作成し、
         * 既に存在する場合は同じレコードを更新します。
         *
         * リレーション経由で処理するため、
         * user_idをリクエストから受け取りません。
         */
        $user->businessProfile()->updateOrCreate(
            [],
            $request->validated()
        );

        return redirect()
            ->route('profile.show')
            ->with(
                'status',
                'business-profile-updated'
            );
    }
}
