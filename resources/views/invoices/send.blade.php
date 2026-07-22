@extends('layouts.app')

@section('title', '請求書メール送信 | ClientWork Manager')
@section('page-label', 'Send Invoice')
@section('page-title', '請求書メール送信')
@section('page-description', '送信先、件名、本文を確認して請求書PDFを送信します。')

@section('page-actions')
    <a href="{{ route('invoices.show', $invoice) }}" class="button button--secondary">
        ← 請求書詳細へ戻る
    </a>
@endsection

@section('content')
    @php
        $recipientEmail = old(
            'recipient_email',
            $recipientEmail ?? $invoice->billing_email
        );

        $mailSubject = old(
            'subject',
            $mailSubject ?? ''
        );

        $messageBody = old(
            'body',
            $messageBody ?? ''
        );

        $dueDate = $invoice->due_date?->format('Y年n月j日')
            ?? '未設定';
    @endphp

    <section class="invoice-send-page" aria-labelledby="invoice-send-title">
        <nav class="invoice-send-breadcrumb" aria-label="パンくずリスト">
            <ol class="invoice-send-breadcrumb__list">
                <li class="invoice-send-breadcrumb__item">
                    <a href="{{ route('invoices.index') }}" class="invoice-send-breadcrumb__link">
                        請求書一覧
                    </a>
                </li>

                <li class="invoice-send-breadcrumb__separator" aria-hidden="true">
                    ›
                </li>

                <li class="invoice-send-breadcrumb__item">
                    <a href="{{ route('invoices.show', $invoice) }}" class="invoice-send-breadcrumb__link">
                        請求書詳細
                    </a>
                </li>

                <li class="invoice-send-breadcrumb__separator" aria-hidden="true">
                    ›
                </li>

                <li class="
                            invoice-send-breadcrumb__item
                            invoice-send-breadcrumb__item--current
                        " aria-current="page">
                    メール送信
                </li>
            </ol>
        </nav>

        @if ($errors->any())
            <div class="invoice-send-errors" role="alert" aria-labelledby="invoice-send-errors-title">
                <h2 id="invoice-send-errors-title" class="invoice-send-errors__title">
                    入力内容を確認してください
                </h2>

                <ul class="invoice-send-errors__list">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <header class="invoice-send-hero">
            <div class="invoice-send-hero__content">
                <p class="invoice-send-hero__eyebrow">
                    Send Invoice
                </p>

                <h2 id="invoice-send-title" class="invoice-send-hero__title">
                    請求書をメールで送信
                </h2>

                <p class="invoice-send-hero__description">
                    送信前に宛先、件名、本文を確認してください。
                    請求書PDFは自動的に添付されます。
                </p>
            </div>

            <div class="invoice-send-hero__number">
                <span>
                    請求書番号
                </span>

                <strong>
                    {{ $invoice->invoice_number }}
                </strong>
            </div>
        </header>

        <div class="invoice-send-layout">
            <main class="invoice-send-main">
                <form method="POST" action="{{ route('invoices.mail.store', $invoice) }}" class="invoice-send-form"
                    novalidate>
                    @csrf

                    <section class="invoice-send-card">
                        <header class="invoice-send-card__header">
                            <div>
                                <p class="invoice-send-card__eyebrow">
                                    Recipient
                                </p>

                                <h3 class="invoice-send-card__title">
                                    送信先
                                </h3>
                            </div>

                            <span class="invoice-send-card__icon" aria-hidden="true">
                                宛
                            </span>
                        </header>

                        <div class="invoice-send-card__body">
                            <div class="invoice-send-field">
                                <label for="recipient_email" class="invoice-send-field__label">
                                    送信先メールアドレス

                                    <span class="invoice-send-field__required">
                                        必須
                                    </span>
                                </label>

                                <input id="recipient_email" type="email" name="recipient_email"
                                    value="{{ $recipientEmail }}" class="
                                            invoice-send-field__control
                                            @error('recipient_email') is-invalid @enderror
                                        " maxlength="255" autocomplete="email" placeholder="customer@example.com"
                                    aria-describedby="
                                            recipient_email_help
                                            @error('recipient_email')
                                                recipient_email_error
                                            @enderror
                                        " required>

                                <p id="recipient_email_help" class="invoice-send-field__help">
                                    請求書に保存されている顧客メールアドレスが初期表示されます。
                                </p>

                                @error('recipient_email')
                                    <p id="recipient_email_error" class="invoice-send-field__error">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="invoice-send-card">
                        <header class="invoice-send-card__header">
                            <div>
                                <p class="invoice-send-card__eyebrow">
                                    Message
                                </p>

                                <h3 class="invoice-send-card__title">
                                    メール内容
                                </h3>
                            </div>

                            <span class="invoice-send-card__icon" aria-hidden="true">
                                文
                            </span>
                        </header>

                        <div class="invoice-send-card__body">
                            <div class="invoice-send-field">
                                <label for="subject" class="invoice-send-field__label">
                                    件名

                                    <span class="invoice-send-field__required">
                                        必須
                                    </span>
                                </label>

                                <input id="subject" type="text" name="subject" value="{{ $mailSubject }}" class="
                                            invoice-send-field__control
                                            @error('subject') is-invalid @enderror
                                        " maxlength="255" autocomplete="off" placeholder="例：【請求書】INV-202607-001 Webサイト制作費"
                                    aria-describedby="
                                            subject_help
                                            @error('subject')
                                                subject_error
                                            @enderror
                                        " required>

                                <p id="subject_help" class="invoice-send-field__help">
                                    顧客が請求書メールだと判断しやすい件名にしてください。
                                </p>

                                @error('subject')
                                    <p id="subject_error" class="invoice-send-field__error">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="invoice-send-field">
                                <label for="body" class="invoice-send-field__label">
                                    本文

                                    <span class="invoice-send-field__required">
                                        必須
                                    </span>
                                </label>

                                <textarea id="body" name="body" rows="12" class="
                                            invoice-send-field__control
                                            invoice-send-field__control--textarea
                                            @error('body') is-invalid @enderror
                                        " maxlength="5000" aria-describedby="
                                            body_help
                                            @error('body')
                                                body_error
                                            @enderror
                                        " required>{{ $messageBody }}</textarea>

                                <p id="body_help" class="invoice-send-field__help">
                                    改行は送信メールにも反映されます。
                                </p>

                                @error('body')
                                    <p id="body_error" class="invoice-send-field__error">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div class="invoice-send-form__actions">
                        <a href="{{ route('invoices.show', $invoice) }}" class="button button--secondary">
                            キャンセル
                        </a>

                        <button type="submit" class="button button--primary">
                            PDFを添付して送信
                        </button>
                    </div>
                </form>
            </main>

            <aside class="invoice-send-sidebar">
                <section class="invoice-send-summary" aria-labelledby="invoice-send-summary-title">
                    <header class="invoice-send-summary__header">
                        <div>
                            <p class="invoice-send-summary__eyebrow">
                                Invoice Summary
                            </p>

                            <h3 id="invoice-send-summary-title" class="invoice-send-summary__title">
                                送信する請求書
                            </h3>
                        </div>
                    </header>

                    <dl class="invoice-send-summary__list">
                        <div class="invoice-send-summary__row">
                            <dt>
                                請求書番号
                            </dt>

                            <dd>
                                {{ $invoice->invoice_number }}
                            </dd>
                        </div>

                        <div class="invoice-send-summary__row">
                            <dt>
                                請求先
                            </dt>

                            <dd>
                                {{ $invoice->billing_name ?: '未登録' }}
                            </dd>
                        </div>

                        <div class="invoice-send-summary__row">
                            <dt>
                                支払期限
                            </dt>

                            <dd>
                                {{ $dueDate }}
                            </dd>
                        </div>

                        <div class="
                                    invoice-send-summary__row
                                    invoice-send-summary__row--total
                                ">
                            <dt>
                                ご請求金額
                            </dt>

                            <dd>
                                ¥{{ number_format($invoice->total_amount) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="invoice-send-attachment">
                    <div class="invoice-send-attachment__icon">
                        PDF
                    </div>

                    <div class="invoice-send-attachment__content">
                        <h3 class="invoice-send-attachment__title">
                            請求書PDFを添付
                        </h3>

                        <p class="invoice-send-attachment__file">
                            invoice-{{ $invoice->invoice_number }}.pdf
                        </p>

                        <p class="invoice-send-attachment__description">
                            送信時に最新の請求書内容からPDFを生成し、
                            メールへ自動添付します。
                        </p>
                    </div>
                </section>

                <section class="invoice-send-notice">
                    <h3 class="invoice-send-notice__title">
                        送信後に更新される情報
                    </h3>

                    <ul class="invoice-send-notice__list">
                        <li>
                            初回送信時はステータスが送信済みになります。
                        </li>

                        <li>
                            送信日時と送信先が保存されます。
                        </li>

                        <li>
                            送信回数が1回加算されます。
                        </li>

                        <li>
                            添付したPDFが非公開Storageへ保存されます。
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </section>
@endsection