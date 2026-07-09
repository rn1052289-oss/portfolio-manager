<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * ダッシュボードを表示する
     */
    public function index(): View
    {
        $user = auth()->user();
        $today = today();

        /*
        |--------------------------------------------------------------------------
        | 案件集計
        |--------------------------------------------------------------------------
        */

        // ログインユーザー本人の総案件数
        $totalProjectsCount = $user
            ->projects()
            ->count();

        // 進行中案件数
        $inProgressProjectsCount = $user
            ->projects()
            ->where('status', 'in_progress')
            ->count();

        // 総案件数に占める進行中案件の割合
        $inProgressProjectsRate = $totalProjectsCount > 0
            ? (int) round(
                ($inProgressProjectsCount / $totalProjectsCount) * 100
            )
            : 0;

        // 今月追加された案件数
        $currentMonthProjectsCount = $user
            ->projects()
            ->whereBetween('created_at', [
                $today->copy()->startOfMonth()->startOfDay(),
                $today->copy()->endOfMonth()->endOfDay(),
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 売上見込み集計
        |--------------------------------------------------------------------------
        */

        // 今月の開始日・終了日
        $currentMonthStart = $today
            ->copy()
            ->startOfMonth()
            ->toDateString();

        $currentMonthEnd = $today
            ->copy()
            ->endOfMonth()
            ->toDateString();

        // 先月の開始日・終了日
        $previousMonthStart = $today
            ->copy()
            ->subMonthNoOverflow()
            ->startOfMonth()
            ->toDateString();

        $previousMonthEnd = $today
            ->copy()
            ->subMonthNoOverflow()
            ->endOfMonth()
            ->toDateString();

        // 今月締切の案件予算合計
        // 保留中案件は除外
        $currentMonthExpectedRevenue = $user
            ->projects()
            ->whereBetween('deadline', [
                $currentMonthStart,
                $currentMonthEnd,
            ])
            ->where('status', '!=', 'paused')
            ->sum('budget');

        // 先月締切の案件予算合計
        // 今月と同じ条件で比較する
        $previousMonthExpectedRevenue = $user
            ->projects()
            ->whereBetween('deadline', [
                $previousMonthStart,
                $previousMonthEnd,
            ])
            ->where('status', '!=', 'paused')
            ->sum('budget');

        // 売上見込みの先月比
        $revenueComparisonRate = match (true) {
            $previousMonthExpectedRevenue > 0 => round(
                (
                    ($currentMonthExpectedRevenue - $previousMonthExpectedRevenue)
                    / $previousMonthExpectedRevenue
                ) * 100,
                1
            ),

            $previousMonthExpectedRevenue == 0
            && $currentMonthExpectedRevenue == 0 => 0,

            default => null,
        };

        /*
        |--------------------------------------------------------------------------
        | タスク集計
        |--------------------------------------------------------------------------
        */

        // 未完了タスク数
        $incompleteTasksCount = $user
            ->tasks()
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->count();

        // 本日締切かつ未完了の全タスク数
        $todayDueTasksCount = $user
            ->tasks()
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->whereDate('due_date', $today->toDateString())
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 今日やること
        |--------------------------------------------------------------------------
        */

        // 本日締切かつ未完了の実タスクを取得
        //
        // 表示順:
        // 1. 優先度 高
        // 2. 優先度 中
        // 3. 優先度 低
        // 4. sort_order
        // 5. 登録日時
        //
        // project を eager loading して
        // Blade側のN+1問題を防ぐ
        $todayDueTasks = $user
            ->tasks()
            ->with([
                'project:id,title',
            ])
            ->where('status', '!=', Task::STATUS_COMPLETED)
            ->whereDate('due_date', $today->toDateString())
            ->orderByRaw(
                '
                    CASE priority
                        WHEN ? THEN 1
                        WHEN ? THEN 2
                        WHEN ? THEN 3
                        ELSE 4
                    END
                ',
                [
                    Task::PRIORITY_HIGH,
                    Task::PRIORITY_MEDIUM,
                    Task::PRIORITY_LOW,
                ]
            )
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 案件進捗
        |--------------------------------------------------------------------------
        */

        // 最近更新された実案件を最大3件取得
        //
        // tasks_count:
        //   案件に紐づく全タスク数
        //
        // completed_tasks_count:
        //   完了済みタスク数
        //
        // withCount() により
        // 案件ごとの個別COUNT発行を避ける
        $progressProjects = $user
            ->projects()
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => fn($query) => $query
                    ->where('status', Task::STATUS_COMPLETED),
            ])
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->each(function ($project): void {
                $tasksCount = (int) $project->tasks_count;
                $completedTasksCount = (int) $project->completed_tasks_count;

                $taskProgressRate = $tasksCount > 0
                    ? (int) round(
                        ($completedTasksCount / $tasksCount) * 100
                    )
                    : 0;

                $project->setAttribute(
                    'task_progress_rate',
                    min(100, $taskProgressRate)
                );
            });

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view('dashboard', compact(
            'totalProjectsCount',
            'inProgressProjectsCount',
            'inProgressProjectsRate',
            'currentMonthProjectsCount',
            'currentMonthExpectedRevenue',
            'revenueComparisonRate',
            'incompleteTasksCount',
            'todayDueTasksCount',
            'todayDueTasks',
            'progressProjects'
        ));
    }
}