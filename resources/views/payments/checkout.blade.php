@extends('layouts.app')

@section('title', '決済情報入力 | ClientWork Manager')
@section('page-label', 'Payment')
@section('page-title', '決済情報入力')
@section('page-description', 'Stripeの安全な決済フォームからカード情報を入力します。')

@section('content')
    <section class="page-section payment-checkout-page">
        <div class="breadcrumb">
            <a href="{{ route('projects.index') }}">
                案件一覧
            </a>

            <span>/</span>

            <a href="{{ route('projects.show', $project) }}">
                案件詳細
            </a>

            <span>/</span>

            <span>
                決済情報入力
            </span>
        </div>

        @if (session('success'))
            <div class="payment-checkout-flash payment-checkout-flash--success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <div class="payment-checkout-layout">
            <aside class="payment-checkout-summary">
                <section class="payment-checkout-card">
                    <div class="payment-checkout-card__header">
                        <div>
                            <p class="payment-checkout-card__eyebrow">
                                PAYMENT SUMMARY
                            </p>

                            <h2 class="payment-checkout-card__title">
                                お支払い内容
                            </h2>
                        </div>

                        <span
                            class="payment-checkout-status payment-checkout-status--{{ str_replace('_', '-', $stripeStatus) }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <dl class="payment-checkout-summary-list">
                        <div class="payment-checkout-summary-list__item">
                            <dt>
                                案件名
                            </dt>

                            <dd>
                                {{ $project->title }}
                            </dd>
                        </div>

                        <div class="payment-checkout-summary-list__item">
                            <dt>
                                顧客名
                            </dt>

                            <dd>
                                {{ $customerName }}
                            </dd>
                        </div>

                        <div class="payment-checkout-summary-list__item">
                            <dt>
                                決済番号
                            </dt>

                            <dd>
                                #{{ $payment->id }}
                            </dd>
                        </div>

                        <div class="payment-checkout-summary-list__item payment-checkout-summary-list__item--amount">
                            <dt>
                                支払金額
                            </dt>

                            <dd>
                                ¥{{ number_format($payment->amount) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="payment-checkout-security">
                    <div class="payment-checkout-security__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="4" y="10" width="16" height="11" rx="2"></rect>

                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                        </svg>
                    </div>

                    <div>
                        <h2 class="payment-checkout-security__title">
                            Stripeによる安全な決済
                        </h2>

                        <p class="payment-checkout-security__text">
                            カード番号などの支払い情報はLaravelへ送信せず、
                            Stripe Payment Elementで安全に処理します。
                        </p>
                    </div>
                </section>

                <a href="{{ route('projects.show', $project) }}" class="payment-checkout-back-link">
                    <span aria-hidden="true">
                        ←
                    </span>

                    案件詳細へ戻る
                </a>
            </aside>

            <main class="payment-checkout-main">
                <section class="payment-checkout-card payment-checkout-form-card">
                    <div class="payment-checkout-card__header">
                        <div>
                            <p class="payment-checkout-card__eyebrow">
                                SECURE PAYMENT
                            </p>

                            <h2 class="payment-checkout-card__title">
                                お支払い情報
                            </h2>
                        </div>
                    </div>

                    @if ($canContinue)
                        <p class="payment-checkout-form-card__description">
                            下記のStripe決済フォームへカード情報を入力し、
                            支払い内容を確認してから決済を確定します。
                        </p>

                        <form id="payment-form" class="payment-checkout-form" data-payment-checkout
                            data-stripe-publishable-key="{{ $stripePublicKey }}" data-client-secret="{{ $clientSecret }}"
                            novalidate>
                            <div id="payment-element" class="payment-checkout-element" aria-label="Stripe決済情報入力欄"></div>

                            <p id="payment-initialization-status" class="payment-checkout-form-card__note" role="status"
                                aria-live="polite">
                                Stripe Payment Elementを読み込んでいます。
                            </p>

                            <div id="payment-message" class="payment-checkout-message" role="alert" aria-live="polite" hidden>
                            </div>

                            <button id="payment-submit" type="submit" class="button button--primary payment-checkout-submit"
                                disabled aria-disabled="true">
                                <span id="payment-submit-text">
                                    支払いを確定する
                                </span>

                                <span id="payment-submit-spinner" class="payment-checkout-spinner" aria-hidden="true"
                                    hidden></span>
                            </button>
                        </form>

                        <p class="payment-checkout-form-card__note">
                            このステップでは決済入力欄の表示確認まで行います。
                            実際の決済確定処理は次のステップで実装します。
                        </p>
                    @else
                        <div class="payment-checkout-state payment-checkout-state--warning" role="status">
                            <h3 class="payment-checkout-state__title">
                                現在は決済情報を入力できません
                            </h3>

                            <p class="payment-checkout-state__text">
                                現在の決済状況は「{{ $statusLabel }}」です。
                                決済状況を確認してから、もう一度お試しください。
                            </p>
                        </div>
                    @endif
                </section>

                <section class="payment-checkout-guide">
                    <h2 class="payment-checkout-guide__title">
                        お支払い前の確認
                    </h2>

                    <ul class="payment-checkout-guide__list">
                        <li>
                            表示されている案件名と支払金額を確認してください。
                        </li>

                        <li>
                            テスト環境ではStripeのテストカードを使用してください。
                        </li>

                        <li>
                            決済ボタンを押した後は、処理が完了するまで画面を閉じないでください。
                        </li>
                    </ul>
                </section>
            </main>
        </div>
    </section>
@endsection