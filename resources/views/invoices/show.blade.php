@extends('layouts.app')

@section('title', '請求書詳細 | ClientWork Manager')
@section('page-label', '請求書')
@section('page-title', '請求書詳細')
@section('page-description', '請求内容、請求先、発行日、支払期限、入金状況を確認できます。')

@section('page-actions')
    <div class="invoice-page-actions">
        @can('send', $invoice)
            <a href="{{ route('invoices.mail.create', $invoice) }}" class="button button--primary">
                メールで送信
            </a>
        @endcan

        @can('update', $invoice)
            <a href="{{ route('invoices.edit', $invoice) }}" class="button button--secondary">
                請求書を編集
            </a>
        @endcan

        <a href="{{ route('invoices.pdf.preview', $invoice) }}" class="button button--secondary" target="_blank"
            rel="noopener">
            PDFをプレビュー
        </a>

        <a href="{{ route('invoices.pdf.download', $invoice) }}" class="button button--secondary">
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
                <li>
                    <a href="{{ route('invoices.index') }}" class="invoice-breadcrumb__link">
                        請求書一覧
                    </a>
                </li>

                <li class="invoice-breadcrumb__separator" aria-hidden="true">
                    ›
                </li>

                <li class="invoice-breadcrumb__current" aria-current="page">
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

        <header class="invoice-overview">
            <div class="invoice-overview__main">
                <div class="invoice-overview__top">
                    <span class="invoice-overview__number">
                        {{ $invoice->invoice_number }}
                    </span>

                    <span class="invoice-detail-status {{ $statusClass }}" aria-label="請求書ステータス：{{ $statusLabel }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <h2 id="invoice-detail-title" class="invoice-overview__title">
                    {{ $invoice->title }}
                </h2>

                @if ($invoice->project)
                    <p class="invoice-overview__project-name">
                        {{ $invoice->project->title }}
                    </p>
                @endif

                <dl class="invoice-overview__meta">
                    <div>
                        <dt>
                            案件
                        </dt>

                        <dd>
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

                    <div>
                        <dt>
                            顧客
                        </dt>

                        <dd>
                            {{ $invoice->billing_name ?: '未登録' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="invoice-overview__amount">
                <span class="invoice-overview__amount-label">
                    ご請求金額
                </span>

                <strong class="invoice-overview__amount-value">
                    ¥{{ number_format($invoice->total_amount) }}
                </strong>

                <span class="invoice-overview__currency">
                    {{ strtoupper($invoice->currency) }}
                </span>
            </div>
        </header>

        <div class="invoice-detail-layout">
            <main class="invoice-detail-main">
                <article class="invoice-section">
                    <header class="invoice-section__header">
                        <h3 class="invoice-section__title">
                            請求内容
                        </h3>

                        @can('update', $invoice)
                            <a href="{{ route('invoices.edit', $invoice) }}" class="invoice-section__edit">
                                編集
                            </a>
                        @endcan
                    </header>

                    <div class="invoice-section__body">
                        <dl class="invoice-definition">
                            <div class="invoice-definition__row">
                                <dt>
                                    案件
                                </dt>

                                <dd>
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

                            <div class="invoice-definition__row">
                                <dt>
                                    請求書タイトル
                                </dt>

                                <dd>
                                    {{ $invoice->title }}
                                </dd>
                            </div>

                            <div class="invoice-definition__row">
                                <dt>
                                    請求内容・備考
                                </dt>

                                <dd>
                                    {!! nl2br(e(
        $invoice->description
        ?: '補足情報はありません。'
    )) !!}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </article>

                <article class="invoice-section">
                    <header class="invoice-section__header">
                        <h3 class="invoice-section__title">
                            請求先情報
                        </h3>
                    </header>

                    <div class="invoice-section__body">
                        <dl class="invoice-definition">
                            <div class="invoice-definition__row">
                                <dt>
                                    会社名
                                </dt>

                                <dd>
                                    {{ $invoice->billing_name ?: '未登録' }}
                                </dd>
                            </div>

                            <div class="invoice-definition__row">
                                <dt>
                                    ご担当者
                                </dt>

                                <dd>
                                    @if ($invoice->billing_contact_name)
                                        {{ $invoice->billing_contact_name }} 様
                                    @else
                                        <span class="invoice-detail-empty">
                                            未登録
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div class="invoice-definition__row">
                                <dt>
                                    メール
                                </dt>

                                <dd>
                                    @if ($invoice->billing_email)
                                        <a href="mailto:{{ $invoice->billing_email }}" class="invoice-detail-link">
                                            {{ $invoice->billing_email }}
                                        </a>
                                    @else
                                        <span class="invoice-detail-empty">
                                            未登録
                                        </span>
                                    @endif
                                </dd>
                            </div>

                            <div class="invoice-definition__row">
                                <dt>
                                    電話番号
                                </dt>

                                <dd>
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

                            <div class="invoice-definition__row">
                                <dt>
                                    住所
                                </dt>

                                <dd class="invoice-definition__address">
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

                        <p class="invoice-snapshot-note">
                            この請求書は作成時点の顧客情報を保存しています。
                            顧客情報を後から変更しても、この請求先情報は変わりません。
                        </p>
                    </div>
                </article>
            </main>

            <aside class="invoice-detail-sidebar">
                <section class="invoice-side-section">
                    <header class="invoice-side-section__header">
                        <h3>
                            金額内訳
                        </h3>
                    </header>

                    <dl class="invoice-amount-list">
                        <div>
                            <dt>
                                小計
                            </dt>

                            <dd>
                                ¥{{ number_format($invoice->subtotal) }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                消費税（{{ $invoice->tax_rate }}%）
                            </dt>

                            <dd>
                                ¥{{ number_format($invoice->tax_amount) }}
                            </dd>
                        </div>

                        <div class="invoice-amount-list__total">
                            <dt>
                                合計
                            </dt>

                            <dd>
                                ¥{{ number_format($invoice->total_amount) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="invoice-side-section">
                    <header class="invoice-side-section__header">
                        <h3>
                            発行・支払情報
                        </h3>
                    </header>

                    <dl class="invoice-side-list">
                        <div>
                            <dt>
                                発行日
                            </dt>

                            <dd>
                                {{ $issuedAt }}
                            </dd>
                        </div>

                        <div>
                            <dt>
                                支払期限
                            </dt>

                            <dd>
                                {{ $dueDate }}
                            </dd>
                        </div>

                        <div>
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

                        <div>
                            <dt>
                                通貨
                            </dt>

                            <dd>
                                {{ strtoupper($invoice->currency) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="invoice-side-section">
                    <header class="invoice-side-section__header">
                        <h3>
                            関連・管理情報
                        </h3>
                    </header>

                    <div class="invoice-related-links">
                        @if ($invoice->project)
                            <a href="{{ route('projects.show', $invoice->project) }}" class="invoice-related-link">
                                <span>
                                    関連案件を確認
                                </span>

                                <span aria-hidden="true">
                                    ↗
                                </span>
                            </a>
                        @endif

                        @if ($invoice->customer)
                            <a href="{{ route('customers.show', $invoice->customer) }}" class="invoice-related-link">
                                <span>
                                    顧客詳細を確認
                                </span>

                                <span aria-hidden="true">
                                    ↗
                                </span>
                            </a>
                        @endif
                    </div>

                    <div class="invoice-side-section__subsection">
                        <h4 class="invoice-side-section__subheading">
                            管理情報
                        </h4>

                        <dl class="invoice-side-list">
                            <div>
                                <dt>
                                    作成日時
                                </dt>

                                <dd>
                                    {{ $createdAt }}
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    更新日時
                                </dt>

                                <dd>
                                    {{ $updatedAt }}
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    送信回数
                                </dt>

                                <dd>
                                    {{ number_format($invoice->send_count) }}回
                                </dd>
                            </div>

                            <div>
                                <dt>
                                    決済履歴
                                </dt>

                                <dd>
                                    {{ number_format($paymentsCount) }}件
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>

                @can('delete', $invoice)
                    <section class="invoice-delete-section">
                        <h3>
                            下書きの削除
                        </h3>

                        @if ($paymentsCount === 0)
                            <p>
                                削除した請求書は元に戻せません。
                            </p>

                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" data-invoice-delete-form
                                data-confirm-message="請求書「{{ $invoice->title }}」を削除します。この操作は元に戻せません。よろしいですか？">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="invoice-delete-button">
                                    この下書きを削除
                                </button>
                            </form>
                        @else
                            <p>
                                決済履歴が存在するため削除できません。
                            </p>
                        @endif
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
                    const message =
                        form.dataset.confirmMessage
                        ?? 'この請求書を削除します。よろしいですか？';

                    if (!window.confirm(message)) {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
@endsection