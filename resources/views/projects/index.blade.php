@extends('layouts.app')

@section('title', '案件一覧 | ClientWork Manager')

@section('content')
    <section class="page-section project-index-page">
        @if (session('success'))
            <div class="flash-message flash-message--success">
                {{ session('success') }}
            </div>
        @endif

        <div class="page-section__header">
            <div class="project-page-heading">
                <p class="section-label">
                    Projects
                </p>

                <h1 class="section-title">
                    案件一覧
                </h1>

                <p class="section-description">
                    案件の進行状況、クライアント、納期、関連情報を一覧で確認できます。
                </p>
            </div>

            <div class="project-header-actions">
                <a href="{{ route('projects.export', request()->only([
        'keyword',
        'category_id',
        'tag_id',
        'status',
        'sort',
    ])) }}" class="button button--secondary">
                    CSVダウンロード
                </a>

                <a href="{{ route('projects.create') }}" class="button button--primary">
                    ＋ 新規案件作成
                </a>
            </div>
        </div>

        @php
            $selectedCategoryId = request('category_id');
            $selectedTagId = request('tag_id');
            $selectedStatus = request('status');
            $selectedSort = request('sort', 'latest');

            $hasActiveFilters = request()->filled('keyword')
                || request()->filled('category_id')
                || request()->filled('tag_id')
                || request()->filled('status')
                || request('sort', 'latest') !== 'latest';
        @endphp

        <form method="GET" action="{{ route('projects.index') }}" class="project-filter">
            <div class="project-filter__field project-filter__field--keyword">
                <label for="keyword">
                    キーワード検索
                </label>

                <input id="keyword" type="text" name="keyword" value="{{ request('keyword') }}"
                    placeholder="案件名・クライアント名で検索">
            </div>

            <div class="project-filter__field project-filter__field--category">
                <label for="category_id">
                    カテゴリ
                </label>

                <select id="category_id" name="category_id">
                    <option value="">
                        すべて
                    </option>

                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(
                            (string) $selectedCategoryId === (string) $category->id
                        )>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="project-filter__field project-filter__field--tag">
                <label for="tag_id">
                    タグ
                </label>

                <select id="tag_id" name="tag_id">
                    <option value="">
                        すべて
                    </option>

                    @foreach ($tags as $tag)
                        <option value="{{ $tag->id }}" @selected(
                            (string) $selectedTagId === (string) $tag->id
                        )>
                            {{ $tag->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="project-filter__field project-filter__field--status">
                <label for="status">
                    ステータス
                </label>

                <select id="status" name="status">
                    <option value="">
                        すべて
                    </option>

                    <option value="not_started" @selected($selectedStatus === 'not_started')>
                        未着手
                    </option>

                    <option value="in_progress" @selected($selectedStatus === 'in_progress')>
                        進行中
                    </option>

                    <option value="completed" @selected($selectedStatus === 'completed')>
                        完了
                    </option>

                    <option value="paused" @selected($selectedStatus === 'paused')>
                        保留
                    </option>
                </select>
            </div>

            <div class="project-filter__field project-filter__field--sort">
                <label for="sort">
                    並び替え
                </label>

                <select id="sort" name="sort">
                    <option value="latest" @selected($selectedSort === 'latest')>
                        新しい順
                    </option>

                    <option value="deadline_asc" @selected($selectedSort === 'deadline_asc')>
                        納期が近い順
                    </option>

                    <option value="deadline_desc" @selected($selectedSort === 'deadline_desc')>
                        納期が遠い順
                    </option>

                    <option value="oldest" @selected($selectedSort === 'oldest')>
                        古い順
                    </option>

                    <option value="budget_desc" @selected($selectedSort === 'budget_desc')>
                        予算が高い順
                    </option>

                    <option value="budget_asc" @selected($selectedSort === 'budget_asc')>
                        予算が低い順
                    </option>
                </select>
            </div>

            <div class="project-filter__actions">
                <button type="submit" class="button button--dark">
                    検索
                </button>

                <a href="{{ route('projects.index') }}" class="button button--secondary">
                    リセット
                </a>
            </div>
        </form>

        <div class="project-list-head">
            <p class="project-count">
                全 {{ $projects->total() }} 件
            </p>

            <p class="project-note">
                PCではテーブル、スマホではカード表示に切り替わります。
            </p>
        </div>

        @if ($projects->isEmpty())
            <div class="empty-card">
                @if ($hasActiveFilters)
                    <h2 class="empty-card__title">
                        条件に一致する案件が見つかりません
                    </h2>

                    <p class="empty-card__text">
                        キーワード・カテゴリ・タグ・ステータスの条件を変更するか、検索条件をリセットしてください。
                    </p>

                    <a href="{{ route('projects.index') }}" class="button button--primary">
                        検索条件をリセットする
                    </a>
                @else
                    <h2 class="empty-card__title">
                        まだ案件が登録されていません
                    </h2>

                    <p class="empty-card__text">
                        最初の案件を登録して、クライアントワークの管理を始めましょう。
                    </p>

                    <a href="{{ route('projects.create') }}" class="button button--primary">
                        案件を登録する
                    </a>
                @endif
            </div>
        @else
            @php
                $statusLabels = [
                    'not_started' => '未着手',
                    'in_progress' => '進行中',
                    'completed' => '完了',
                    'paused' => '保留',
                ];

                $priorityLabels = [
                    'high' => '高',
                    'medium' => '中',
                    'low' => '低',
                ];
            @endphp

            <div class="project-table-wrap">
                <table class="project-table">
                    <thead>
                        <tr>
                            <th>案件名</th>
                            <th>クライアント名</th>
                            <th>ステータス</th>
                            <th>優先度</th>
                            <th>納期</th>
                            <th>カテゴリ</th>
                            <th>タグ</th>
                            <th>タスク数</th>
                            <th>操作</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($projects as $project)
                                    @php
                                        $deadlineText = $project->deadline
                                            ? \Illuminate\Support\Carbon::parse(
                                                $project->deadline
                                            )->format('Y/m/d')
                                            : '未設定';

                                        $statusLabel = $statusLabels[$project->status]
                                            ?? $project->status;

                                        $priorityLabel = $priorityLabels[$project->priority]
                                            ?? $project->priority;
                                    @endphp

                                    <tr>
                                        <td>
                                            <div class="project-name">
                                                <x-projects.cover-image :project="$project" variant="table" />

                                                <div class="project-name__body">
                                                    <a href="{{ route('projects.show', $project) }}" class="project-name__link">
                                                        {{ $project->title }}
                                                    </a>

                                                    <p class="project-name__code">
                                                        WEB-{{ str_pad(
                                $project->id,
                                4,
                                '0',
                                STR_PAD_LEFT
                            ) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="project-client-name">
                                                {{ $project->client_name }}
                                            </span>
                                        </td>

                                        <td>
                                            <span class="project-status project-status--{{ $project->status }}">
                                                <span class="project-status__dot" aria-hidden="true"></span>

                                                <span class="project-status__label">
                                                    {{ $statusLabel }}
                                                </span>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="project-priority project-priority--{{ $project->priority }}">
                                                <span class="project-priority__marker" aria-hidden="true"></span>

                                                <span class="project-priority__label">
                                                    {{ $priorityLabel }}
                                                </span>
                                            </span>
                                        </td>

                                        <td>
                                            <strong class="project-deadline">
                                                {{ $deadlineText }}
                                            </strong>
                                        </td>

                                        <td>
                                            <span class="project-category">
                                                {{ $project->category?->name ?? '未分類' }}
                                            </span>
                                        </td>

                                        <td>
                                            <div class="project-tags-mini">
                                                @forelse ($project->tags as $tag)
                                                    <span>
                                                        {{ $tag->name }}
                                                    </span>
                                                @empty
                                                    <span class="project-tags-mini__empty">
                                                        未設定
                                                    </span>
                                                @endforelse
                                            </div>
                                        </td>

                                        <td>
                                            <a href="{{ route(
                                'projects.tasks.index',
                                $project
                            ) }}" class="project-task-count-link"
                                                aria-label="{{ $project->title }}のタスク管理を開く">
                                                {{ $project->tasks_count }}件
                                            </a>
                                        </td>

                                        <td class="project-table__action-cell">
                                            <div class="project-action-menu" data-project-action-menu>
                                                <button type="button" class="project-action-menu__trigger"
                                                    aria-label="{{ $project->title }}の操作メニューを開く" aria-haspopup="true" aria-expanded="false"
                                                    aria-controls="project-actions-{{ $project->id }}" data-project-action-trigger>
                                                    <svg class="project-action-menu__icon" viewBox="0 0 24 24" fill="currentColor"
                                                        aria-hidden="true">
                                                        <circle cx="12" cy="5" r="1.8"></circle>

                                                        <circle cx="12" cy="12" r="1.8"></circle>

                                                        <circle cx="12" cy="19" r="1.8"></circle>
                                                    </svg>
                                                </button>

                                                <div id="project-actions-{{ $project->id }}" class="project-action-menu__content"
                                                    data-project-action-content hidden>
                                                    <a href="{{ route(
                                'projects.show',
                                $project
                            ) }}" class="project-action-menu__link">
                                                        詳細を見る
                                                    </a>

                                                    <a href="{{ route(
                                'projects.tasks.index',
                                $project
                            ) }}" class="project-action-menu__link">
                                                        タスク管理
                                                    </a>

                                                    <a href="{{ route(
                                'projects.edit',
                                $project
                            ) }}" class="project-action-menu__link">
                                                        編集する
                                                    </a>

                                                    <div class="project-action-menu__divider"></div>

                                                    <form action="{{ route(
                                'projects.destroy',
                                $project
                            ) }}" method="POST" class="project-action-menu__form" onsubmit="return confirm(
                                                                        'この案件を削除しますか？この操作は取り消せません。'
                                                                    );">
                                                        @csrf

                                                        @method('DELETE')

                                                        <button type="submit"
                                                            class="project-action-menu__button project-action-menu__button--danger">
                                                            削除する
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="project-mobile-list">
                @foreach ($projects as $project)
                    @php
                        $deadlineText = $project->deadline
                            ? \Illuminate\Support\Carbon::parse(
                                $project->deadline
                            )->format('Y/m/d')
                            : '未設定';

                        $statusLabel = $statusLabels[$project->status]
                            ?? $project->status;

                        $priorityLabel = $priorityLabels[$project->priority]
                            ?? $project->priority;
                    @endphp

                    <article class="project-card">
                        <div class="project-card__summary">
                            <x-projects.cover-image :project="$project" variant="card" />

                            <div class="project-card__body">
                                <div class="project-card__header">
                                    <div>
                                        <h2 class="project-card__title">
                                            <a href="{{ route(
                        'projects.show',
                        $project
                    ) }}" class="project-card__title-link">
                                                {{ $project->title }}
                                            </a>
                                        </h2>

                                        <p class="project-card__client">
                                            {{ $project->client_name }}
                                        </p>
                                    </div>

                                    <a href="{{ route(
                        'projects.show',
                        $project
                    ) }}" class="project-card__arrow" aria-label="{{ $project->title }}の詳細を見る">
                                        ›
                                    </a>
                                </div>

                                <div class="project-card__status-row">
                                    <span class="project-status project-status--{{ $project->status }}">
                                        <span class="project-status__dot" aria-hidden="true"></span>

                                        <span class="project-status__label">
                                            {{ $statusLabel }}
                                        </span>
                                    </span>

                                    <span class="project-priority project-priority--{{ $project->priority }}">
                                        <span class="project-priority__marker" aria-hidden="true"></span>

                                        <span class="project-priority__label">
                                            優先度 {{ $priorityLabel }}
                                        </span>
                                    </span>
                                </div>

                                <dl class="project-card__meta">
                                    <div>
                                        <dt>納期</dt>

                                        <dd>
                                            {{ $deadlineText }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>カテゴリ</dt>

                                        <dd>
                                            {{ $project->category?->name ?? '未分類' }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt>タスク</dt>

                                        <dd>
                                            <a href="{{ route(
                        'projects.tasks.index',
                        $project
                    ) }}" class="project-task-count-link"
                                                aria-label="{{ $project->title }}のタスク管理を開く">
                                                {{ $project->tasks_count }}件
                                            </a>
                                        </dd>
                                    </div>
                                </dl>

                                <div class="project-tags-mini">
                                    @forelse ($project->tags as $tag)
                                        <span>
                                            {{ $tag->name }}
                                        </span>
                                    @empty
                                        <span class="project-tags-mini__empty">
                                            未設定
                                        </span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="project-card__actions">
                            <a href="{{ route(
                        'projects.show',
                        $project
                    ) }}" class="button button--secondary">
                                詳細
                            </a>

                            <a href="{{ route(
                        'projects.edit',
                        $project
                    ) }}" class="button button--secondary">
                                編集
                            </a>

                            <form action="{{ route(
                        'projects.destroy',
                        $project
                    ) }}" method="POST" class="project-card-delete-form" onsubmit="return confirm(
                                                'この案件を削除しますか？この操作は取り消せません。'
                                            );">
                                @csrf

                                @method('DELETE')

                                <button type="submit" class="button button--danger">
                                    削除
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($projects->hasPages())
                <div class="project-pagination">
                    <div class="project-pagination__buttons" aria-label="ページネーション">
                        @if ($projects->onFirstPage())
                            <span class="project-pagination__button project-pagination__button--disabled">
                                ←
                            </span>
                        @else
                            <a href="{{ $projects->previousPageUrl() }}" class="project-pagination__button">
                                ←
                            </a>
                        @endif

                        @foreach (
                                $projects->getUrlRange(
                                    1,
                                    $projects->lastPage()
                                ) as $page => $url
                            )
                            @if ($page === $projects->currentPage())
                                <span class="project-pagination__button project-pagination__button--current">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="project-pagination__button">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach

                        @if ($projects->hasMorePages())
                            <a href="{{ $projects->nextPageUrl() }}" class="project-pagination__button">
                                →
                            </a>
                        @else
                            <span class="project-pagination__button project-pagination__button--disabled">
                                →
                            </span>
                        @endif
                    </div>

                    <p class="project-pagination__text">
                        {{ $projects->firstItem() }}
                        -
                        {{ $projects->lastItem() }}
                        /
                        {{ $projects->total() }}
                        件
                    </p>
                </div>
            @endif
        @endif
    </section>
@endsection