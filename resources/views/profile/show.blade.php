@extends('layouts.app')

@section('title', 'プロフィール | ClientWork Manager')

@section('page-label', 'Profile')

@section('page-title', 'プロフィール')

@section('page-description', '登録情報の確認・変更と、案件・タスクの実績確認ができます。')

@section('content')
    @php
        $shouldOpenPasswordForm =
            $errors->updatePassword->any()
            || session('status') === 'password-updated';
    @endphp

    <div class="profile-page">
        <section class="profile-hero">
            <div
                class="profile-hero__avatar"
                aria-hidden="true"
            >
                {{ mb_substr($user->name, 0, 1) }}
            </div>

            <div class="profile-hero__body">
                <p class="profile-hero__eyebrow">
                    Account Profile
                </p>

                <h2 class="profile-hero__name">
                    {{ $user->name }}
                </h2>

                <dl class="profile-hero__details">
                    <div class="profile-hero__detail">
                        <dt>
                            メールアドレス
                        </dt>

                        <dd>
                            {{ $user->email }}
                        </dd>
                    </div>

                    <div class="profile-hero__detail">
                        <dt>
                            登録日
                        </dt>

                        <dd>
                            {{ $user->created_at->format('Y年m月d日') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section
            class="profile-summary"
            aria-label="利用実績"
        >
            <article class="profile-summary-card">
                <div class="profile-summary-card__icon">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M3 7.5h6l2 2h10v10.5H3V7.5Z"></path>
                        <path d="M3 7.5V5h6l2 2"></path>
                    </svg>
                </div>

                <div>
                    <p class="profile-summary-card__label">
                        自分の案件数
                    </p>

                    <p class="profile-summary-card__value">
                        {{ number_format($totalProjectsCount) }}
                        <span>件</span>
                    </p>
                </div>
            </article>

            <article class="profile-summary-card">
                <div class="profile-summary-card__icon profile-summary-card__icon--green">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                        ></circle>
                        <path d="m8 12 2.5 2.5L16 9"></path>
                    </svg>
                </div>

                <div>
                    <p class="profile-summary-card__label">
                        完了案件数
                    </p>

                    <p class="profile-summary-card__value">
                        {{ number_format($completedProjectsCount) }}
                        <span>件</span>
                    </p>
                </div>
            </article>

            <article class="profile-summary-card">
                <div class="profile-summary-card__icon profile-summary-card__icon--blue">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M12 3a9 9 0 1 1-6.36 2.64"></path>
                        <path d="M3 4v5h5"></path>
                    </svg>
                </div>

                <div>
                    <p class="profile-summary-card__label">
                        進行中案件数
                    </p>

                    <p class="profile-summary-card__value">
                        {{ number_format($inProgressProjectsCount) }}
                        <span>件</span>
                    </p>
                </div>
            </article>

            <article class="profile-summary-card">
                <div class="profile-summary-card__icon profile-summary-card__icon--yellow">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m4 6 1.5 1.5L8.5 4.5"></path>
                        <path d="M11 6h9"></path>
                        <path d="m4 12 1.5 1.5 3-3"></path>
                        <path d="M11 12h9"></path>
                        <path d="m4 18 1.5 1.5 3-3"></path>
                        <path d="M11 18h9"></path>
                    </svg>
                </div>

                <div>
                    <p class="profile-summary-card__label">
                        未完了タスク数
                    </p>

                    <p class="profile-summary-card__value">
                        {{ number_format($incompleteTasksCount) }}
                        <span>件</span>
                    </p>
                </div>
            </article>
        </section>

        <div class="profile-content-grid">
            <div class="profile-settings-stack">
                <section class="profile-panel">
                    <div class="profile-panel__head">
                        <div>
                            <p class="profile-panel__eyebrow">
                                Account Information
                            </p>

                            <h2 class="profile-panel__title">
                                基本情報を編集
                            </h2>

                            <p class="profile-panel__description">
                                ユーザー名とログイン用メールアドレスを変更できます。
                            </p>
                        </div>
                    </div>

                    @if (session('status') === 'profile-information-updated')
                        <div
                            class="profile-feedback profile-feedback--success"
                            role="status"
                        >
                            プロフィール情報を更新しました。
                        </div>
                    @endif

                    <form
                        action="{{ route('user-profile-information.update') }}"
                        method="POST"
                        class="profile-form"
                    >
                        @csrf
                        @method('PUT')

                        <div class="profile-form__field">
                            <label
                                for="profile-name"
                                class="profile-form__label"
                            >
                                ユーザー名
                                <span class="profile-form__required">
                                    必須
                                </span>
                            </label>

                            <input
                                id="profile-name"
                                type="text"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                class="profile-form__input @error('name', 'updateProfileInformation') is-invalid @enderror"
                                maxlength="255"
                                autocomplete="name"
                                required
                                aria-describedby="profile-name-error"
                            >

                            @error('name', 'updateProfileInformation')
                                <p
                                    id="profile-name-error"
                                    class="profile-form__error"
                                >
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="profile-form__field">
                            <label
                                for="profile-email"
                                class="profile-form__label"
                            >
                                メールアドレス
                                <span class="profile-form__required">
                                    必須
                                </span>
                            </label>

                            <input
                                id="profile-email"
                                type="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                class="profile-form__input @error('email', 'updateProfileInformation') is-invalid @enderror"
                                maxlength="255"
                                autocomplete="username"
                                required
                                aria-describedby="profile-email-help profile-email-error"
                            >

                            <p
                                id="profile-email-help"
                                class="profile-form__help"
                            >
                                次回ログイン時から新しいメールアドレスを使用します。
                            </p>

                            @error('email', 'updateProfileInformation')
                                <p
                                    id="profile-email-error"
                                    class="profile-form__error"
                                >
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="profile-form__actions">
                            <button
                                type="submit"
                                class="profile-form__submit"
                            >
                                基本情報を保存
                            </button>
                        </div>
                    </form>
                </section>

                <section class="profile-panel profile-panel--password">
                    <div class="profile-panel__head">
                        <div>
                            <p class="profile-panel__eyebrow">
                                Security
                            </p>

                            <h2 class="profile-panel__title">
                                パスワード設定
                            </h2>

                            <p class="profile-panel__description">
                                本人確認のため、変更時には現在のパスワードが必要です。
                            </p>
                        </div>
                    </div>

                    <details
                        class="profile-password-disclosure"
                        @if ($shouldOpenPasswordForm) open @endif
                    >
                        <summary class="profile-password-disclosure__summary">
                            <span class="profile-password-disclosure__summary-text">
                                パスワードを変更する
                            </span>

                            <span
                                class="profile-password-disclosure__icon"
                                aria-hidden="true"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </span>
                        </summary>

                        <div class="profile-password-disclosure__content">
                            @if (session('status') === 'password-updated')
                                <div
                                    class="profile-feedback profile-feedback--success"
                                    role="status"
                                >
                                    パスワードを変更しました。
                                </div>
                            @endif

                            <form
                                action="{{ route('user-password.update') }}"
                                method="POST"
                                class="profile-form"
                            >
                                @csrf
                                @method('PUT')

                                <div class="profile-form__field">
                                    <label
                                        for="current-password"
                                        class="profile-form__label"
                                    >
                                        現在のパスワード
                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="current-password"
                                        type="password"
                                        name="current_password"
                                        class="profile-form__input @error('current_password', 'updatePassword') is-invalid @enderror"
                                        autocomplete="current-password"
                                        required
                                        aria-describedby="current-password-error"
                                    >

                                    @error('current_password', 'updatePassword')
                                        <p
                                            id="current-password-error"
                                            class="profile-form__error"
                                        >
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="new-password"
                                        class="profile-form__label"
                                    >
                                        新しいパスワード
                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="new-password"
                                        type="password"
                                        name="password"
                                        class="profile-form__input @error('password', 'updatePassword') is-invalid @enderror"
                                        autocomplete="new-password"
                                        required
                                        aria-describedby="new-password-help new-password-error"
                                    >

                                    <p
                                        id="new-password-help"
                                        class="profile-form__help"
                                    >
                                        8文字以上で、推測されにくいパスワードを設定してください。
                                    </p>

                                    @error('password', 'updatePassword')
                                        <p
                                            id="new-password-error"
                                            class="profile-form__error"
                                        >
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="password-confirmation"
                                        class="profile-form__label"
                                    >
                                        新しいパスワードの確認
                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="password-confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        class="profile-form__input"
                                        autocomplete="new-password"
                                        required
                                    >
                                </div>

                                <div class="profile-form__actions">
                                    <button
                                        type="submit"
                                        class="profile-form__submit"
                                    >
                                        パスワードを変更
                                    </button>
                                </div>
                            </form>
                        </div>
                    </details>
                </section>
            </div>

            <section class="profile-panel profile-panel--recent">
                <div class="profile-panel__head">
                    <div>
                        <p class="profile-panel__eyebrow">
                            Recent Projects
                        </p>

                        <h2 class="profile-panel__title">
                            最近の案件
                        </h2>

                        <p class="profile-panel__description">
                            最近更新した案件を3件表示しています。
                        </p>
                    </div>

                    <a
                        href="{{ route('projects.index') }}"
                        class="profile-panel__link"
                    >
                        すべて見る
                    </a>
                </div>

                @if ($recentProjects->isEmpty())
                    <div class="profile-empty">
                        <p class="profile-empty__title">
                            案件がまだありません
                        </p>

                        <a
                            href="{{ route('projects.create') }}"
                            class="profile-empty__link"
                        >
                            案件を登録する
                        </a>
                    </div>
                @else
                    <div class="profile-project-list">
                        @foreach ($recentProjects as $project)
                            <a
                                href="{{ route('projects.show', $project) }}"
                                class="profile-project"
                            >
                                <div class="profile-project__icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M3 7.5h6l2 2h10v10.5H3V7.5Z"></path>
                                        <path d="M3 7.5V5h6l2 2"></path>
                                    </svg>
                                </div>

                                <div class="profile-project__body">
                                    <p class="profile-project__title">
                                        {{ $project->title }}
                                    </p>

                                    <p class="profile-project__client">
                                        {{ $project->client_name }}
                                    </p>
                                </div>

                                <div class="profile-project__meta">
                                    <time datetime="{{ $project->updated_at->toDateString() }}">
                                        {{ $project->updated_at->format('Y/m/d') }}
                                    </time>

                                    @switch($project->status)
                                        @case('not_started')
                                            <span class="c-badge c-badge--gray">
                                                未着手
                                            </span>
                                            @break

                                        @case('in_progress')
                                            <span class="c-badge c-badge--blue">
                                                進行中
                                            </span>
                                            @break

                                        @case('completed')
                                            <span class="c-badge c-badge--green">
                                                完了
                                            </span>
                                            @break

                                        @case('paused')
                                            <span class="c-badge c-badge--yellow">
                                                保留中
                                            </span>
                                            @break

                                        @default
                                            <span class="c-badge c-badge--gray">
                                                未設定
                                            </span>
                                    @endswitch
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection