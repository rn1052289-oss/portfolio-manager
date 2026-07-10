@extends('layouts.app')

@section('title', '案件詳細 | ClientWork Manager')

@section('content')
    @php
        $statusLabels = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'completed' => '完了',
            'paused' => '保留',
        ];

        $taskPriorityLabels = [
            'high' => '高',
            'medium' => '中',
            'low' => '低',
        ];

        $taskStatusLabels = [
            'not_started' => '未着手',
            'in_progress' => '進行中',
            'completed' => '完了',
        ];
    @endphp

    <section class="page-section project-show-page">
        <div class="project-show-layout">
            <div class="project-show-main">
                {{-- 案件基本情報 --}}
                <article class="project-hero-card">
                    <div class="project-hero-card__header">
                        <div>
                            <div class="breadcrumb">
                                <a href="{{ route('projects.index') }}">
                                    案件一覧
                                </a>

                                <span>/</span>

                                <span>
                                    案件詳細
                                </span>
                            </div>

                            <h1 class="project-hero-card__title">
                                {{ $project->title }}
                            </h1>

                            <p class="project-hero-card__client">
                                {{ $project->client_name }}
                            </p>
                        </div>

                        <div class="project-hero-card__actions">
                            <a href="{{ route('projects.edit', $project) }}" class="button button--secondary button--small">
                                編集
                            </a>

                            <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                class="project-delete-form" onsubmit="return confirm('この案件を削除しますか？この操作は取り消せません。');">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="button button--danger button--small">
                                    削除
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="project-hero-card__meta">
                        <span class="status-badge status-badge--{{ $project->status }}">
                            {{ $statusLabels[$project->status] ?? $project->status }}
                        </span>

                        <div class="project-meta-pill">
                            <span>開始日</span>

                            <strong>
                                {{ $project->start_date?->format('Y/m/d') ?? '未設定' }}
                            </strong>
                        </div>

                        <div class="project-meta-pill">
                            <span>納期</span>

                            <strong>
                                {{ $project->deadline?->format('Y/m/d') ?? '未設定' }}
                            </strong>
                        </div>

                        <div class="project-meta-pill">
                            <span>見積金額</span>

                            <strong>
                                {{ $project->budget !== null ? '¥' . number_format($project->budget) : '未設定' }}
                            </strong>
                        </div>
                    </div>
                </article>

                {{-- 案件概要 --}}
                <section class="project-panel">
                    <div class="project-panel__header">
                        <h2>
                            案件概要
                        </h2>
                    </div>

                    @if ($project->description)
                        <p class="project-description">
                            {{ $project->description }}
                        </p>
                    @else
                        <p class="project-empty-text">
                            案件概要はまだ登録されていません。
                        </p>
                    @endif
                </section>

                {{-- タスク概要 --}}
                <section class="project-panel project-task-summary">
                    <div class="project-panel__header">
                        <h2>
                            タスク概要
                        </h2>
                    </div>

                    <div class="project-task-summary__progress">
                        <div class="project-task-summary__progress-header">
                            <span class="project-task-summary__progress-label">
                                進捗率
                            </span>

                            <p class="project-task-summary__progress-value">
                                <strong>
                                    {{ $progressRate }}%
                                </strong>

                                <span>
                                    （{{ $completedTasks }}/{{ $totalTasks }}件完了）
                                </span>
                            </p>
                        </div>

                        <div class="project-progress">
                            <div class="project-progress__bar" role="progressbar" aria-label="タスク進捗率" aria-valuemin="0"
                                aria-valuemax="100" aria-valuenow="{{ $progressRate }}">
                                <span style="width: {{ $progressRate }}%;"></span>
                            </div>
                        </div>
                    </div>

                    <div class="project-task-summary__status-list">
                        <div class="project-task-summary__status-item">
                            <span class="project-task-summary__status-dot project-task-summary__status-dot--not-started"
                                aria-hidden="true"></span>

                            <span class="project-task-summary__status-label">
                                未着手
                            </span>

                            <strong>
                                {{ $notStartedTasks }}件
                            </strong>
                        </div>

                        <div class="project-task-summary__status-item">
                            <span class="project-task-summary__status-dot project-task-summary__status-dot--in-progress"
                                aria-hidden="true"></span>

                            <span class="project-task-summary__status-label">
                                進行中
                            </span>

                            <strong>
                                {{ $inProgressTasks }}件
                            </strong>
                        </div>

                        <div class="project-task-summary__status-item">
                            <span class="project-task-summary__status-dot project-task-summary__status-dot--completed"
                                aria-hidden="true"></span>

                            <span class="project-task-summary__status-label">
                                完了
                            </span>

                            <strong>
                                {{ $completedTasks }}件
                            </strong>
                        </div>
                    </div>

                    @can('update', $project)
                        <div class="project-task-summary__actions">
                            <a href="{{ route('projects.tasks.create', $project) }}"
                                class="button button--primary button--small">
                                ＋ タスクを追加
                            </a>
                        </div>
                    @endcan
                </section>

                {{-- 備考・メモ --}}
                <section class="project-panel">
                    <div class="project-panel__header">
                        <h2>
                            備考・メモ
                        </h2>

                        <span class="project-panel__badge">
                            Coming Soon
                        </span>
                    </div>

                    <p class="project-empty-text">
                        メモ機能は今後追加予定です。クライアントとのやり取りや補足情報を残せるようにします。
                    </p>
                </section>

                {{-- 関連タスク一覧 --}}
                <section class="project-panel project-related-tasks">
                    <div class="project-panel__header">
                        <h2>
                            関連タスク一覧
                        </h2>
                    </div>

                    @if ($relatedTasks->isNotEmpty())
                        <div class="project-related-tasks__table-wrap">
                            <table class="project-related-tasks__table">
                                <thead>
                                    <tr>
                                        <th>タスク名</th>
                                        <th>期限</th>
                                        <th>優先度</th>
                                        <th>ステータス</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($relatedTasks as $task)
                                        <tr>
                                            <td data-label="タスク名">
                                                <strong class="project-related-tasks__title">
                                                    {{ $task->title }}
                                                </strong>
                                            </td>

                                            <td data-label="期限">
                                                <span class="project-related-tasks__date">
                                                    {{ $task->due_date?->format('Y/m/d') ?? '未設定' }}
                                                </span>
                                            </td>

                                            <td data-label="優先度">
                                                <span class="project-task-priority project-task-priority--{{ $task->priority }}">
                                                    {{ $taskPriorityLabels[$task->priority] ?? $task->priority }}
                                                </span>
                                            </td>

                                            <td data-label="ステータス">
                                                <span
                                                    class="project-task-status project-task-status--{{ str_replace('_', '-', $task->status) }}">
                                                    {{ $taskStatusLabels[$task->status] ?? $task->status }}
                                                </span>
                                            </td>

                                            <td data-label="操作">
                                                @can('update', $task)
                                                    <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                                                        class="project-related-tasks__edit-link">
                                                        編集
                                                    </a>
                                                @else
                                                    <span class="project-related-tasks__no-action">
                                                        -
                                                    </span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="project-related-tasks__footer">
                            <a href="{{ route('projects.tasks.index', $project) }}" class="project-related-tasks__all-link">
                                すべてのタスクを見る

                                <span aria-hidden="true">
                                    →
                                </span>
                            </a>
                        </div>
                    @else
                        <div class="project-related-tasks__empty">
                            <h3 class="project-related-tasks__empty-title">
                                タスクがまだ登録されていません
                            </h3>

                            <p class="project-related-tasks__empty-text">
                                この案件に最初のタスクを追加すると、ここに表示されます。
                            </p>

                            @can('update', $project)
                                <div class="project-related-tasks__empty-actions">
                                    <a href="{{ route('projects.tasks.create', $project) }}"
                                        class="button button--primary button--small">
                                        ＋ タスクを追加
                                    </a>
                                </div>
                            @endcan
                        </div>
                    @endif
                </section>
            </div>

            {{-- 右サイド --}}
            <aside class="project-show-side">
                {{-- 案件イメージ --}}
                <section class="project-side-card">
                    <h2 class="project-side-card__title">
                        案件イメージ
                    </h2>

                    <x-projects.cover-image :project="$project" variant="detail" />

                    <p class="project-side-card__note">
                        編集画面からカバー画像を登録・変更できます。
                    </p>
                </section>

                {{-- カテゴリ --}}
                <section class="project-side-card">
                    <h2 class="project-side-card__title">
                        カテゴリ
                    </h2>

                    @if ($project->category)
                        <div class="project-tags">
                            <span>
                                {{ $project->category->name }}
                            </span>
                        </div>

                        <p class="project-side-card__note">
                            この案件に紐づいているカテゴリです。
                        </p>
                    @else
                        <p class="project-empty-text">
                            未分類
                        </p>

                        <p class="project-side-card__note">
                            編集画面からカテゴリを設定できます。
                        </p>
                    @endif
                </section>

                {{-- タグ --}}
                <section class="project-side-card">
                    <h2 class="project-side-card__title">
                        タグ
                    </h2>

                    @if ($project->tags->isNotEmpty())
                        <div class="project-tags">
                            @foreach ($project->tags as $tag)
                                <span>
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </div>

                        <p class="project-side-card__note">
                            この案件に紐づいているタグです。
                        </p>
                    @else
                        <p class="project-empty-text">
                            タグはまだ設定されていません。
                        </p>

                        <p class="project-side-card__note">
                            編集画面からタグを設定できます。
                        </p>
                    @endif
                </section>

                {{-- 主要情報 --}}
                <section class="project-side-card">
                    <h2 class="project-side-card__title">
                        主要情報
                    </h2>

                    <dl class="project-info-list">
                        <div>
                            <dt>案件ID</dt>

                            <dd>
                                #{{ $project->id }}
                            </dd>
                        </div>

                        <div>
                            <dt>作成日</dt>

                            <dd>
                                {{ $project->created_at->format('Y/m/d') }}
                            </dd>
                        </div>

                        <div>
                            <dt>最終更新日</dt>

                            <dd>
                                {{ $project->updated_at->format('Y/m/d H:i') }}
                            </dd>
                        </div>

                        <div>
                            <dt>ステータス</dt>

                            <dd>
                                {{ $statusLabels[$project->status] ?? $project->status }}
                            </dd>
                        </div>

                        <div>
                            <dt>クライアント</dt>

                            <dd>
                                {{ $project->client_name }}
                            </dd>
                        </div>
                    </dl>
                </section>

                {{-- 添付ファイル --}}
                <section class="project-side-card">
                    <h2 class="project-side-card__title">
                        添付ファイル
                    </h2>

                    <p class="project-empty-text">
                        添付ファイル機能は今後追加予定です。
                    </p>
                </section>
            </aside>
        </div>
    </section>
@endsection