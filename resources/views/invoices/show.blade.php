@extends('layouts.app')

@section('title', '請求書詳細 | ClientWork Manager')
@section('page-label', 'Invoice Details')
@section('page-title', '請求書詳細')
@section('page-description', '請求内容、請求先、発行日、支払期限、入金日時を確認できます。')

@section('page-actions')
    <div class="invoice-page-actions">
        @can('send', $invoice)
            <a href="{{ route('invoices.mail.create', $invoice) }}" class="button button--primary invoice-page-actions__send">
                メールで送信
            </a>
        @endcan

        @can('update', $invoice)
            <a href="{{ route('invoices.edit', $invoice) }}" class="button button--secondary invoice-page-actions__edit">
                請求書を編集
            </a>
        @endcan

        <a href="{{ route('invoices.pdf.preview', $invoice) }}"
            class="button button--secondary invoice-page-actions__preview" target="_blank" rel="noopener">
            PDFをプレビュー
        </a>

        <a href="{{ route('invoices.pdf.download', $invoice) }}"
            class="button button--secondary invoice-page-actions__download">
            PDFをダウンロード
        </a>
    </div>
@endsection

@section('content')
    @php
        $statusLabels = [
            \App\Models\Invoice::STATUS_DRAFT => '下書き',
            \App\Models\Invoice::STATUS_SENT => '送信済み',
            \App\Models\Invoice::STATUS_PAID => '入金済み',
            \App\Models\Invoice::STATUS_OVERDUE => '期限超過',
            \App\Models\Invoice::STATUS_CANCELED => 'キャンセル',
        ];

        $statusClasses = [
            \App\Models\Invoice::STATUS_DRAFT => 'invoice-detail-status--draft',
            \App\Models\Invoice::STATUS_SENT => 'invoice-detail-status--sent',
            \App\Models\Invoice::STATUS_PAID => 'invoice-detail-status--paid',
            \App\Models\Invoice::STATUS_OVERDUE => 'invoice-detail-status--overdue',
            \App\Models\Invoice::STATUS_CANCELED => 'invoice-detail-status--canceled',
        ];

        $statusLabel = $statusLabels[$invoice->status]
            ?? $invoice->status;

        $statusClass = $statusClasses[$invoice->status]
            ?? 'invoice-detail-status--default';

        $issuedAt = $invoice->issued_at?->format('Y年n月j日')
            ?? '未設定';

        $dueDate = $invoice->due_date?->format('Y年n月j日')
            ?? '未設定';

        $paidAt = $invoice->paid_at?->format('Y年n月j日 H:i')
            ?? '未入金';

        $createdAt = $invoice->created_at?->format('Y年n月j日 H:i')
            ?? '未記録';

        $updatedAt = $invoice->updated_at?->format('Y年n月j日 H:i')
            ?? '未記録';

        $paymentsCount = (int) ($invoice->payments_count ?? 0);
    @endphp

    <section class="invoice-detail-page" aria-labelledby="invoice-detail-title">
        <nav class="invoice-breadcrumb" aria-label="パンくずリスト">
            <ol class="invoice-breadcrumb__list">
                <li class="invoice-breadcrumb__item">
                    <a href="{{ route('invoices.index') }}" class="invoice-breadcrumb__link">
                        請求書一覧
                    </a>
                </li>

                <li class="invoice-breadcrumb__separator" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="m7 4 6 6-6 6"></path>
                    </svg>
                </li>

                <li class="invoice-breadcrumb__item invoice-breadcrumb__item--current" aria-current="page">
                    請求書詳細
                </li>
            </ol>
        </nav>

        @if (session('success'))
            <div class="invoice-detail-flash invoice-detail-flash--success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="invoice-detail-flash invoice-detail-flash--error" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <header class="invoice-detail-hero">
            <div class="invoice-detail-hero__content">
                <div class="invoice-detail-hero__meta">
                    <span class="invoice-detail-hero__label">
                        Invoice
                    </span>

                    <span class="invoice-detail-status {{ $statusClass }}" aria-label="請求書ステータス：{{ $statusLabel }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <p class="invoice-detail-hero__number">
                    {{ $invoice->invoice_number }}
                </p>

                <h2 id="invoice-detail-title" class="invoice-detail-hero__title">
                    {{ $invoice->title }}
                </h2>

                <p class="invoice-detail-hero__description">
                    請求書に保存された請求先情報と金額を確認できます。
                </p>
            </div>

            <div class="invoice-detail-hero__amount">
                <span class="invoice-detail-hero__amount-label">
                    ご請求金額
                </span>

                <strong class="invoice-detail-hero__amount-value">
                    ¥{{ number_format($invoice->total_amount) }}
                </strong>

                <span class="invoice-detail-hero__currency">
                    {{ strtoupper($invoice->currency) }}
                </span>
            </div>
        </header>

        <div class="invoice-detail-layout">
            <main class="invoice-detail-main">
                <article class="invoice-detail-card">
                    <header class="invoice-detail-card__header">
                        <div>
                            <p class="invoice-detail-card__eyebrow">
                                Billing Details
                            </p>

                            <h3 class="invoice-detail-card__title">
                                請求内容
                            </h3>
                        </div>

                        <span class="invoice-detail-card__icon" aria-hidden="true">
                            ¥
                        </span>
                    </header>

                    <div class="invoice-detail-card__body">
                        <dl class="invoice-detail-definition">
                            <div class="invoice-detail-definition__row">
                                <dt class="invoice-detail-definition__term">
                                    案件
                                </dt>

                                <dd class="invoice-detail-definition__value">
                                    @if ($invoice->project)
                                        <a href="{{ route('projects.show', $invoice->project) }}" class="invoice-detail-link">
                                            {{ $invoice->project->title }}
                                        </a>
                                    @else
                                        <span class="invoice-detail-empty">
                                            案件情報なし
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div class="invoice-detail-definition__row">
                                <dt class="invoice-detail-definition__term">
                                    請求書タイトル
                                </dt>

                                <dd class="invoice-detail-definition__value">
                                    {{ $invoice->title }}
                                </dd>
                            </div>

                            <div class="invoice-detail-definition__row invoice-detail-definition__row--block">
                                <dt class="invoice-detail-definition__term">
                                    請求内容・補足
                                </dt>

                                <dd class="invoice-detail-definition__value invoice-detail-definition__value--description">
                                    {!! nl2br(e(
        $invoice->description
        ?: '補足情報はありません。'
    )) !!}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </article>

                <article class="invoice-detail-card">
                    <header class="invoice-detail-card__header">
                        <div>
                            <p class="invoice-detail-card__eyebrow">
                                Billing Address
                            </p>

                            <h3 class="invoice-detail-card__title">
                                請求先情報
                            </h3>
                        </div>

                        <span class="invoice-detail-card__icon" aria-hidden="true">
                            宛
                        </span>
                    </header>

                    <div class="invoice-detail-card__body">
                        <div class="invoice-detail-billing">
                            <div class="invoice-detail-billing__primary">
                                <span class="invoice-detail-billing__label">
                                    請求先
                                </span>

                                <strong class="invoice-detail-billing__name">
                                    {{ $invoice->billing_name ?: '未登録' }}
                                </strong>

                                @if ($invoice->billing_contact_name)
                                    <span class="invoice-detail-billing__contact">
                                        {{ $invoice->billing_contact_name }} 様
                                    </span>
                                @endif
                            </div>

                            <dl class="invoice-detail-contact-list">
                                <div class="invoice-detail-contact-list__item">
                                    <dt class="invoice-detail-contact-list__term">
                                        メール
                                    </dt>

                                    <dd class="invoice-detail-contact-list__value">
                                        @if ($invoice->billing_email)
                                            <a href="mailto:{{ $invoice->billing_email }}"
                                                class="invoice-detail-link invoice-detail-link--wrap">
                                                {{ $invoice->billing_email }}
                                            </a>
                                        @else
                                            <span class="invoice-detail-empty">
                                                未登録
                                            </span>
                                        @endif
                                    </dd>
                                </div>

                                <div class="invoice-detail-contact-list__item">
                                    <dt class="invoice-detail-contact-list__term">
                                        電話番号
                                    </dt>

                                    <dd class="invoice-detail-contact-list__value">
                                        @if ($invoice->billing_phone)
                                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $invoice->billing_phone) }}"
                                                class="invoice-detail-link">
                                                {{ $invoice->billing_phone }}
                                            </a>
                                        @else
                                            <span class="invoice-detail-empty">
                                                未登録
                                            </span>
                                        @endif
                                    </dd>
                                </div>

                                <div class="invoice-detail-contact-list__item">
                                    <dt class="invoice-detail-contact-list__term">
                                        住所
                                    </dt>

                                    <dd
                                        class="invoice-detail-contact-list__value invoice-detail-contact-list__value--address">
                                        @if (
                                                $invoice->billing_postal_code
                                                || $invoice->billing_address
                                            )
                                            @if ($invoice->billing_postal_code)
                                                <span>
                                                    〒{{ $invoice->billing_postal_code }}
                                                </span>
                                            @endif

                                            @if ($invoice->billing_address)
                                                <span>
                                                    {{ $invoice->billing_address }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="invoice-detail-empty">
                                                未登録
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>

                            <p class="invoice-detail-billing__note">
                                請求書作成時点の顧客情報を表示しています。
                                顧客情報を後から変更しても、この請求先情報は変わりません。
                            </p>
                        </div>
                    </div>
                </article>
            </main>

            <aside class="invoice-detail-sidebar">
                <section class="invoice-detail-summary" aria-labelledby="invoice-detail-summary-title">
                    <header class="invoice-detail-summary__header">
                        <div>
                            <p class="invoice-detail-summary__eyebrow">
                                Payment Summary
                            </p>

                            <h3 id="invoice-detail-summary-title" class="invoice-detail-summary__title">
                                金額内訳
                            </h3>
                        </div>

                        <span class="invoice-detail-summary__icon" aria-hidden="true">
                            ¥
                        </span>
                    </header>

                    <dl class="invoice-detail-summary__list">
                        <div class="invoice-detail-summary__row">
                            <dt>
                                小計
                            </dt>

                            <dd>
                                ¥{{ number_format($invoice->subtotal) }}
                            </dd>
                        </div>

                        <div class="invoice-detail-summary__row">
                            <dt>
                                消費税
                            </dt>

                            <dd>
                                ¥{{ number_format($invoice->tax_amount) }}
                            </dd>
                        </div>

                        <div class="invoice-detail-summary__row invoice-detail-summary__row--total">
                            <dt>
                                合計
                            </dt>

                            <dd>
                                ¥{{ number_format($invoice->total_amount) }}
                            </dd>
                        </div>
                    </dl>

                    <p class="invoice-detail-summary__note">
                        保存時にサーバー側で計算された金額です。
                    </p>
                </section>

                <section class="invoice-detail-side-card">
                    <header class="invoice-detail-side-card__header">
                        <h3 class="invoice-detail-side-card__title">
                            発行・支払情報
                        </h3>
                    </header>

                    <dl class="invoice-detail-side-list">
                        <div class="invoice-detail-side-list__item">
                            <dt>
                                発行日
                            </dt>

                            <dd>
                                {{ $issuedAt }}
                            </dd>
                        </div>

                        <div class="invoice-detail-side-list__item">
                            <dt>
                                支払期限
                            </dt>

                            <dd>
                                {{ $dueDate }}
                            </dd>
                        </div>

                        <div class="invoice-detail-side-list__item">
                            <dt>
                                入金日時
                            </dt>

                            <dd>
                                @if ($invoice->paid_at)
                                    <time datetime="{{ $invoice->paid_at->toIso8601String() }}">
                                        {{ $paidAt }}
                                    </time>
                                @else
                                    <span class="invoice-detail-empty">
                                        未入金
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div class="invoice-detail-side-list__item">
                            <dt>
                                通貨
                            </dt>

                            <dd>
                                {{ strtoupper($invoice->currency) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="invoice-detail-side-card">
                    <header class="invoice-detail-side-card__header">
                        <h3 class="invoice-detail-side-card__title">
                            関連情報
                        </h3>
                    </header>

                    <div class="invoice-detail-related-links">
                        @if ($invoice->project)
                            <a href="{{ route('projects.show', $invoice->project) }}" class="invoice-detail-related-link">
                                <span>
                                    関連案件を確認
                                </span>

                                <span aria-hidden="true">
                                    →
                                </span>
                            </a>
                        @endif

                        @if ($invoice->customer)
                            <a href="{{ route('customers.show', $invoice->customer) }}" class="invoice-detail-related-link">
                                <span>
                                    顧客詳細を確認
                                </span>

                                <span aria-hidden="true">
                                    →
                                </span>
                            </a>
                        @endif
                    </div>
                </section>

                <section class="invoice-detail-side-card">
                    <header class="invoice-detail-side-card__header">
                        <h3 class="invoice-detail-side-card__title">
                            管理情報
                        </h3>
                    </header>

                    <dl class="invoice-detail-side-list">
                        <div class="invoice-detail-side-list__item">
                            <dt>
                                作成日時
                            </dt>

                            <dd>
                                {{ $createdAt }}
                            </dd>
                        </div>

                        <div class="invoice-detail-side-list__item">
                            <dt>
                                更新日時
                            </dt>

                            <dd>
                                {{ $updatedAt }}
                            </dd>
                        </div>

                        <div class="invoice-detail-side-list__item">
                            <dt>
                                送信回数
                            </dt>

                            <dd>
                                {{ number_format($invoice->send_count) }}回
                            </dd>
                        </div>

                        <div class="invoice-detail-side-list__item">
                            <dt>
                                決済履歴
                            </dt>

                            <dd>
                                {{ number_format($paymentsCount) }}件
                            </dd>
                        </div>
                    </dl>
                </section>

                @can('delete', $invoice)
                    <section class="invoice-detail-danger-card" aria-labelledby="invoice-delete-title">
                        <header class="invoice-detail-danger-card__header">
                            <div>
                                <p class="invoice-detail-danger-card__eyebrow">
                                    Danger Zone
                                </p>

                                <h3 id="invoice-delete-title" class="invoice-detail-danger-card__title">
                                    下書きの削除
                                </h3>
                            </div>
                        </header>

                        <div class="invoice-detail-danger-card__body">
                            @if ($paymentsCount === 0)
                                <p class="invoice-detail-danger-card__description">
                                    この操作を実行すると、請求書を元に戻すことはできません。
                                    削除できるのは未送信の下書きだけです。
                                </p>

                                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                                    class="invoice-detail-delete-form" data-invoice-delete-form
                                    data-confirm-message="請求書「{{ $invoice->title }}」を削除します。この操作は元に戻せません。よろしいですか？">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="invoice-detail-delete-button">
                                        この下書きを削除
                                    </button>
                                </form>
                            @else
                                <div class="invoice-detail-danger-card__blocked" role="status">
                                    <strong>
                                        この請求書は削除できません
                                    </strong>

                                    <p>
                                        決済履歴が
                                        {{ number_format($paymentsCount) }}件
                                        紐づいているため、請求書を保存する必要があります。
                                    </p>
                                </div>
                            @endif
                        </div>
                    </section>
                @endcan
            </aside>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deleteForms = document.querySelectorAll(
                '[data-invoice-delete-form]'
            );

            deleteForms.forEach((form) => {
                form.addEventListener('submit', (event) => {
                    const message = form.dataset.confirmMessage
                        ?? 'この請求書を削除します。よろしいですか？';

                    const confirmed = window.confirm(message);

                    if (!confirmed) {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
@endsection