@extends('layouts.app')

@section('title', 'ダッシュボード | ClientWork Manager')

@section('page-label', 'Dashboard')

@section('page-title', 'ダッシュボード')

@section('page-description', '案件の進行状況・売上見込み・未完了タスクをまとめて確認できます。')

@section('page-actions')
    <a href="#" class="c-button c-button--primary">
        新規案件を追加
    </a>
@endsection

@section('content')
    <div class="dashboard">
        <section class="dashboard-summary" aria-label="案件サマリー">
            <article class="dashboard-summary-card">
                <div class="dashboard-summary-card__icon dashboard-summary-card__icon--blue">
                    案
                </div>

                <div>
                    <p class="dashboard-summary-card__label">進行中案件</p>
                    <p class="dashboard-summary-card__value">8件</p>
                </div>

                <p class="dashboard-summary-card__note">前月比 +2</p>
            </article>

            <article class="dashboard-summary-card">
                <div class="dashboard-summary-card__icon dashboard-summary-card__icon--green">
                    売
                </div>

                <div>
                    <p class="dashboard-summary-card__label">今月の売上見込み</p>
                    <p class="dashboard-summary-card__value">¥420,000</p>
                </div>

                <p class="dashboard-summary-card__note">目標まで 70%</p>
            </article>

            <article class="dashboard-summary-card">
                <div class="dashboard-summary-card__icon dashboard-summary-card__icon--yellow">
                    未
                </div>

                <div>
                    <p class="dashboard-summary-card__label">未完了タスク</p>
                    <p class="dashboard-summary-card__value">14件</p>
                </div>

                <p class="dashboard-summary-card__note">本日締切 3件</p>
            </article>

            <article class="dashboard-summary-card">
                <div class="dashboard-summary-card__icon dashboard-summary-card__icon--purple">
                    顧
                </div>

                <div>
                    <p class="dashboard-summary-card__label">登録顧客</p>
                    <p class="dashboard-summary-card__value">23社</p>
                </div>

                <p class="dashboard-summary-card__note">新規 +4</p>
            </article>
        </section>

        <div class="dashboard-grid">
            <section class="c-card c-card--padding dashboard-panel">
                <div class="dashboard-panel__head">
                    <div>
                        <p class="dashboard-panel__label">Project Progress</p>
                        <h2 class="dashboard-panel__title">案件の進行状況</h2>
                    </div>

                    <span class="c-badge c-badge--blue">今月</span>
                </div>

                <div class="dashboard-project-list">
                    <article class="dashboard-project">
                        <div class="dashboard-project__head">
                            <div>
                                <h3 class="dashboard-project__title">コーポレートサイト制作</h3>
                                <p class="dashboard-project__client">株式会社サンプル</p>
                            </div>

                            <span class="c-badge c-badge--green">進行中</span>
                        </div>

                        <div class="dashboard-progress">
                            <div class="dashboard-progress__bar">
                                <span style="width: 72%;"></span>
                            </div>
                            <p class="dashboard-progress__text">72%</p>
                        </div>
                    </article>

                    <article class="dashboard-project">
                        <div class="dashboard-project__head">
                            <div>
                                <h3 class="dashboard-project__title">LPデザイン改善</h3>
                                <p class="dashboard-project__client">Blue Works</p>
                            </div>

                            <span class="c-badge c-badge--yellow">確認待ち</span>
                        </div>

                        <div class="dashboard-progress">
                            <div class="dashboard-progress__bar">
                                <span style="width: 48%;"></span>
                            </div>
                            <p class="dashboard-progress__text">48%</p>
                        </div>
                    </article>

                    <article class="dashboard-project">
                        <div class="dashboard-project__head">
                            <div>
                                <h3 class="dashboard-project__title">WordPress保守対応</h3>
                                <p class="dashboard-project__client">Green Studio</p>
                            </div>

                            <span class="c-badge c-badge--gray">下書き</span>
                        </div>

                        <div class="dashboard-progress">
                            <div class="dashboard-progress__bar">
                                <span style="width: 24%;"></span>
                            </div>
                            <p class="dashboard-progress__text">24%</p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="c-card c-card--padding dashboard-panel">
                <div class="dashboard-panel__head">
                    <div>
                        <p class="dashboard-panel__label">Today Tasks</p>
                        <h2 class="dashboard-panel__title">今日やること</h2>
                    </div>

                    <span class="c-badge c-badge--green">3件</span>
                </div>

                <div class="dashboard-task-list">
                    <label class="dashboard-task">
                        <input type="checkbox" class="dashboard-task__checkbox">
                        <span class="dashboard-task__body">
                            <span class="dashboard-task__title">トップページのFV案を確認</span>
                            <span class="dashboard-task__meta">10:00 / デザイン確認</span>
                        </span>
                    </label>

                    <label class="dashboard-task">
                        <input type="checkbox" class="dashboard-task__checkbox">
                        <span class="dashboard-task__body">
                            <span class="dashboard-task__title">見積書の内容を修正</span>
                            <span class="dashboard-task__meta">13:00 / 顧客対応</span>
                        </span>
                    </label>

                    <label class="dashboard-task">
                        <input type="checkbox" class="dashboard-task__checkbox">
                        <span class="dashboard-task__body">
                            <span class="dashboard-task__title">制作スケジュールを更新</span>
                            <span class="dashboard-task__meta">18:00 / 案件管理</span>
                        </span>
                    </label>
                </div>
            </section>
        </div>
    </div>
@endsection