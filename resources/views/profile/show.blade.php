@extends('layouts.app')

@section('title', 'プロフィール | ClientWork Manager')

@section('page-label', 'Profile')

@section('page-title', 'プロフィール')

@section('page-description', '登録情報の確認・変更と、案件・タスクの実績確認ができます。')

@section('content')
    @php
        $businessProfile = $user->businessProfile;

        $shouldOpenBusinessProfileForm =
            $businessProfile === null
            || $errors->updateBusinessProfile->any()
            || session('status') === 'business-profile-updated';

        $shouldOpenPasswordForm =
            $errors->updatePassword->any()
            || session('status') === 'password-updated';

        $businessStatusClass = $businessProfile
            ? 'profile-business-status--completed'
            : 'profile-business-status--incomplete';

        $businessStatusLabel = $businessProfile
            ? '設定済み'
            : '未設定';

        $selectedBankAccountType = old(
            'bank_account_type',
            $businessProfile?->bank_account_type
        );
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
                            >

                            @error('name', 'updateProfileInformation')
                                <p class="profile-form__error">
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
                            >

                            <p class="profile-form__help">
                                次回ログイン時から新しいメールアドレスを使用します。
                            </p>

                            @error('email', 'updateProfileInformation')
                                <p class="profile-form__error">
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
                        {{ $shouldOpenPasswordForm ? 'open' : '' }}
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
                                    >

                                    @error('current_password', 'updatePassword')
                                        <p class="profile-form__error">
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
                                    >

                                    <p class="profile-form__help">
                                        8文字以上で、推測されにくいパスワードを設定してください。
                                    </p>

                                    @error('password', 'updatePassword')
                                        <p class="profile-form__error">
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

        <section class="profile-panel profile-panel--business">
            <div class="profile-panel__head">
                <div>
                    <p class="profile-panel__eyebrow">
                        Billing Settings
                    </p>

                    <h2 class="profile-panel__title">
                        請求書・事業者設定
                    </h2>

                    <p class="profile-panel__description">
                        請求書PDFに表示する発行者情報と振込先を設定します。
                    </p>
                </div>

                <span class="profile-business-status {{ $businessStatusClass }}">
                    {{ $businessStatusLabel }}
                </span>
            </div>

            <details
                class="profile-business-disclosure"
                {{ $shouldOpenBusinessProfileForm ? 'open' : '' }}
            >
                <summary class="profile-business-disclosure__summary">
                    <span class="profile-business-disclosure__summary-text">
                        <span
                            class="profile-business-disclosure__summary-icon"
                            aria-hidden="true"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M4 21V5a2 2 0 0 1 2-2h9l5 5v13H4Z"></path>
                                <path d="M14 3v6h6"></path>
                                <path d="M8 13h8"></path>
                                <path d="M8 17h5"></path>
                            </svg>
                        </span>

                        事業者情報と振込先を編集する
                    </span>

                    <span
                        class="profile-business-disclosure__arrow"
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

                <div class="profile-business-disclosure__content">
                    @if (session('status') === 'business-profile-updated')
                        <div
                            class="profile-feedback profile-feedback--success"
                            role="status"
                        >
                            事業者情報を更新しました。
                        </div>
                    @endif

                    @if ($businessProfile === null)
                        <div
                            class="profile-business-notice"
                            role="status"
                        >
                            <strong>
                                請求書PDFを作成する前に設定してください
                            </strong>

                            <p>
                                屋号・住所・振込先など、請求書へ掲載する情報がまだ登録されていません。
                            </p>
                        </div>
                    @endif

                    <form
                        action="{{ route('profile.business-profile.update') }}"
                        method="POST"
                        class="profile-form profile-business-form"
                    >
                        @csrf
                        @method('PUT')

                        <section
                            class="profile-business-section"
                            aria-labelledby="business-basic-information-title"
                        >
                            <div class="profile-business-section__head">
                                <p class="profile-business-section__eyebrow">
                                    Issuer Information
                                </p>

                                <h3
                                    id="business-basic-information-title"
                                    class="profile-business-section__title"
                                >
                                    発行者情報
                                </h3>

                                <p class="profile-business-section__description">
                                    請求書に表示する屋号・連絡先・所在地を入力してください。
                                </p>
                            </div>

                            <div class="profile-form__grid">
                                <div class="profile-form__field">
                                    <label
                                        for="business-name"
                                        class="profile-form__label"
                                    >
                                        屋号・会社名

                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="business-name"
                                        type="text"
                                        name="business_name"
                                        value="{{ old('business_name', $businessProfile?->business_name) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('business_name') ? ' is-invalid' : '' }}"
                                        maxlength="255"
                                        autocomplete="organization"
                                        required
                                    >

                                    @if ($errors->updateBusinessProfile->has('business_name'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('business_name') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="representative-name"
                                        class="profile-form__label"
                                    >
                                        代表者名
                                    </label>

                                    <input
                                        id="representative-name"
                                        type="text"
                                        name="representative_name"
                                        value="{{ old('representative_name', $businessProfile?->representative_name) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('representative_name') ? ' is-invalid' : '' }}"
                                        maxlength="255"
                                        autocomplete="name"
                                    >

                                    @if ($errors->updateBusinessProfile->has('representative_name'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('representative_name') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="business-email"
                                        class="profile-form__label"
                                    >
                                        事業用メールアドレス

                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="business-email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email', $businessProfile?->email ?? $user->email) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('email') ? ' is-invalid' : '' }}"
                                        maxlength="255"
                                        autocomplete="email"
                                        required
                                    >

                                    <p class="profile-form__help">
                                        請求書へ表示する事業用の連絡先です。
                                    </p>

                                    @if ($errors->updateBusinessProfile->has('email'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('email') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="business-phone"
                                        class="profile-form__label"
                                    >
                                        電話番号
                                    </label>

                                    <input
                                        id="business-phone"
                                        type="tel"
                                        name="phone"
                                        value="{{ old('phone', $businessProfile?->phone) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('phone') ? ' is-invalid' : '' }}"
                                        maxlength="50"
                                        autocomplete="tel"
                                    >

                                    @if ($errors->updateBusinessProfile->has('phone'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('phone') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="business-postal-code"
                                        class="profile-form__label"
                                    >
                                        郵便番号

                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="business-postal-code"
                                        type="text"
                                        name="postal_code"
                                        value="{{ old('postal_code', $businessProfile?->postal_code) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('postal_code') ? ' is-invalid' : '' }}"
                                        maxlength="20"
                                        autocomplete="postal-code"
                                        inputmode="numeric"
                                        placeholder="123-4567"
                                        required
                                    >

                                    @if ($errors->updateBusinessProfile->has('postal_code'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('postal_code') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="invoice-registration-number"
                                        class="profile-form__label"
                                    >
                                        適格請求書発行事業者登録番号
                                    </label>

                                    <input
                                        id="invoice-registration-number"
                                        type="text"
                                        name="invoice_registration_number"
                                        value="{{ old('invoice_registration_number', $businessProfile?->invoice_registration_number) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('invoice_registration_number') ? ' is-invalid' : '' }}"
                                        maxlength="18"
                                        placeholder="T1234567890123"
                                        spellcheck="false"
                                    >

                                    <p class="profile-form__help">
                                        登録済みの場合のみ入力してください。
                                    </p>

                                    @if ($errors->updateBusinessProfile->has('invoice_registration_number'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('invoice_registration_number') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field profile-form__field--full">
                                    <label
                                        for="business-address"
                                        class="profile-form__label"
                                    >
                                        住所

                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <textarea
                                        id="business-address"
                                        name="address"
                                        class="profile-form__input profile-form__textarea{{ $errors->updateBusinessProfile->has('address') ? ' is-invalid' : '' }}"
                                        maxlength="1000"
                                        autocomplete="street-address"
                                        rows="3"
                                        required
                                    >{{ old('address', $businessProfile?->address) }}</textarea>

                                    @if ($errors->updateBusinessProfile->has('address'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('address') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </section>

                        <section
                            class="profile-business-section"
                            aria-labelledby="business-bank-information-title"
                        >
                            <div class="profile-business-section__head">
                                <p class="profile-business-section__eyebrow">
                                    Bank Account
                                </p>

                                <h3
                                    id="business-bank-information-title"
                                    class="profile-business-section__title"
                                >
                                    振込先情報
                                </h3>

                                <p class="profile-business-section__description">
                                    請求先が銀行振込を行うための口座情報を入力してください。
                                </p>
                            </div>

                            <div class="profile-form__grid">
                                <div class="profile-form__field">
                                    <label
                                        for="bank-name"
                                        class="profile-form__label"
                                    >
                                        金融機関名

                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="bank-name"
                                        type="text"
                                        name="bank_name"
                                        value="{{ old('bank_name', $businessProfile?->bank_name) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('bank_name') ? ' is-invalid' : '' }}"
                                        maxlength="255"
                                        autocomplete="off"
                                        required
                                    >

                                    @if ($errors->updateBusinessProfile->has('bank_name'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('bank_name') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="bank-branch-name"
                                        class="profile-form__label"
                                    >
                                        支店名

                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="bank-branch-name"
                                        type="text"
                                        name="bank_branch_name"
                                        value="{{ old('bank_branch_name', $businessProfile?->bank_branch_name) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('bank_branch_name') ? ' is-invalid' : '' }}"
                                        maxlength="255"
                                        autocomplete="off"
                                        required
                                    >

                                    @if ($errors->updateBusinessProfile->has('bank_branch_name'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('bank_branch_name') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="bank-account-type"
                                        class="profile-form__label"
                                    >
                                        口座種別

                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <select
                                        id="bank-account-type"
                                        name="bank_account_type"
                                        class="profile-form__input profile-form__select{{ $errors->updateBusinessProfile->has('bank_account_type') ? ' is-invalid' : '' }}"
                                        required
                                    >
                                        <option value="">
                                            選択してください
                                        </option>

                                        <option
                                            value="普通"
                                            {{ $selectedBankAccountType === '普通' ? 'selected' : '' }}
                                        >
                                            普通
                                        </option>

                                        <option
                                            value="当座"
                                            {{ $selectedBankAccountType === '当座' ? 'selected' : '' }}
                                        >
                                            当座
                                        </option>

                                        <option
                                            value="貯蓄"
                                            {{ $selectedBankAccountType === '貯蓄' ? 'selected' : '' }}
                                        >
                                            貯蓄
                                        </option>
                                    </select>

                                    @if ($errors->updateBusinessProfile->has('bank_account_type'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('bank_account_type') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field">
                                    <label
                                        for="bank-account-number"
                                        class="profile-form__label"
                                    >
                                        口座番号

                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="bank-account-number"
                                        type="text"
                                        name="bank_account_number"
                                        value="{{ old('bank_account_number', $businessProfile?->bank_account_number) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('bank_account_number') ? ' is-invalid' : '' }}"
                                        maxlength="20"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        required
                                    >

                                    <p class="profile-form__help">
                                        口座番号は暗号化して保存されます。
                                    </p>

                                    @if ($errors->updateBusinessProfile->has('bank_account_number'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('bank_account_number') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="profile-form__field profile-form__field--span-2">
                                    <label
                                        for="bank-account-holder"
                                        class="profile-form__label"
                                    >
                                        口座名義

                                        <span class="profile-form__required">
                                            必須
                                        </span>
                                    </label>

                                    <input
                                        id="bank-account-holder"
                                        type="text"
                                        name="bank_account_holder"
                                        value="{{ old('bank_account_holder', $businessProfile?->bank_account_holder) }}"
                                        class="profile-form__input{{ $errors->updateBusinessProfile->has('bank_account_holder') ? ' is-invalid' : '' }}"
                                        maxlength="255"
                                        autocomplete="off"
                                        placeholder="ヤマダ タロウ"
                                        required
                                    >

                                    @if ($errors->updateBusinessProfile->has('bank_account_holder'))
                                        <p class="profile-form__error">
                                            {{ $errors->updateBusinessProfile->first('bank_account_holder') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </section>

                        <div class="profile-form__actions">
                            <button
                                type="submit"
                                class="profile-form__submit"
                            >
                                事業者情報を保存
                            </button>
                        </div>
                    </form>
                </div>
            </details>
        </section>
    </div>
@endsection