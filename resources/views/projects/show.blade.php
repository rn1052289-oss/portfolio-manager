@extends('layouts.app')

@section('title', '案件詳細 | ClientWork Manager')

@section('content')
    <section class="page-section project-show-page">
        <div class="page-section__header page-section__header--compact">
            <div>
                <p class="section-label">Project Detail</p>
                <h1 class="section-title">案件詳細</h1>
                <p class="section-description">
                    案件の基本情報、進捗、金額、納期を確認できます。
                </p>
            </div>

            <div class="project-show-actions">
                <a href="{{ route('projects.index') }}" class="button button--secondary">
                    案件一覧へ戻る
                </a>

                <a href="{{ route('projects.edit', $project) }}" class="button button--primary">
                    編集する
                </a>
            </div>
        </div>

        <div class="project-show-layout">
            <div class="project-show-main">
                <article class="project-hero-card">
                    <div class="project-hero-card__header">
                        <div>
                            <div class="breadcrumb">
                                <a href="{{ route('projects.index') }}">案件一覧</a>
                                <span>/</span>
                                <span>案件詳細</span>
                            </div>

                            <h2 class="project-hero-card__title">
                                {{ $project->title }}
                            </h2>

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
                            @if ($project->status === 'not_started')
                                未着手
                            @elseif ($project->status === 'in_progress')
                                進行中
                            @elseif ($project->status === 'completed')
                                完了
                            @elseif ($project->status === 'paused')
                                停止中
                            @endif
                        </span>

                        <div class="project-meta-pill">
                            <span>開始日</span>
                            <strong>
                                {{ $project->start_date ? $project->start_date->format('Y/m/d') : '未設定' }}
                            </strong>
                        </div>

                        <div class="project-meta-pill">
                            <span>納期</span>
                            <strong>
                                {{ $project->deadline ? $project->deadline->format('Y/m/d') : '未設定' }}
                            </strong>
                        </div>

                        <div class="project-meta-pill">
                            <span>見積金額</span>
                            <strong>
                                {{ $project->budget ? '¥' . number_format($project->budget) : '未設定' }}
                            </strong>
                        </div>
                    </div>
                </article>

                <section class="project-panel">
                    <div class="project-panel__header">
                        <h3>案件概要</h3>
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

                <section class="project-panel">
                    <div class="project-panel__header">
                        <h3>タスク概要</h3>
                        <span class="project-panel__badge">Coming Soon</span>
                    </div>

                    <div class="project-progress">
                        <div class="project-progress__bar">
                            <span style="width: 0%;"></span>
                        </div>

                        <p class="project-progress__text">
                            <strong>0%</strong>
                            <span>タスク機能追加後に自動計算します</span>
                        </p>
                    </div>
                </section>

                <section class="project-panel">
                    <div class="project-panel__header">
                        <h3>備考・メモ</h3>
                        <span class="project-panel__badge">Coming Soon</span>
                    </div>

                    <p class="project-empty-text">
                        メモ機能は今後追加予定です。クライアントとのやり取りや補足情報を残せるようにします。
                    </p>
                </section>

                <section class="project-panel">
                    <div class="project-panel__header">
                        <h3>関連タスク一覧</h3>
                        <span class="project-panel__badge">Coming Soon</span>
                    </div>

                    <div class="project-task-placeholder">
                        <p>この案件に紐づくタスクを、今後ここに表示します。</p>
                    </div>
                </section>
            </div>

            <aside class="project-show-side">
                <section class="project-side-card">
                    <h3 class="project-side-card__title">案件イメージ</h3>

                    <div class="project-thumbnail-placeholder">
                        <span>画像プレビュー</span>
                    </div>

                    <p class="project-side-card__note">
                        画像アップロード機能は今後追加予定です。
                    </p>
                </section>

                <section class="project-side-card">
                    <h3 class="project-side-card__title">カテゴリ</h3>

                    @if ($project->category)
                        <div class="project-tags">
                            <span>{{ $project->category->name }}</span>
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

                <section class="project-side-card">
                    <h3 class="project-side-card__title">タグ</h3>

                    <div class="project-tags">
                        <span>Web制作</span>
                        <span>Laravel</span>
                        <span>管理画面</span>
                    </div>

                    <p class="project-side-card__note">
                        タグ機能は今後DB化します。
                    </p>
                </section>

                <section class="project-side-card">
                    <h3 class="project-side-card__title">主要情報</h3>

                    <dl class="project-info-list">
                        <div>
                            <dt>案件ID</dt>
                            <dd>#{{ $project->id }}</dd>
                        </div>

                        <div>
                            <dt>作成日</dt>
                            <dd>{{ $project->created_at->format('Y/m/d') }}</dd>
                        </div>

                        <div>
                            <dt>最終更新日</dt>
                            <dd>{{ $project->updated_at->format('Y/m/d H:i') }}</dd>
                        </div>

                        <div>
                            <dt>ステータス</dt>
                            <dd>
                                @if ($project->status === 'not_started')
                                    未着手
                                @elseif ($project->status === 'in_progress')
                                    進行中
                                @elseif ($project->status === 'completed')
                                    完了
                                @elseif ($project->status === 'paused')
                                    停止中
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt>クライアント</dt>
                            <dd>{{ $project->client_name }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="project-side-card">
                    <h3 class="project-side-card__title">添付ファイル</h3>

                    <p class="project-empty-text">
                        添付ファイル機能は今後追加予定です。
                    </p>
                </section>
            </aside>
        </div>
    </section>
@endsection