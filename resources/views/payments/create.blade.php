@extends('layouts.app')

@section('title', '決済内容確認 | ClientWork Manager')
@section('page-label', 'Payment')
@section('page-title', '決済内容確認')
@section('page-description', '案件の支払内容を確認して、Stripe決済へ進みます。')

@section('content')
    <section class="page-section payment-create-page">
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
                決済内容確認
            </span>
        </div>

        @if (session('success'))
            <div class="payment-create-flash payment-create-flash--success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="payment-create-alert payment-create-alert--danger payment-create-error-summary" role="alert">
                <h2 class="payment-create-alert__title">
                    決済準備を作成できませんでした
                </h2>

                <ul class="payment-create-alert__list">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="payment-create-layout">
            <main class="payment-create-main">
                <section class="payment-create-card">
                    <div class="payment-create-card__header">
                        <div>
                            <p class="payment-create-card__eyebrow">
                                PAYMENT SUMMARY
                            </p>

                            <h2 class="payment-create-card__title">
                                お支払い内容
                            </h2>
                        </div>

                        <span class="payment-create-card__status">
                            未決済
                        </span>
                    </div>

                    <dl class="payment-summary-list">
                        <div class="payment-summary-list__item">
                            <dt>
                                案件名
                            </dt>

                            <dd>
                                {{ $project->title }}
                            </dd>
                        </div>

                        <div class="payment-summary-list__item">
                            <dt>
                                顧客名
                            </dt>

                            <dd>
                                {{ $customerName }}
                            </dd>
                        </div>

                        <div class="payment-summary-list__item">
                            <dt>
                                決済説明
                            </dt>

                            <dd>
                                {{ $paymentDescription }}
                            </dd>
                        </div>

                        <div class="payment-summary-list__item payment-summary-list__item--amount">
                            <dt>
                                支払金額
                            </dt>

                            <dd>
                                @if ($amount !== null)
                                    ¥{{ number_format($amount) }}
                                @else
                                    未設定
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="payment-create-card">
                    <div class="payment-create-card__header">
                        <div>
                            <p class="payment-create-card__eyebrow">
                                NEXT STEP
                            </p>

                            <h2 class="payment-create-card__title">
                                決済手続き
                            </h2>
                        </div>
                    </div>

                    @if ($isPayable)
                        <p class="payment-create-card__description">
                            内容に問題がなければ、Stripeの安全な決済入力画面へ進みます。
                        </p>

                        <form action="{{ route('projects.payments.store', $project) }}" method="POST"
                            class="payment-create-form">
                            @csrf

                            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

                            <button type="submit" class="button button--primary payment-create-submit">
                                決済手続きへ進む
                            </button>
                        </form>

                        <p class="payment-create-card__note">
                            この操作では決済準備を作成します。カードへの請求はまだ確定されません。
                        </p>
                    @else
                        <div class="payment-create-alert payment-create-alert--danger" role="alert">
                            <h3 class="payment-create-alert__title">
                                現在、この案件は決済できません
                            </h3>

                            <p class="payment-create-alert__text">
                                見積金額が未設定、50円未満、または決済上限を超えています。
                                案件編集画面で見積金額を確認してください。
                            </p>
                        </div>

                        <a href="{{ route('projects.edit', $project) }}" class="button button--secondary">
                            案件金額を編集する
                        </a>
                    @endif
                </section>
            </main>

            <aside class="payment-create-side">
                <section class="payment-security-card">
                    <div class="payment-security-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="4" y="10" width="16" height="11" rx="2"></rect>

                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                        </svg>
                    </div>

                    <div>
                        <h2 class="payment-security-card__title">
                            Stripeによる安全な決済
                        </h2>

                        <p class="payment-security-card__text">
                            カード番号などの支払い情報は、Laravel側では保存しません。
                            次の決済画面ではStripe Payment Elementを使用します。
                        </p>
                    </div>
                </section>

                <section class="payment-security-card">
                    <h2 class="payment-security-card__title">
                        金額の安全性
                    </h2>

                    <p class="payment-security-card__text">
                        支払金額はブラウザの入力値ではなく、
                        サーバーに保存されている案件の見積金額から決定されます。
                    </p>
                </section>

                <a href="{{ route('projects.show', $project) }}" class="payment-create-back-link">
                    <span aria-hidden="true">
                        ←
                    </span>

                    案件詳細へ戻る
                </a>
            </aside>
        </div>
    </section>
@endsection