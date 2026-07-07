@extends('layouts.app')

@section('title', 'タスク管理 | ClientWork Manager')

@section('page-label', 'Task Management')

@section('page-title', 'タスク管理')

@section('page-description', '案件に紐づくタスクの進捗・優先度・期限を管理します。')

@section('content')
    <section class="page-section task-index-page">
        @if (session('success'))
            <div class="task-flash task-flash--success" role="status">
                <span class="task-flash__icon" aria-hidden="true">
                    ✓
                </span>

                <p class="task-flash__message">
                    {{ session('success') }}
                </p>
            </div>
        @endif
        {{-- 関連案件 --}}
        <section class="task-project-card">
            <div class="task-project-card__content">
                <span class="task-project-card__label">
                    関連案件
                </span>

                <div class="task-project-card__body">
                    <h2 class="task-project-card__title">
                        {{ $project->title }}
                    </h2>

                    <p class="task-project-card__client">
                        {{ $project->client_name }}
                    </p>
                </div>
            </div>

            <div class="task-project-card__actions">
                <a href="{{ route('projects.show', $project) }}" class="button button--secondary">
                    案件詳細へ
                </a>

                @if (auth()->user()->can('update', $project))
                    <a href="{{ route('projects.tasks.create', $project) }}" class="task-add-button">
                        ＋ タスクを追加
                    </a>
                @endif
            </div>
        </section>

        {{-- タスク管理メインエリア --}}
        <div class="task-index-layout">
            <main class="task-index-main">
                <section class="task-list-card">
                    <div class="task-list-card__header">
                        <div>
                            <p class="section-label">
                                Task List
                            </p>

                            <h2 class="section-title">
                                タスク一覧
                            </h2>
                        </div>

                        <span class="task-count-badge">
                            {{ $totalTasks }}件
                        </span>
                    </div>

                    @if ($tasks->isNotEmpty())
                        <div class="task-table-wrap">
                            <table class="task-table">
                                <thead>
                                    <tr>
                                        <th>タスク名</th>
                                        <th>期限</th>
                                        <th>優先度</th>
                                        <th>ステータス</th>
                                        <th>メモ</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($tasks as $task)
                                        <tr>
                                            <td>
                                                <strong class="task-table__title">
                                                    {{ $task->title }}
                                                </strong>
                                            </td>

                                            <td>
                                                <span class="task-table__date">
                                                    {{ $task->due_date?->format('Y/m/d') ?? '未設定' }}
                                                </span>
                                            </td>

                                            <td>
                                                @if ($task->priority === 'high')
                                                    <span class="priority-badge priority-badge--high">
                                                        高
                                                    </span>
                                                @elseif ($task->priority === 'medium')
                                                    <span class="priority-badge priority-badge--middle">
                                                        中
                                                    </span>
                                                @else
                                                    <span class="priority-badge priority-badge--low">
                                                        低
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                @if ($task->status === 'not_started')
                                                    <span class="task-status-badge task-status-badge--not-started">
                                                        未着手
                                                    </span>
                                                @elseif ($task->status === 'in_progress')
                                                    <span class="task-status-badge task-status-badge--in-progress">
                                                        進行中
                                                    </span>
                                                @else
                                                    <span class="task-status-badge task-status-badge--completed">
                                                        完了
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                <span class="task-table__memo">
                                                    {{ $task->memo ?: '未設定' }}
                                                </span>
                                            </td>

                                            <td>
    @can('update', $task)
        <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
            class="task-edit-link">
            編集
        </a>
    @endcan
</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="task-empty">
                            <h3 class="task-empty__title">
                                タスクがまだ登録されていません
                            </h3>

                            <p class="task-empty__text">
                                「＋ タスクを追加」から最初のタスクを登録できます。
                            </p>

                            @if (auth()->user()->can('update', $project))
                                <div class="task-empty__actions">
                                    <a href="{{ route('projects.tasks.create', $project) }}" class="task-add-button">
                                        ＋ タスクを追加
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </section>
            </main>

            {{-- 進捗率 --}}
            <aside class="task-progress-card">
                <p class="section-label">
                    Progress
                </p>

                <h2 class="task-progress-card__title">
                    タスク進捗率
                </h2>

                <div class="task-progress-circle" style="--progress-rate: {{ $progressRate }}%;">
                    <strong>
                        {{ $progressRate }}%
                    </strong>

                    <span>
                        {{ $completedTasks }} / {{ $totalTasks }}
                    </span>
                </div>

                <dl class="task-progress-summary">
                    <div>
                        <dt>完了</dt>
                        <dd>{{ $completedTasks }}件</dd>
                    </div>

                    <div>
                        <dt>未完了</dt>
                        <dd>{{ $remainingTasks }}件</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </section>
@endsection