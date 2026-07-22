@extends('layouts.app')

@section('title', '請求書一覧 | ClientWork Manager')
@section('page-label', 'Invoice Management')
@section('page-title', '請求書管理')
@section('page-description', '案件ごとの請求金額、発行日、支払期限、入金状況を確認できます。')

@section('content')
    <section class="invoice-index">
        @if (session('success'))
            <div class="invoice-index__flash" role="status">
                {{ session('success') }}
            </div>
        @endif

        <div class="invoice-index__summary">
            <div class="invoice-index__summary-body">
                <p class="invoice-index__eyebrow">
                    Invoice List
                </p>

                <h2 class="invoice-index__heading">
                    請求書一覧
                </h2>

                <p class="invoice-index__description">
                    発行済みの請求書と、入金状況・支払期限をまとめて確認できます。
                </p>
            </div>

            <div class="invoice-index__count">
                <span class="invoice-index__count-label">
                    登録件数
                </span>

                <strong class="invoice-index__count-value">
                    {{ number_format($invoices->total()) }}
                </strong>

                <span class="invoice-index__count-unit">
                    件
                </span>
            </div>
        </div>

        @if ($invoices->isEmpty())
            <div class="invoice-empty">
                <div class="invoice-empty__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M6 2.75h9l3 3V21.25H6z"></path>
                        <path d="M15 2.75v3h3"></path>
                        <path d="M9 10h6"></path>
                        <path d="M9 14h6"></path>
                        <path d="M9 18h3"></path>
                    </svg>
                </div>

                <h3 class="invoice-empty__title">
                    請求書はまだ登録されていません
                </h3>

                <p class="invoice-empty__text">
                    請求書を作成すると、案件ごとの請求金額、発行日、支払期限、入金状況を管理できます。
                </p>
            </div>
        @else
            @php
                $statusLabels = [
                    \App\Models\Invoice::STATUS_DRAFT => '下書き',
                    \App\Models\Invoice::STATUS_SENT => '送信済み',
                    \App\Models\Invoice::STATUS_PAID => '入金済み',
                    \App\Models\Invoice::STATUS_OVERDUE => '期限超過',
                    \App\Models\Invoice::STATUS_CANCELED => 'キャンセル',
                ];

                $statusDescriptions = [
                    \App\Models\Invoice::STATUS_DRAFT => '編集中',
                    \App\Models\Invoice::STATUS_SENT => '入金待ち',
                    \App\Models\Invoice::STATUS_PAID => '支払完了',
                    \App\Models\Invoice::STATUS_OVERDUE => '期限を超過',
                    \App\Models\Invoice::STATUS_CANCELED => '処理終了',
                ];

                $formatInvoiceAmount = static function (int $amount, string $currency): string {
                    if (strtolower($currency) === 'jpy') {
                        return '¥' . number_format($amount);
                    }

                    return strtoupper($currency) . ' ' . number_format($amount);
                };
            @endphp

            {{-- =====================================
            PC・ノートPC用テーブル
            ====================================== --}}
            <div class="invoice-table-panel">
                <div class="invoice-table-panel__scroll">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    請求書
                                </th>

                                <th scope="col">
                                    案件
                                </th>

                                <th scope="col">
                                    顧客
                                </th>

                                <th scope="col">
                                    ステータス
                                </th>

                                <th scope="col" class="invoice-table__date-heading">
                                    発行日
                                </th>

                                <th scope="col" class="invoice-table__date-heading">
                                    支払期限
                                </th>

                                <th scope="col" class="invoice-table__amount-heading">
                                    請求金額
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($invoices as $invoice)
                                            <tr class="invoice-table__clickable-row" tabindex="0" role="link"
                                                aria-label="請求書 {{ $invoice->invoice_number }} の詳細を開く" data-invoice-navigation
                                                data-invoice-url="{{ route('invoices.show', $invoice) }}">
                                                <td>
                                                    <div class="invoice-table__primary">
                                                        <span class="invoice-table__document-icon" aria-hidden="true">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                                                stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M6 2.75h9l3 3V21.25H6z"></path>
                                                                <path d="M15 2.75v3h3"></path>
                                                                <path d="M9 10h6"></path>
                                                                <path d="M9 14h6"></path>
                                                                <path d="M9 18h3"></path>
                                                            </svg>
                                                        </span>

                                                        <div class="invoice-table__primary-body">
                                                            <p class="invoice-table__number">
                                                                {{ $invoice->invoice_number }}
                                                            </p>

                                                            <p class="invoice-table__title">
                                                                {{ $invoice->title }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    @if ($invoice->project)
                                                        <span class="invoice-table__value">
                                                            {{ $invoice->project->title }}
                                                        </span>
                                                    @else
                                                        <span class="invoice-table__muted">
                                                            案件削除済み
                                                        </span>
                                                    @endif
                                                </td>

                                                <td>
                                                    @if ($invoice->customer)
                                                        <span class="invoice-table__value">
                                                            {{ $invoice->customer->name }}
                                                        </span>
                                                    @else
                                                        <span class="invoice-table__muted">
                                                            顧客削除済み
                                                        </span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div class="invoice-status">
                                                        <span class="invoice-status__badge invoice-status__badge--{{ $invoice->status }}">
                                                            {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                                                        </span>

                                                        <span class="invoice-status__description">
                                                            {{ $statusDescriptions[$invoice->status] ?? '状態を確認してください' }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <td class="invoice-table__date-cell">
                                                    <time datetime="{{ $invoice->issued_at->toDateString() }}" class="invoice-table__date">
                                                        {{ $invoice->issued_at->format('Y/m/d') }}
                                                    </time>
                                                </td>

                                                <td class="invoice-table__date-cell">
                                                    <time datetime="{{ $invoice->due_date->toDateString() }}" class="invoice-table__date">
                                                        {{ $invoice->due_date->format('Y/m/d') }}
                                                    </time>
                                                </td>

                                                <td class="invoice-table__amount-cell">
                                                    <strong class="invoice-table__amount">
                                                        {{ $formatInvoiceAmount(
                                    $invoice->total_amount,
                                    $invoice->currency
                                ) }}
                                                    </strong>
                                                </td>
                                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- =====================================
            スマートフォン用カード
            ====================================== --}}
            <div class="invoice-card-list">
                @foreach ($invoices as $invoice)
                    <article class="invoice-card invoice-card--clickable" tabindex="0" role="link"
                        aria-label="請求書 {{ $invoice->invoice_number }} の詳細を開く" data-invoice-navigation
                        data-invoice-url="{{ route('invoices.show', $invoice) }}">
                        <header class="invoice-card__header">
                            <div class="invoice-card__identity">
                                <span class="invoice-card__document-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 2.75h9l3 3V21.25H6z"></path>
                                        <path d="M15 2.75v3h3"></path>
                                        <path d="M9 10h6"></path>
                                        <path d="M9 14h6"></path>
                                        <path d="M9 18h3"></path>
                                    </svg>
                                </span>

                                <div class="invoice-card__heading">
                                    <p class="invoice-card__number">
                                        {{ $invoice->invoice_number }}
                                    </p>

                                    <h3 class="invoice-card__title">
                                        {{ $invoice->title }}
                                    </h3>
                                </div>
                            </div>

                            <span class="invoice-status__badge invoice-status__badge--{{ $invoice->status }}">
                                {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                            </span>
                        </header>

                        <div class="invoice-card__amount">
                            <span class="invoice-card__amount-label">
                                請求金額
                            </span>

                            <strong class="invoice-card__amount-value">
                                {{ $formatInvoiceAmount(
                        $invoice->total_amount,
                        $invoice->currency
                    ) }}
                            </strong>
                        </div>

                        <dl class="invoice-card__details">
                            <div class="invoice-card__detail">
                                <dt>
                                    案件
                                </dt>

                                <dd>
                                    @if ($invoice->project)
                                        {{ $invoice->project->title }}
                                    @else
                                        <span class="invoice-card__muted">
                                            案件削除済み
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div class="invoice-card__detail">
                                <dt>
                                    顧客
                                </dt>

                                <dd>
                                    @if ($invoice->customer)
                                        {{ $invoice->customer->name }}
                                    @else
                                        <span class="invoice-card__muted">
                                            顧客削除済み
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div class="invoice-card__detail">
                                <dt>
                                    発行日
                                </dt>

                                <dd>
                                    <time datetime="{{ $invoice->issued_at->toDateString() }}">
                                        {{ $invoice->issued_at->format('Y/m/d') }}
                                    </time>
                                </dd>
                            </div>

                            <div class="invoice-card__detail">
                                <dt>
                                    支払期限
                                </dt>

                                <dd>
                                    <time datetime="{{ $invoice->due_date->toDateString() }}">
                                        {{ $invoice->due_date->format('Y/m/d') }}
                                    </time>
                                </dd>
                            </div>
                        </dl>

                        <footer class="invoice-card__footer">
                            <span class="invoice-card__status-description">
                                {{ $statusDescriptions[$invoice->status] ?? '状態を確認してください' }}
                            </span>

                            <span class="invoice-card__created-at">
                                登録日

                                <time datetime="{{ $invoice->created_at->toDateString() }}">
                                    {{ $invoice->created_at->format('Y/m/d') }}
                                </time>
                            </span>
                        </footer>
                    </article>
                @endforeach
            </div>

            {{-- =====================================
            ページネーション
            ====================================== --}}
            @if ($invoices->hasPages())
                @php
                    $startPage = max(
                        1,
                        $invoices->currentPage() - 2
                    );

                    $endPage = min(
                        $invoices->lastPage(),
                        $invoices->currentPage() + 2
                    );
                @endphp

                <nav class="invoice-pagination" aria-label="請求書一覧のページネーション">
                    <p class="invoice-pagination__summary">
                        全{{ number_format($invoices->total()) }}件中

                        <strong>
                            {{ number_format($invoices->firstItem()) }}
                        </strong>

                        〜

                        <strong>
                            {{ number_format($invoices->lastItem()) }}
                        </strong>

                        件を表示
                    </p>

                    <div class="invoice-pagination__controls">
                        @if ($invoices->onFirstPage())
                            <span class="invoice-pagination__button invoice-pagination__button--disabled" aria-disabled="true">
                                <span aria-hidden="true">
                                    ←
                                </span>

                                <span>
                                    前へ
                                </span>
                            </span>
                        @else
                            <a href="{{ $invoices->previousPageUrl() }}" class="invoice-pagination__button" rel="prev">
                                <span aria-hidden="true">
                                    ←
                                </span>

                                <span>
                                    前へ
                                </span>
                            </a>
                        @endif

                        <div class="invoice-pagination__pages">
                            @if ($startPage > 1)
                                <a href="{{ $invoices->url(1) }}" class="invoice-pagination__page">
                                    1
                                </a>

                                @if ($startPage > 2)
                                    <span class="invoice-pagination__ellipsis" aria-hidden="true">
                                        …
                                    </span>
                                @endif
                            @endif

                            @foreach ($invoices->getUrlRange($startPage, $endPage) as $page => $url)
                                @if ($page === $invoices->currentPage())
                                    <span class="invoice-pagination__page invoice-pagination__page--current" aria-current="page">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="invoice-pagination__page">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach

                            @if ($endPage < $invoices->lastPage())
                                @if ($endPage < $invoices->lastPage() - 1)
                                    <span class="invoice-pagination__ellipsis" aria-hidden="true">
                                        …
                                    </span>
                                @endif

                                <a href="{{ $invoices->url($invoices->lastPage()) }}" class="invoice-pagination__page">
                                    {{ $invoices->lastPage() }}
                                </a>
                            @endif
                        </div>

                        @if ($invoices->hasMorePages())
                            <a href="{{ $invoices->nextPageUrl() }}" class="invoice-pagination__button" rel="next">
                                <span>
                                    次へ
                                </span>

                                <span aria-hidden="true">
                                    →
                                </span>
                            </a>
                        @else
                            <span class="invoice-pagination__button invoice-pagination__button--disabled" aria-disabled="true">
                                <span>
                                    次へ
                                </span>

                                <span aria-hidden="true">
                                    →
                                </span>
                            </span>
                        @endif
                    </div>
                </nav>
            @endif
        @endif
    </section>

    <style>
        .invoice-table__clickable-row,
        .invoice-card--clickable {
            cursor: pointer;
        }

        .invoice-table__clickable-row:focus-visible {
            position: relative;
            z-index: 1;
            outline: 3px solid rgb(37 99 235 / 24%);
            outline-offset: -3px;
        }

        .invoice-card--clickable:focus-visible {
            outline: 3px solid rgb(37 99 235 / 24%);
            outline-offset: 3px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navigationElements = document.querySelectorAll(
                '[data-invoice-navigation]'
            );

            const interactiveSelector = [
                'a',
                'button',
                'input',
                'select',
                'textarea',
                'label',
                'summary',
                '[role="button"]',
                '[data-navigation-ignore]',
            ].join(',');

            const openInvoice = (element, event = null) => {
                const url = element.dataset.invoiceUrl;

                if (!url) {
                    return;
                }

                if (
                    event instanceof MouseEvent
                    && event.target instanceof Element
                    && event.target.closest(interactiveSelector)
                ) {
                    return;
                }

                if (
                    event instanceof MouseEvent
                    && event.button !== 0
                ) {
                    return;
                }

                if (
                    event instanceof MouseEvent
                    && (
                        event.metaKey
                        || event.ctrlKey
                        || event.shiftKey
                        || event.altKey
                    )
                ) {
                    window.open(
                        url,
                        '_blank',
                        'noopener,noreferrer'
                    );

                    return;
                }

                window.location.assign(url);
            };

            navigationElements.forEach((element) => {
                element.addEventListener('click', (event) => {
                    openInvoice(element, event);
                });

                element.addEventListener('keydown', (event) => {
                    if (
                        event.target instanceof Element
                        && event.target.closest(interactiveSelector)
                    ) {
                        return;
                    }

                    if (
                        event.key !== 'Enter'
                        && event.key !== ' '
                    ) {
                        return;
                    }

                    event.preventDefault();

                    openInvoice(element);
                });
            });
        });
    </script>
@endsection