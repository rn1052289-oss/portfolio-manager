@extends('layouts.app')

@section('title', 'ダッシュボード | ClientWork Manager')

@section('page-label', 'Dashboard')

@section('page-title', 'ダッシュボード')

@section('page-description', '案件の進行状況やタスクを確認できる管理画面です。')

@section('page-actions')
    <a href="#" class="c-button c-button--primary">
        新規案件を追加
    </a>
@endsection

@section('content')
    <section class="c-section">
        <div class="c-section__head">
            <div>
                <h2 class="c-section__title">
                    今日の状況
                </h2>

                <p class="c-section__text">
                    進行中の案件や確認が必要なタスクを、ここでまとめて確認できます。
                </p>
            </div>

            <span class="c-badge c-badge--blue">
                Portfolio UI
            </span>
        </div>

        <div class="c-card c-card--padding">
            <p class="dashboard__card-label">Welcome</p>

            <h2 class="dashboard__card-title">
                ClientWork Managerへようこそ
            </h2>

            <p class="dashboard__card-text">
                ここから案件・顧客・タスクを整理して、仕事の進み具合を見える化していきます。
            </p>

            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 20px;">
                <span class="c-badge c-badge--green">進行中</span>
                <span class="c-badge c-badge--yellow">確認待ち</span>
                <span class="c-badge c-badge--gray">下書き</span>
            </div>
        </div>
    </section>
@endsection