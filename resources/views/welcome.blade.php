<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ClientWork Manager | 小規模事業者向け案件管理アプリ</title>

    <meta name="description" content="ClientWork Managerは、案件・顧客・タスク・期限・決済をまとめて管理できる、小規模事業者・フリーランス向けの案件管理アプリです。">

    @vite('resources/css/app.css')
</head>

<body class="landing-page">
    <a class="landing-skip-link" href="#main-content">
        本文へ移動
    </a>

    <header class="landing-header">
        <div class="landing-container landing-header__inner">
            <a class="landing-brand" href="{{ url('/') }}" aria-label="ClientWork Manager トップページ">
                <span class="landing-brand__logo">
                    <img src="{{ asset('images/clientwork-manager-logo.png') }}" alt="" width="44" height="44">
                </span>

                <span class="landing-brand__copy">
                    <strong class="landing-brand__name">
                        ClientWork Manager
                    </strong>

                    <span class="landing-brand__description">
                        小規模事業者向け 案件管理アプリ
                    </span>
                </span>
            </a>

            <nav class="landing-header__navigation" aria-label="メインナビゲーション">
                <a href="#features">機能</a>
                <a href="#screens">主な画面</a>
                <a href="#benefits">導入メリット</a>
            </nav>

            <div class="landing-header__actions">
                @auth
                    <a class="landing-button landing-button--secondary landing-button--small"
                        href="{{ route('dashboard') }}">
                        ダッシュボードへ
                    </a>
                @else
                    <a class="landing-button landing-button--ghost landing-button--small" href="{{ route('login') }}">
                        ログイン
                    </a>

                    <a class="landing-button landing-button--primary landing-button--small" href="{{ route('register') }}">
                        無料で始める
                    </a>
                @endauth
            </div>

            <details class="landing-mobile-menu">
                <summary aria-label="メニューを開く">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </summary>

                <div class="landing-mobile-menu__panel">
                    <nav aria-label="スマートフォン用ナビゲーション">
                        <a href="#features">機能</a>
                        <a href="#screens">主な画面</a>
                        <a href="#benefits">導入メリット</a>
                    </nav>

                    <div class="landing-mobile-menu__actions">
                        @auth
                            <a class="landing-button landing-button--primary" href="{{ route('dashboard') }}">
                                ダッシュボードへ
                            </a>
                        @else
                            <a class="landing-button landing-button--secondary" href="{{ route('login') }}">
                                ログイン
                            </a>

                            <a class="landing-button landing-button--primary" href="{{ route('register') }}">
                                無料で始める
                            </a>
                        @endauth
                    </div>
                </div>
            </details>
        </div>
    </header>

    <main id="main-content">
        <section class="landing-hero">
            <div class="landing-container landing-hero__inner">
                <div class="landing-hero__content">
                    <p class="landing-eyebrow">
                        フリーランス・小規模事業者向け
                    </p>

                    <h1 class="landing-hero__title">
                        案件もタスクも進捗も、
                        <span>ひとつで見える。</span>
                    </h1>

                    <p class="landing-hero__description">
                        ClientWork Managerは、案件・顧客・タスク・期限・決済を
                        まとめて管理できる業務管理アプリです。
                        仕事の全体像を見える化し、次にやるべきことを迷わせません。
                    </p>

                    <div class="landing-hero__actions">
                        @auth
                            <a class="landing-button landing-button--primary landing-button--large"
                                href="{{ route('dashboard') }}">
                                ダッシュボードへ
                            </a>
                        @else
                            <a class="landing-button landing-button--primary landing-button--large"
                                href="{{ route('register') }}">
                                無料で始める
                            </a>

                            <a class="landing-button landing-button--secondary landing-button--large" href="#features">
                                機能を詳しく見る
                            </a>
                        @endauth
                    </div>

                    <ul class="landing-hero__points">
                        <li>
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                            クレジットカード不要
                        </li>

                        <li>
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                            すぐに利用開始
                        </li>

                        <li>
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                            スマートフォン対応
                        </li>
                    </ul>
                </div>

                <div class="landing-dashboard-preview" aria-label="ダッシュボード画面イメージ">
                    <div class="landing-dashboard-preview__header">
                        <div>
                            <p class="landing-dashboard-preview__title">
                                ダッシュボード
                            </p>

                            <p class="landing-dashboard-preview__subtitle">
                                今日の仕事の全体像
                            </p>
                        </div>

                        <span class="landing-dashboard-preview__month">
                            2026年7月
                        </span>
                    </div>

                    <div class="landing-dashboard-preview__stats">
                        <article class="landing-preview-stat">
                            <p class="landing-preview-stat__label">
                                <span class="landing-preview-stat__dot landing-preview-stat__dot--blue"></span>
                                進行中案件
                            </p>

                            <p class="landing-preview-stat__value">
                                12<span>件</span>
                            </p>

                            <p class="landing-preview-stat__note">
                                前月比 +2
                            </p>
                        </article>

                        <article class="landing-preview-stat">
                            <p class="landing-preview-stat__label">
                                <span class="landing-preview-stat__dot landing-preview-stat__dot--orange"></span>
                                未完了タスク
                            </p>

                            <p class="landing-preview-stat__value">
                                28<span>件</span>
                            </p>

                            <p class="landing-preview-stat__note">
                                期限内 21件
                            </p>
                        </article>

                        <article class="landing-preview-stat">
                            <p class="landing-preview-stat__label">
                                <span class="landing-preview-stat__dot landing-preview-stat__dot--green"></span>
                                今月売上見込
                            </p>

                            <p class="landing-preview-stat__value landing-preview-stat__value--money">
                                ¥680K
                            </p>

                            <p class="landing-preview-stat__note">
                                決済待ち 3件
                            </p>
                        </article>
                    </div>

                    <div class="landing-dashboard-preview__content">
                        <article class="landing-preview-panel">
                            <h2 class="landing-preview-panel__title">
                                案件進行状況
                            </h2>

                            <div class="landing-progress-list">
                                <div class="landing-progress-item">
                                    <div class="landing-progress-item__header">
                                        <span>進行中</span>
                                        <strong>12件</strong>
                                    </div>

                                    <div class="landing-progress-item__track">
                                        <span class="landing-progress-item__bar landing-progress-item__bar--blue"
                                            style="width: 76%"></span>
                                    </div>
                                </div>

                                <div class="landing-progress-item">
                                    <div class="landing-progress-item__header">
                                        <span>見積中</span>
                                        <strong>4件</strong>
                                    </div>

                                    <div class="landing-progress-item__track">
                                        <span class="landing-progress-item__bar landing-progress-item__bar--orange"
                                            style="width: 42%"></span>
                                    </div>
                                </div>

                                <div class="landing-progress-item">
                                    <div class="landing-progress-item__header">
                                        <span>完了</span>
                                        <strong>8件</strong>
                                    </div>

                                    <div class="landing-progress-item__track">
                                        <span class="landing-progress-item__bar landing-progress-item__bar--green"
                                            style="width: 58%"></span>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="landing-preview-panel">
                            <h2 class="landing-preview-panel__title">
                                今週のタスク
                            </h2>

                            <ul class="landing-preview-task-list">
                                <li>
                                    <span class="landing-preview-task-list__check is-active"></span>
                                    <span class="landing-preview-task-list__name">提案書を送付</span>
                                    <time>今日</time>
                                </li>

                                <li>
                                    <span class="landing-preview-task-list__check"></span>
                                    <span class="landing-preview-task-list__name">デザイン確認</span>
                                    <time>7/21</time>
                                </li>

                                <li>
                                    <span class="landing-preview-task-list__check"></span>
                                    <span class="landing-preview-task-list__name">Stripe入金確認</span>
                                    <time>7/23</time>
                                </li>

                                <li>
                                    <span class="landing-preview-task-list__check"></span>
                                    <span class="landing-preview-task-list__name">LPコーディング</span>
                                    <time>7/25</time>
                                </li>
                            </ul>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section id="benefits" class="landing-proof">
            <div class="landing-container landing-proof__grid">
                <article class="landing-proof__item">
                    <p class="landing-proof__title">案件管理</p>
                    <p class="landing-proof__description">
                        一覧・検索・画像・CSV
                    </p>
                </article>

                <article class="landing-proof__item">
                    <p class="landing-proof__title">タスク管理</p>
                    <p class="landing-proof__description">
                        期限・優先度・並び替え
                    </p>
                </article>

                <article class="landing-proof__item">
                    <p class="landing-proof__title">顧客管理</p>
                    <p class="landing-proof__description">
                        顧客情報と名刺画像
                    </p>
                </article>

                <article class="landing-proof__item">
                    <p class="landing-proof__title">決済連携</p>
                    <p class="landing-proof__description">
                        Stripe Payment Element
                    </p>
                </article>
            </div>
        </section>

        <section id="features" class="landing-section landing-features">
            <div class="landing-container">
                <div class="landing-section-heading">
                    <p class="landing-section-heading__eyebrow">
                        FEATURES
                    </p>

                    <h2 class="landing-section-heading__title">
                        仕事の管理を、もっとシンプルに
                    </h2>

                    <p class="landing-section-heading__description">
                        実務で必要な機能を、迷わず使える画面に整理しました。
                    </p>
                </div>

                <div class="landing-feature-grid">
                    <article class="landing-feature-card">
                        <div class="landing-feature-card__icon">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4l1.5 2h9.5A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5v-10Z" />
                            </svg>
                        </div>

                        <p class="landing-feature-card__number">01</p>

                        <h3 class="landing-feature-card__title">
                            案件情報を一元管理
                        </h3>

                        <p class="landing-feature-card__description">
                            案件名、顧客、進捗、優先度、納期、見積金額をひとつの画面で管理。
                            検索・絞り込み・CSV出力にも対応しています。
                        </p>
                    </article>

                    <article class="landing-feature-card">
                        <div class="landing-feature-card__icon">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6h11M9 12h11M9 18h11" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m3 6 1.2 1.2L6.5 5M3 12l1.2 1.2L6.5 11M3 18l1.2 1.2L6.5 17" />
                            </svg>
                        </div>

                        <p class="landing-feature-card__number">02</p>

                        <h3 class="landing-feature-card__title">
                            期限とタスクを見える化
                        </h3>

                        <p class="landing-feature-card__description">
                            案件ごとのタスクを優先度・期限・状態で管理。
                            並び替えや期限通知により、確認漏れや対応漏れを防ぎます。
                        </p>
                    </article>

                    <article class="landing-feature-card">
                        <div class="landing-feature-card__icon">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11h6M19 8v6" />
                            </svg>
                        </div>

                        <p class="landing-feature-card__number">03</p>

                        <h3 class="landing-feature-card__title">
                            顧客と決済までつなげる
                        </h3>

                        <p class="landing-feature-card__description">
                            顧客情報や名刺画像を保存し、Stripe決済へ接続。
                            案件管理から支払い確認までの業務フローをまとめます。
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section id="screens" class="landing-section landing-screens">
            <div class="landing-container">
                <div class="landing-section-heading">
                    <p class="landing-section-heading__eyebrow">
                        SCREENS
                    </p>

                    <h2 class="landing-section-heading__title">
                        主な画面
                    </h2>

                    <p class="landing-section-heading__description">
                        PCでもスマートフォンでも、必要な情報へすぐにアクセスできます。
                    </p>
                </div>

                <div class="landing-screen-grid">
                    <article class="landing-screen-card">
                        <div class="landing-screen-preview" aria-hidden="true">
                            <div class="landing-screen-preview__sidebar"></div>

                            <div class="landing-screen-preview__content">
                                <span class="landing-screen-preview__heading"></span>

                                <div class="landing-screen-preview__metrics">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>

                                <div class="landing-screen-preview__panels">
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>

                        <h3>ダッシュボード</h3>
                        <p>案件数、タスク、売上見込みをひと目で確認できます。</p>
                    </article>

                    <article class="landing-screen-card">
                        <div class="landing-screen-preview" aria-hidden="true">
                            <div class="landing-screen-preview__sidebar"></div>

                            <div class="landing-screen-preview__content">
                                <span class="landing-screen-preview__heading"></span>

                                <div class="landing-screen-preview__filter"></div>

                                <div class="landing-screen-preview__rows">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>

                        <h3>案件一覧</h3>
                        <p>検索・絞り込み・優先度・CSV出力に対応しています。</p>
                    </article>

                    <article class="landing-screen-card">
                        <div class="landing-screen-preview" aria-hidden="true">
                            <div class="landing-screen-preview__sidebar"></div>

                            <div class="landing-screen-preview__content">
                                <span class="landing-screen-preview__heading"></span>

                                <div class="landing-screen-preview__detail">
                                    <span></span>
                                    <span></span>
                                </div>

                                <div class="landing-screen-preview__rows">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>

                        <h3>案件詳細</h3>
                        <p>画像、進捗、タスク、カテゴリ、タグをまとめて表示します。</p>
                    </article>

                    <article class="landing-screen-card">
                        <div class="landing-screen-preview" aria-hidden="true">
                            <div class="landing-screen-preview__sidebar"></div>

                            <div class="landing-screen-preview__content">
                                <span class="landing-screen-preview__heading"></span>

                                <div class="landing-screen-preview__filter"></div>

                                <div class="landing-screen-preview__tasks">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>

                        <h3>タスク管理</h3>
                        <p>期限、優先度、ステータスを案件ごとに管理できます。</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="landing-cta">
            <div class="landing-container landing-cta__inner">
                <p class="landing-cta__eyebrow">
                    GET STARTED
                </p>

                <h2 class="landing-cta__title">
                    案件管理を、今日からもっとわかりやすく。
                </h2>

                <p class="landing-cta__description">
                    案件・顧客・タスク・期限・決済を、ひとつにつなげましょう。
                </p>

                <div class="landing-cta__actions">
                    @auth
                        <a class="landing-button landing-button--primary landing-button--large"
                            href="{{ route('dashboard') }}">
                            ダッシュボードへ
                        </a>
                    @else
                        <a class="landing-button landing-button--primary landing-button--large"
                            href="{{ route('register') }}">
                            無料で始める
                        </a>

                        <a class="landing-button landing-button--dark-outline landing-button--large"
                            href="{{ route('login') }}">
                            ログイン
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-container landing-footer__inner">
            <div class="landing-footer__brand">
                <div class="landing-footer__brand-row">
                    <span class="landing-footer__logo">
                        <img src="{{ asset('images/clientwork-manager-logo.png') }}" alt="" width="42" height="42">
                    </span>

                    <strong>ClientWork Manager</strong>
                </div>

                <p>
                    小規模事業者・フリーランス向け案件管理アプリ
                </p>

                <small>
                    © {{ now()->year }} ClientWork Manager
                </small>
            </div>

            <div class="landing-footer__navigation">
                <div>
                    <p class="landing-footer__heading">製品</p>
                    <a href="#features">機能紹介</a>
                    <a href="#screens">主な画面</a>
                    <a href="#benefits">導入メリット</a>
                </div>

                <div>
                    <p class="landing-footer__heading">アカウント</p>

                    @auth
                        <a href="{{ route('dashboard') }}">ダッシュボード</a>
                    @else
                        <a href="{{ route('login') }}">ログイン</a>
                        <a href="{{ route('register') }}">新規登録</a>
                    @endauth
                </div>

                <div>
                    <p class="landing-footer__heading">技術構成</p>
                    <span>Laravel 10</span>
                    <span>Fortify / Sanctum</span>
                    <span>Stripe</span>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>