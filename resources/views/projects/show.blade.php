@extends('layouts.app')

@section('title', '案件詳細 | ClientWork Manager')

@section('content')
    <section class="projects-page project-show-page">
        <div class="projects-page__inner">
            <div class="projects-page__head">
                <div>
                    <p class="section-label">Project Detail</p>
                    <h1 class="section-title">案件詳細</h1>
                    <p class="section-description">
                        案件の基本情報、進捗、金額、納期を確認できます。
                    </p>
                </div>

                <div class="projects-page__actions">
                    <a href="{{ route('projects.index') }}" class="btn btn--ghost">
                        案件一覧へ戻る
                    </a>

                    <a href="{{ route('projects.edit', $project) }}" class="btn btn--primary">
                        編集する
                    </a>
                </div>
            </div>

            <div class="project-show">
                <div class="project-show__main">
                    <article class="project-detail-card">
                        <div class="project-detail-card__head">
                            <div>
                                <div class="project-detail-card__breadcrumb">
                                    <a href="{{ route('projects.index') }}">案件一覧</a>
                                    <span>/</span>
                                    <span>案件詳細</span>
                                </div>

                                <h2 class="project-detail-card__title">
                                    {{ $project->title }}
                                </h2>

                                <p class="project-detail-card__client">
                                    {{ $project->client_name }}
                                </p>
                            </div>

                            <div class="project-detail-card__actions">
                                <a href="{{ route('projects.edit', $project) }}" class="btn btn--sm btn--ghost">
                                    編集
                                </a>

                                <button type="button" class="btn btn--sm btn--danger" disabled>
                                    削除
                                </button>
                            </div>
                        </div>

                        <div class="project-detail-card__meta">
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

                            <div class="project-detail-card__meta-item">
                                <span class="project-detail-card__meta-label">開始日</span>
                                <span>
                                    {{ $project->start_date ? $project->start_date->format('Y/m/d') : '未設定' }}
                                </span>
                            </div>

                            <div class="project-detail-card__meta-item">
                                <span class="project-detail-card__meta-label">納期</span>
                                <span>
                                    {{ $project->deadline ? $project->deadline->format('Y/m/d') : '未設定' }}
                                </span>
                            </div>

                            <div class="project-detail-card__meta-item">
                                <span class="project-detail-card__meta-label">見積金額</span>
                                <span>
                                    {{ $project->budget ? '¥' . number_format($project->budget) : '未設定' }}
                                </span>
                            </div>
                        </div>
                    </article>

                    <section class="project-panel">
                        <div class="project-panel__head">
                            <h3 class="project-panel__title">案件概要</h3>
                        </div>

                        <div class="project-panel__body">
                            @if ($project->description)
                                <p class="project-description">
                                    {{ $project->description }}
                                </p>
                            @else
                                <p class="project-empty-text">
                                    案件概要はまだ登録されていません。
                                </p>
                            @endif
                        </div>
                    </section>

                    <section class="project-panel">
                        <div class="project-panel__head">
                            <h3 class="project-panel__title">タスク概要</h3>

                            <span class="project-panel__badge">
                                Coming Soon
                            </span>
                        </div>

                        <div class="project-progress">
                            <div class="project-progress__bar">
                                <span style="width: 0%;"></span>
                            </div>

                            <div class="project-progress__text">
                                <span>進捗率</span>
                                <strong>0%</strong>
                                <span>タスク機能追加後に自動計算します</span>
                            </div>
                        </div>
                    </section>

                    <section class="project-panel">
                        <div class="project-panel__head">
                            <h3 class="project-panel__title">備考・メモ</h3>

                            <span class="project-panel__badge">
                                Coming Soon
                            </span>
                        </div>

                        <p class="project-empty-text">
                            メモ機能は今後追加予定です。クライアントとのやり取りや補足情報を残せるようにします。
                        </p>
                    </section>

                    <section class="project-panel">
                        <div class="project-panel__head">
                            <h3 class="project-panel__title">関連タスク一覧</h3>

                            <span class="project-panel__badge">
                                Coming Soon
                            </span>
                        </div>

                        <div class="project-task-placeholder">
                            <p>この案件に紐づくタスクを、今後ここに表示します。</p>
                        </div>
                    </section>
                </div>

                <aside class="project-show__side">
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

                        <p class="project-empty-text">
                            Coming Soon
                        </p>
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
        </div>
    </section>
@endsection