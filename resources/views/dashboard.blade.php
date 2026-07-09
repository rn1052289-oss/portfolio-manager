@extends('layouts.app')

@section('title', 'ダッシュボード | ClientWork Manager')

@section('page-label', 'Dashboard')

@section('page-title', 'ダッシュボード')

@section('page-description', '案件の進行状況・売上見込み・未完了タスクをまとめて確認できます。')

@section('page-actions')
    <a
        href="{{ route('projects.create') }}"
        class="c-button c-button--primary"
    >
        新規案件を追加
    </a>
@endsection

@section('content')
    <div class="dashboard">

        {{-- ========================================
            サマリーカード
        ========================================= --}}
        <section
            class="dashboard-summary"
            aria-label="案件サマリー"
        >
            {{-- 進行中案件 --}}
            <article class="dashboard-summary-card">
                <div
                    class="
                        dashboard-summary-card__icon
                        dashboard-summary-card__icon--blue
                    "
                    aria-hidden="true"
                >
                    案
                </div>

                <div class="dashboard-summary-card__body">
                    <p class="dashboard-summary-card__label">
                        進行中案件
                    </p>

                    <p class="dashboard-summary-card__value">
                        {{ number_format($inProgressProjectsCount) }}件
                    </p>
                </div>

                <p class="dashboard-summary-card__note">
                    全案件のうち
                    {{ number_format($inProgressProjectsRate) }}%
                </p>
            </article>

            {{-- 今月の売上見込み --}}
            <article class="dashboard-summary-card">
                <div
                    class="
                        dashboard-summary-card__icon
                        dashboard-summary-card__icon--green
                    "
                    aria-hidden="true"
                >
                    売
                </div>

                <div class="dashboard-summary-card__body">
                    <p class="dashboard-summary-card__label">
                        今月の売上見込み
                    </p>

                    <p class="dashboard-summary-card__value">
                        ¥{{ number_format($currentMonthExpectedRevenue) }}
                    </p>
                </div>

                <p class="dashboard-summary-card__note">
                    @if (is_null($revenueComparisonRate))
                        先月比 算出不可
                    @elseif ($revenueComparisonRate > 0)
                        先月比
                        +{{ number_format($revenueComparisonRate, 1) }}%
                    @elseif ($revenueComparisonRate < 0)
                        先月比
                        {{ number_format($revenueComparisonRate, 1) }}%
                    @else
                        先月比 0%
                    @endif
                </p>
            </article>

            {{-- 未完了タスク --}}
            <article class="dashboard-summary-card">
                <div
                    class="
                        dashboard-summary-card__icon
                        dashboard-summary-card__icon--yellow
                    "
                    aria-hidden="true"
                >
                    未
                </div>

                <div class="dashboard-summary-card__body">
                    <p class="dashboard-summary-card__label">
                        未完了タスク
                    </p>

                    <p class="dashboard-summary-card__value">
                        {{ number_format($incompleteTasksCount) }}件
                    </p>
                </div>

                <p class="dashboard-summary-card__note">
                    本日締切
                    {{ number_format($todayDueTasksCount) }}件
                </p>
            </article>

            {{-- 総案件数 --}}
            <article class="dashboard-summary-card">
                <div
                    class="
                        dashboard-summary-card__icon
                        dashboard-summary-card__icon--purple
                    "
                    aria-hidden="true"
                >
                    総
                </div>

                <div class="dashboard-summary-card__body">
                    <p class="dashboard-summary-card__label">
                        総案件数
                    </p>

                    <p class="dashboard-summary-card__value">
                        {{ number_format($totalProjectsCount) }}件
                    </p>
                </div>

                <p class="dashboard-summary-card__note">
                    今月追加
                    {{ number_format($currentMonthProjectsCount) }}件
                </p>
            </article>
        </section>

        {{-- ========================================
            メインコンテンツ
        ========================================= --}}
        <div class="dashboard-grid">

            {{-- ========================================
                案件の進行状況
            ========================================= --}}
            <section class="c-card c-card--padding dashboard-panel">
                <div class="dashboard-panel__head">
                    <div>
                        <p class="dashboard-panel__label">
                            Project Progress
                        </p>

                        <h2 class="dashboard-panel__title">
                            案件の進行状況
                        </h2>
                    </div>

                    <span class="c-badge c-badge--blue">
                        更新順
                    </span>
                </div>

                @if ($progressProjects->isEmpty())
                    <div class="dashboard-empty">
                        <p class="dashboard-empty__title">
                            案件がまだありません
                        </p>

                        <p class="dashboard-empty__text">
                            案件を登録すると、タスク完了率をもとに進捗状況が表示されます。
                        </p>

                        <a
                            href="{{ route('projects.create') }}"
                            class="dashboard-empty__link"
                        >
                            最初の案件を登録する
                        </a>
                    </div>
                @else
                    <div class="dashboard-project-list">
                        @foreach ($progressProjects as $project)
                            <article class="dashboard-project">
                                <div class="dashboard-project__head">
                                    <div class="dashboard-project__identity">
                                        <h3 class="dashboard-project__title">
                                            <a
                                                href="{{ route('projects.show', $project) }}"
                                                class="dashboard-project__title-link"
                                            >
                                                {{ $project->title }}
                                            </a>
                                        </h3>

                                        <p class="dashboard-project__client">
                                            {{ $project->client_name }}
                                        </p>
                                    </div>

                                    @switch($project->status)
                                        @case('not_started')
                                            <span class="c-badge c-badge--gray">
                                                未着手
                                            </span>
                                            @break

                                        @case('in_progress')
                                            <span class="c-badge c-badge--blue">
                                                進行中
                                            </span>
                                            @break

                                        @case('completed')
                                            <span class="c-badge c-badge--green">
                                                完了
                                            </span>
                                            @break

                                        @case('paused')
                                            <span class="c-badge c-badge--yellow">
                                                保留中
                                            </span>
                                            @break

                                        @default
                                            <span class="c-badge c-badge--gray">
                                                未設定
                                            </span>
                                    @endswitch
                                </div>

                                <div class="dashboard-project__meta">
                                    <span class="dashboard-project__meta-item">
                                        @if ((int) $project->tasks_count > 0)
                                            {{ number_format($project->completed_tasks_count) }}
                                            /
                                            {{ number_format($project->tasks_count) }}
                                            タスク完了
                                        @else
                                            タスク未登録
                                        @endif
                                    </span>

                                    @if ($project->deadline)
                                        <span class="dashboard-project__meta-item">
                                            期限
                                            {{ $project->deadline->format('Y/m/d') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="dashboard-progress">
                                    <div
                                        class="dashboard-progress__bar"
                                        role="progressbar"
                                        aria-label="{{ $project->title }}のタスク進捗率"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-valuenow="{{ $project->task_progress_rate }}"
                                    >
                                        <span
                                            class="dashboard-progress__fill"
                                            style="--progress: {{ $project->task_progress_rate }}%;"
                                        ></span>
                                    </div>

                                    <p class="dashboard-progress__text">
                                        {{ number_format($project->task_progress_rate) }}%
                                    </p>
                                </div>

                                <div class="dashboard-project__actions">
                                    <a
                                        href="{{ route('projects.show', $project) }}"
                                        class="dashboard-project__action-link"
                                    >
                                        案件詳細
                                    </a>

                                    <a
                                        href="{{ route('projects.tasks.index', $project) }}"
                                        class="
                                            dashboard-project__action-link
                                            dashboard-project__action-link--primary
                                        "
                                    >
                                        タスク管理
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ========================================
                今日やること
            ========================================= --}}
            <section class="c-card c-card--padding dashboard-panel">
                <div class="dashboard-panel__head">
                    <div>
                        <p class="dashboard-panel__label">
                            Today Tasks
                        </p>

                        <h2 class="dashboard-panel__title">
                            今日やること
                        </h2>
                    </div>

                    <span class="c-badge c-badge--green">
                        {{ number_format($todayDueTasksCount) }}件
                    </span>
                </div>

                @if ($todayDueTasks->isEmpty())
                    <div class="dashboard-empty">
                        <p class="dashboard-empty__title">
                            本日締切のタスクはありません
                        </p>

                        <p class="dashboard-empty__text">
                            今日が期限の未完了タスクがあると、ここに自動表示されます。
                        </p>

                        <a
                            href="{{ route('projects.index') }}"
                            class="dashboard-empty__link"
                        >
                            案件一覧を見る
                        </a>
                    </div>
                @else
                    <div class="dashboard-task-list">
                        @foreach ($todayDueTasks as $task)
                            <a
                                href="{{ route('projects.tasks.edit', [
                                    $task->project,
                                    $task,
                                ]) }}"
                                class="dashboard-task"
                                aria-label="{{ $task->title }}を編集"
                            >
                                <span
                                    class="
                                        dashboard-task__priority
                                        dashboard-task__priority--{{ $task->priority }}
                                    "
                                    aria-hidden="true"
                                ></span>

                                <span class="dashboard-task__body">
                                    <span class="dashboard-task__head">
                                        <span class="dashboard-task__title">
                                            {{ $task->title }}
                                        </span>

                                        @switch($task->status)
                                            @case('not_started')
                                                <span class="dashboard-task__status dashboard-task__status--gray">
                                                    未着手
                                                </span>
                                                @break

                                            @case('in_progress')
                                                <span class="dashboard-task__status dashboard-task__status--blue">
                                                    進行中
                                                </span>
                                                @break
                                        @endswitch
                                    </span>

                                    <span class="dashboard-task__meta">
                                        <span class="dashboard-task__meta-item">
                                            {{ $task->project->title }}
                                        </span>

                                        <span class="dashboard-task__meta-item">
                                            優先度
                                            @switch($task->priority)
                                                @case('high')
                                                    高
                                                    @break

                                                @case('medium')
                                                    中
                                                    @break

                                                @case('low')
                                                    低
                                                    @break

                                                @default
                                                    未設定
                                            @endswitch
                                        </span>
                                    </span>
                                </span>

                                <span class="dashboard-task__edit">
                                    編集
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection