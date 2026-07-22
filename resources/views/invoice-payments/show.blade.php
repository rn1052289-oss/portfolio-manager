<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="robots" content="noindex, nofollow, noarchive">

    <title>
        請求書 {{ $invoice->invoice_number }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="invoice-payment-page">
    @php
        $statusLabel = match ($invoice->status) {
            \App\Models\Invoice::STATUS_SENT => 'お支払い受付中',
            \App\Models\Invoice::STATUS_OVERDUE => '支払期限超過',
            \App\Models\Invoice::STATUS_PAID => 'お支払い済み',
            \App\Models\Invoice::STATUS_CANCELED => 'キャンセル済み',
            default => '確認できません',
        };

        $statusClass = match ($invoice->status) {
            \App\Models\Invoice::STATUS_SENT => 'is-payable',
            \App\Models\Invoice::STATUS_OVERDUE => 'is-overdue',
            \App\Models\Invoice::STATUS_PAID => 'is-paid',
            \App\Models\Invoice::STATUS_CANCELED => 'is-canceled',
            default => '',
        };
    @endphp

    <main class="invoice-payment">
        <div class="invoice-payment__container">
            <header class="invoice-payment__header">
                <p class="invoice-payment__service-name">
                    ClientWork Manager
                </p>

                <h1 class="invoice-payment__heading">
                    オンライン請求書
                </h1>

                <p class="invoice-payment__description">
                    請求内容をご確認ください。
                </p>
            </header>

            @if (session('success'))
                <div class="invoice-payment-flash invoice-payment-flash--success" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="invoice-payment-flash invoice-payment-flash--error" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <section class="invoice-payment-card" aria-labelledby="invoice-payment-title">
                <div class="invoice-payment-card__top">
                    <div>
                        <p class="invoice-payment-card__issuer-label">
                            請求元
                        </p>

                        <h2 id="invoice-payment-title" class="invoice-payment-card__issuer">
                            {{ $invoice->issuer_business_name ?: '請求元情報未設定' }}
                        </h2>
                    </div>

                    <span class="invoice-payment-status {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <dl class="invoice-payment-details">
                    <div class="invoice-payment-details__row">
                        <dt>
                            請求書番号
                        </dt>

                        <dd>
                            {{ $invoice->invoice_number }}
                        </dd>
                    </div>

                    <div class="invoice-payment-details__row">
                        <dt>
                            請求内容
                        </dt>

                        <dd>
                            {{ $invoice->title }}
                        </dd>
                    </div>

                    <div class="invoice-payment-details__row">
                        <dt>
                            請求先
                        </dt>

                        <dd>
                            {{ $invoice->billing_name }}
                        </dd>
                    </div>

                    <div class="invoice-payment-details__row">
                        <dt>
                            発行日
                        </dt>

                        <dd>
                            {{ $invoice->issued_at?->format('Y年m月d日') }}
                        </dd>
                    </div>

                    <div class="invoice-payment-details__row">
                        <dt>
                            支払期限
                        </dt>

                        <dd>
                            {{ $invoice->due_date?->format('Y年m月d日') }}
                        </dd>
                    </div>
                </dl>

                <div class="invoice-payment-amount">
                    <p class="invoice-payment-amount__label">
                        ご請求金額
                    </p>

                    <p class="invoice-payment-amount__value">
                        ¥{{ number_format((int) $invoice->total_amount) }}
                    </p>

                    <p class="invoice-payment-amount__tax">
                        税込・{{ strtoupper($invoice->currency) }}
                    </p>
                </div>

                @if ($pageState === 'payable')
                    <section class="invoice-payment-message" aria-labelledby="invoice-payment-action-title">
                        @if ($invoice->isOverdue())
                            <h2 id="invoice-payment-action-title" class="invoice-payment-message__title">
                                支払期限を過ぎています
                            </h2>

                            <p class="invoice-payment-message__text">
                                支払期限を過ぎていますが、
                                この請求書はオンラインでお支払いいただけます。
                            </p>
                        @else
                            <h2 id="invoice-payment-action-title" class="invoice-payment-message__title">
                                お支払い手続き
                            </h2>

                            <p class="invoice-payment-message__text">
                                請求内容とご請求金額をご確認のうえ、
                                Stripeの安全な決済フォームからお支払いください。
                            </p>
                        @endif

                        @if ($paymentSetupError !== null)
                            <div class="invoice-payment-inline-error" role="alert">
                                {{ $paymentSetupError }}
                            </div>
                        @elseif ($payment === null)
                                        <form method="POST" action="{{ route('invoice-payments.store', [
                                'publicToken' => $invoice->public_token,
                            ]) }}" class="invoice-payment-start-form">
                                            @csrf

                                            <button type="submit" class="button button--primary invoice-payment-start-button">
                                                オンライン決済を開始する
                                            </button>
                                        </form>
                        @elseif ($canContinuePayment)
                            <div class="invoice-payment-checkout">
                                <div class="invoice-payment-checkout__header">
                                    <div>
                                        <p class="invoice-payment-checkout__eyebrow">
                                            SECURE PAYMENT
                                        </p>

                                        <h3 class="invoice-payment-checkout__title">
                                            お支払い情報
                                        </h3>
                                    </div>

                                    @if ($stripeStatusLabel !== null)
                                        <span class="invoice-payment-checkout__status">
                                            {{ $stripeStatusLabel }}
                                        </span>
                                    @endif
                                </div>

                                <p class="invoice-payment-checkout__description">
                                    カード情報を入力し、
                                    内容を確認してからお支払いを確定してください。
                                </p>

                                <form id="payment-form" class="invoice-payment-checkout__form" data-payment-checkout
                                    data-stripe-publishable-key="{{ $stripePublicKey }}"
                                    data-client-secret="{{ $clientSecret }}" data-return-url="{{ $returnUrl }}" novalidate>
                                    <div id="payment-element" class="invoice-payment-checkout__element"
                                        aria-label="Stripe決済情報入力欄"></div>

                                    <p id="payment-initialization-status" class="invoice-payment-checkout__note" role="status"
                                        aria-live="polite">
                                        Stripe Payment Elementを読み込んでいます。
                                    </p>

                                    <div id="payment-message" class="invoice-payment-checkout__error" role="alert"
                                        aria-live="polite" hidden></div>

                                    <button id="payment-submit" type="submit" class="invoice-payment-checkout__submit" disabled
                                        aria-disabled="true">
                                        <span id="payment-submit-text">
                                            支払いを確定する
                                        </span>

                                        <span id="payment-submit-spinner" class="invoice-payment-checkout__spinner"
                                            aria-hidden="true" hidden></span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="invoice-payment-processing" role="status">
                                <h3 class="invoice-payment-processing__title">
                                    決済状況を確認しています
                                </h3>

                                <p class="invoice-payment-processing__text">
                                    現在の状況：
                                    {{ $stripeStatusLabel ?? '確認中' }}
                                </p>

                                <p class="invoice-payment-processing__note">
                                    画面を再読み込みすると、最新の状況を確認できます。
                                    決済ボタンを繰り返し押す必要はありません。
                                </p>
                            </div>
                        @endif

                        <div class="invoice-payment-security-note">
                            <span class="invoice-payment-security-note__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4" y="10" width="16" height="11" rx="2"></rect>

                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                                </svg>
                            </span>

                            <p>
                                カード番号などの支払い情報は
                                ClientWork Managerには保存されず、
                                Stripeによって安全に処理されます。
                            </p>
                        </div>
                    </section>
                @elseif ($pageState === 'paid')
                    <section class="invoice-payment-result is-success" role="status">
                        <h2 class="invoice-payment-result__title">
                            お支払い済みです
                        </h2>

                        <p class="invoice-payment-result__text">
                            この請求書のお支払いは完了しています。
                            再度お支払いいただく必要はありません。
                        </p>

                        @if ($invoice->paid_at !== null)
                            <p class="invoice-payment-result__date">
                                入金確認日時：
                                {{ $invoice->paid_at->format('Y年m月d日 H:i') }}
                            </p>
                        @endif
                    </section>
                @elseif ($pageState === 'canceled')
                    <section class="invoice-payment-result is-canceled" role="status">
                        <h2 class="invoice-payment-result__title">
                            この請求書はキャンセルされています
                        </h2>

                        <p class="invoice-payment-result__text">
                            このページからお支払いいただくことはできません。
                            詳細は請求元へお問い合わせください。
                        </p>
                    </section>
                @endif
            </section>

            <footer class="invoice-payment__footer">
                <p>
                    このページでは、請求書の確認とオンライン決済を行えます。
                </p>
            </footer>
        </div>
    </main>
</body>

</html>