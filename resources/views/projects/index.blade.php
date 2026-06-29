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
            <div>
                <p class="section-label">Projects</p>
                <h1 class="section-title">案件一覧</h1>
                <p class="section-description">
                    案件の進行状況、クライアント、納期、関連情報を一覧で確認できます。
                </p>
            </div>

            <a href="{{ route('projects.create') }}" class="button button--primary">
                ＋ 新規案件作成
            </a>
        </div>

        <div class="project-filter">
            <div class="project-filter__field project-filter__field--keyword">
                <label for="keyword">キーワード検索</label>
                <input id="keyword" type="text" name="keyword" placeholder="案件名・クライアント名で検索" disabled>
            </div>

            <div class="project-filter__field">
                <label for="category">カテゴリ</label>
                <select id="category" name="category" disabled>
                    <option>すべて</option>
                    <option>Web制作</option>
                    <option>システム開発</option>
                    <option>デザイン</option>
                </select>
            </div>

            <div class="project-filter__field">
                <label for="status">ステータス</label>
                <select id="status" name="status" disabled>
                    <option>すべて</option>
                    <option>未着手</option>
                    <option>進行中</option>
                    <option>完了</option>
                    <option>保留</option>
                </select>
            </div>

            <div class="project-filter__field">
                <label for="priority">優先度</label>
                <select id="priority" name="priority" disabled>
                    <option>すべて</option>
                    <option>高</option>
                    <option>中</option>
                    <option>低</option>
                </select>
            </div>

            <div class="project-filter__field">
                <label for="sort">並び替え</label>
                <select id="sort" name="sort" disabled>
                    <option>納期が近い順</option>
                    <option>新しい順</option>
                    <option>予算が高い順</option>
                </select>
            </div>

            <button type="button" class="button button--dark" disabled>
                検索
            </button>
        </div>

        <div class="project-list-head">
            <p class="project-count">
                全 {{ $projects->count() }} 件
            </p>
            <p class="project-note">
                PCではテーブル、スマホではカード表示に切り替わります。
            </p>
        </div>

        @if ($projects->isEmpty())
            <div class="empty-card">
                <h2 class="empty-card__title">まだ案件が登録されていません</h2>
                <p class="empty-card__text">
                    最初の案件を登録して、クライアントワークの管理を始めましょう。
                </p>

                <a href="{{ route('projects.create') }}" class="button button--primary">
                    案件を登録する
                </a>
            </div>
        @else
            @php
                $statusLabels = [
                    'not_started' => '未着手',
                    'in_progress' => '進行中',
                    'completed' => '完了',
                    'paused' => '保留',
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
                                    ? \Illuminate\Support\Carbon::parse($project->deadline)->format('Y/m/d')
                                    : '未設定';

                                $priorityLabel = match ($project->status) {
                                    'completed' => '低',
                                    'paused' => '中',
                                    default => '高',
                                };

                                $priorityClass = match ($priorityLabel) {
                                    '高' => 'high',
                                    '中' => 'middle',
                                    default => 'low',
                                };
                            @endphp

                            <tr>
                                <td>
                                    <div class="project-name">
                                        <span class="project-name__icon"></span>
                                        <div>
                                            <strong>{{ $project->title }}</strong>
                                            <p>WEB-{{ str_pad($project->id, 4, '0', STR_PAD_LEFT) }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="project-client-name">
                                        {{ $project->client_name }}
                                    </span>
                                </td>

                                <td>
                                    <span class="status-badge status-badge--{{ $project->status }}">
                                        {{ $statusLabels[$project->status] ?? $project->status }}
                                    </span>
                                </td>

                                <td>
                                    <span class="priority-badge priority-badge--{{ $priorityClass }}">
                                        {{ $priorityLabel }}
                                    </span>
                                </td>

                                <td>
                                    <strong class="project-deadline">
                                        {{ $deadlineText }}
                                    </strong>
                                </td>

                                <td>
                                    <span class="project-category">
                                        Web制作
                                    </span>
                                </td>

                                <td>
                                    <div class="project-tags-mini">
                                        <span>Web</span>
                                        <span>Laravel</span>
                                    </div>
                                </td>

                                <td>
                                    <span class="project-task-count">
                                        0 / 0
                                    </span>
                                </td>

                                <td>
                                    <div class="project-table-actions">
                                        <a href="{{ route('projects.show', $project) }}" class="table-action-link">
                                            詳細
                                        </a>

                                        <a href="{{ route('projects.edit', $project) }}" class="table-action-link">
                                            編集
                                        </a>

                                        <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                            class="project-inline-delete-form"
                                            onsubmit="return confirm('この案件を削除しますか？この操作は取り消せません。');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="table-action-link table-action-link--danger">
                                                削除
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="project-pagination">
                <div class="project-pagination__buttons" aria-label="ページネーション">
                    <span class="project-pagination__button project-pagination__button--disabled">←</span>
                    <span class="project-pagination__button project-pagination__button--current">1</span>
                    <span class="project-pagination__button project-pagination__button--disabled">→</span>
                </div>

                <p class="project-pagination__text">
                    1 - {{ $projects->count() }} / {{ $projects->count() }} 件
                </p>
            </div>

            <div class="project-mobile-list">
                @foreach ($projects as $project)
                    @php
                        $deadlineText = $project->deadline
                            ? \Illuminate\Support\Carbon::parse($project->deadline)->format('Y/m/d')
                            : '未設定';

                        $priorityLabel = match ($project->status) {
                            'completed' => '低',
                            'paused' => '中',
                            default => '高',
                        };

                        $priorityClass = match ($priorityLabel) {
                            '高' => 'high',
                            '中' => 'middle',
                            default => 'low',
                        };
                    @endphp

                    <article class="project-card">
                        <div class="project-card__summary">
                            <span class="project-name__icon project-card__icon"></span>

                            <div class="project-card__body">
                                <div class="project-card__header">
                                    <div>
                                        <h2 class="project-card__title">
                                            {{ $project->title }}
                                        </h2>
                                        <p class="project-card__client">
                                            {{ $project->client_name }}
                                        </p>
                                    </div>

                                    <a href="{{ route('projects.show', $project) }}" class="project-card__arrow" aria-label="案件詳細へ">
                                        ›
                                    </a>
                                </div>

                                <div class="project-card__badges">
                                    <span class="status-badge status-badge--{{ $project->status }}">
                                        {{ $statusLabels[$project->status] ?? $project->status }}
                                    </span>

                                    <span class="priority-badge priority-badge--{{ $priorityClass }}">
                                        {{ $priorityLabel }}
                                    </span>
                                </div>

                                <dl class="project-card__meta">
                                    <div>
                                        <dt>納期</dt>
                                        <dd>{{ $deadlineText }}</dd>
                                    </div>

                                    <div>
                                        <dt>タスク</dt>
                                        <dd>0 / 0</dd>
                                    </div>
                                </dl>

                                <div class="project-tags-mini">
                                    <span>Web</span>
                                    <span>Laravel</span>
                                </div>
                            </div>
                        </div>

                        <div class="project-card__actions">
                            <a href="{{ route('projects.show', $project) }}" class="button button--secondary">
                                詳細
                            </a>

                            <a href="{{ route('projects.edit', $project) }}" class="button button--primary">
                                編集
                            </a>

                            <form action="{{ route('projects.destroy', $project) }}" method="POST" class="project-card-delete-form"
                                onsubmit="return confirm('この案件を削除しますか？この操作は取り消せません。');">
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
        @endif
    </section>
@endsection