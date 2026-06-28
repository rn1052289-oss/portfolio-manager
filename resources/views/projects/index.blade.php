@extends('layouts.app')

@section('title', '案件一覧 | ClientWork Manager')

@section('content')
    <section class="page-section">
        <div class="page-section__header">
            <div>
                <p class="section-label">Projects</p>
                <h1 class="section-title">案件一覧</h1>
                <p class="section-description">
                    案件の進行状況、クライアント、予算、納期を一覧で確認できます。
                </p>
            </div>

            <a href="{{ route('projects.create') }}" class="button button--primary">
                + 新規案件を追加
            </a>
        </div>

        <div class="project-filter">
            <div class="project-filter__field project-filter__field--keyword">
                <label for="keyword">キーワード検索</label>
                <input id="keyword" type="text" name="keyword" placeholder="案件名・クライアント名で検索" disabled>
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
                <label for="sort">並び替え</label>
                <select id="sort" name="sort" disabled>
                    <option>新しい順</option>
                    <option>納期が近い順</option>
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
                            <th>予算</th>
                            <th>開始日</th>
                            <th>納期</th>
                            <th>操作</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($projects as $project)
                            <tr>
                                <td>
                                    <div class="project-name">
                                        <span class="project-name__icon"></span>
                                        <div>
                                            <strong>{{ $project->title }}</strong>
                                            <p>{{ \Illuminate\Support\Str::limit($project->description, 42) }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td>{{ $project->client_name }}</td>

                                <td>
                                    <span class="status-badge status-badge--{{ $project->status }}">
                                        {{ $statusLabels[$project->status] ?? $project->status }}
                                    </span>
                                </td>

                                <td>
                                    @if ($project->budget)
                                        ¥{{ number_format($project->budget) }}
                                    @else
                                        未設定
                                    @endif
                                </td>

                                <td>{{ $project->start_date ?? '未設定' }}</td>

                                <td>
                                    <strong>{{ $project->deadline ?? '未設定' }}</strong>
                                </td>

                                <td>
                                    <button type="button" class="table-action" aria-label="案件操作">
                                        ⋮
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="project-mobile-list">
                @foreach ($projects as $project)
                    <article class="project-card">
                        <div class="project-card__header">
                            <div>
                                <h2 class="project-card__title">
                                    {{ $project->title }}
                                </h2>
                                <p class="project-card__client">
                                    {{ $project->client_name }}
                                </p>
                            </div>

                            <span class="status-badge status-badge--{{ $project->status }}">
                                {{ $statusLabels[$project->status] ?? $project->status }}
                            </span>
                        </div>

                        @if ($project->description)
                            <p class="project-card__description">
                                {{ \Illuminate\Support\Str::limit($project->description, 80) }}
                            </p>
                        @endif

                        <dl class="project-card__meta">
                            <div>
                                <dt>予算</dt>
                                <dd>
                                    @if ($project->budget)
                                        ¥{{ number_format($project->budget) }}
                                    @else
                                        未設定
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt>開始日</dt>
                                <dd>{{ $project->start_date ?? '未設定' }}</dd>
                            </div>

                            <div>
                                <dt>納期</dt>
                                <dd>{{ $project->deadline ?? '未設定' }}</dd>
                            </div>
                        </dl>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection