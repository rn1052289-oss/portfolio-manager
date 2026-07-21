@extends('layouts.app')

@php
    $invoice = $invoice ?? null;
    $isEdit = $invoice instanceof \App\Models\Invoice;

    $formAction = $isEdit
        ? route('invoices.update', $invoice)
        : route('invoices.store');

    $selectedProjectId = (string) old(
        'project_id',
        $isEdit
        ? $invoice->project_id
        : request('project_id', '')
    );

    $selectedCustomerId = (string) old(
        'customer_id',
        $isEdit
        ? $invoice->customer_id
        : request('customer_id', '')
    );

    $oldSubtotal = old(
        'subtotal',
        $isEdit
        ? $invoice->subtotal
        : null
    );

    $formattedOldSubtotal = $oldSubtotal !== null
        && $oldSubtotal !== ''
        ? number_format((int) $oldSubtotal)
        : '';

    $documentTitle = $isEdit
        ? '請求書編集 | ClientWork Manager'
        : '請求書作成 | ClientWork Manager';

    $pageLabel = $isEdit
        ? 'Edit Invoice'
        : 'Create Invoice';

    $pageTitle = $isEdit
        ? '請求書編集'
        : '請求書作成';

    $pageDescription = $isEdit
        ? '下書き請求書の案件、請求先、金額、日付を編集します。'
        : '案件と顧客を選択し、請求金額や支払期限を登録します。';
@endphp

@section('title', $documentTitle)
@section('page-label', $pageLabel)
@section('page-title', $pageTitle)
@section('page-description', $pageDescription)

@section('page-actions')
    @if ($isEdit)
        <a href="{{ route('invoices.show', $invoice) }}" class="button button--secondary">
            ← 請求書詳細へ戻る
        </a>
    @else
        <a href="{{ route('invoices.index') }}" class="button button--secondary">
            ← 請求書一覧へ戻る
        </a>
    @endif
@endsection

@section('content')
    <section class="invoice-form-page">
        <div class="invoice-form-page__header">
            <div>
                <p class="invoice-form-page__eyebrow">
                    {{ $isEdit ? 'Edit Invoice' : 'New Invoice' }}
                </p>

                <h2 class="invoice-form-page__title">
                    {{ $isEdit
        ? '請求書を編集'
        : '新しい請求書を作成' }}
                </h2>

                <p class="invoice-form-page__description">
                    @if ($isEdit)
                        請求書番号
                        {{ $invoice->invoice_number }}
                        の内容を編集します。
                    @else
                        案件情報、請求先、請求金額、支払期限を入力してください。
                    @endif
                </p>
            </div>

            <div class="invoice-form-page__status">
                <span class="invoice-status__badge invoice-status__badge--draft">
                    下書き
                </span>

                <span class="invoice-form-page__status-text">
                    @if ($isEdit)
                        下書き状態の請求書だけ編集できます
                    @else
                        作成直後は下書きとして保存されます
                    @endif
                </span>
            </div>
        </div>

        @if ($errors->any())
            <div class="invoice-form-errors" role="alert" aria-labelledby="invoice-form-errors-title">
                <h3 id="invoice-form-errors-title" class="invoice-form-errors__title">
                    入力内容を確認してください
                </h3>

                <ul class="invoice-form-errors__list">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($projects->isEmpty() || $customers->isEmpty())
            <div class="invoice-form-requirements">
                <h3 class="invoice-form-requirements__title">
                    請求書を作成するための準備が必要です
                </h3>

                <p class="invoice-form-requirements__text">
                    請求書を作成するには、案件と顧客の両方が登録されている必要があります。
                </p>

                <div class="invoice-form-requirements__actions">
                    @if ($projects->isEmpty())
                        <a href="{{ route('projects.create') }}" class="button button--primary">
                            案件を登録する
                        </a>
                    @endif

                    @if ($customers->isEmpty())
                        <a href="{{ route('customers.create') }}" class="button button--secondary">
                            顧客を登録する
                        </a>
                    @endif
                </div>
            </div>
        @else
            <form method="POST" action="{{ $formAction }}" class="invoice-form" novalidate data-invoice-form>
                @csrf

                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="invoice-form__section">
                    <div class="invoice-form__section-heading">
                        <span class="invoice-form__section-number">
                            01
                        </span>

                        <div>
                            <h3 class="invoice-form__section-title">
                                案件・請求先
                            </h3>

                            <p class="invoice-form__section-description">
                                請求対象の案件と、請求書を送る顧客を選択します。
                            </p>
                        </div>
                    </div>

                    <div class="invoice-form__grid invoice-form__grid--two-columns">
                        <div class="invoice-form__field">
                            <label for="project_id" class="invoice-form__label">
                                案件

                                <span class="invoice-form__required">
                                    必須
                                </span>
                            </label>

                            <select id="project_id" name="project_id"
                                class="invoice-form__control @error('project_id') is-invalid @enderror"
                                data-invoice-project-select
                                aria-describedby="project_id_help @error('project_id') project_id_error @enderror" required>
                                <option value="">
                                    案件を選択してください
                                </option>

                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" data-customer-id="{{ $project->customer_id ?? '' }}"
                                        @selected(
                                            $selectedProjectId === (string) $project->id
                                        )>
                                        {{ $project->title }}

                                        @if ($project->client_name)
                                            （{{ $project->client_name }}）
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            <p id="project_id_help" class="invoice-form__help">
                                請求対象となる案件を選択してください。
                            </p>

                            @error('project_id')
                                <p id="project_id_error" class="invoice-form__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="invoice-form__field">
                            <label for="customer_id" class="invoice-form__label">
                                顧客

                                <span class="invoice-form__required">
                                    必須
                                </span>
                            </label>

                            <select id="customer_id" name="customer_id"
                                class="invoice-form__control @error('customer_id') is-invalid @enderror"
                                data-invoice-customer-select
                                aria-describedby="customer_id_help @error('customer_id') customer_id_error @enderror" required>
                                <option value="">
                                    顧客を選択してください
                                </option>

                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(
                                        $selectedCustomerId === (string) $customer->id
                                    )>
                                        {{ $customer->name }}

                                        @if ($customer->contact_name)
                                            （担当：{{ $customer->contact_name }}）
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                            <p id="customer_id_help" class="invoice-form__help" data-invoice-customer-help>
                                案件に顧客が設定されている場合は、自動で選択されます。
                            </p>

                            @error('customer_id')
                                <p id="customer_id_error" class="invoice-form__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="invoice-form__section">
                    <div class="invoice-form__section-heading">
                        <span class="invoice-form__section-number">
                            02
                        </span>

                        <div>
                            <h3 class="invoice-form__section-title">
                                請求内容
                            </h3>

                            <p class="invoice-form__section-description">
                                請求書に表示するタイトルと作業内容を入力します。
                            </p>
                        </div>
                    </div>

                    <div class="invoice-form__grid">
                        <div class="invoice-form__field">
                            <label for="title" class="invoice-form__label">
                                請求書タイトル

                                <span class="invoice-form__required">
                                    必須
                                </span>
                            </label>

                            <input id="title" type="text" name="title" value="{{ old(
                'title',
                $isEdit
                ? $invoice->title
                : ''
            ) }}" class="invoice-form__control @error('title') is-invalid @enderror" maxlength="255"
                                placeholder="例：Webサイト制作費" autocomplete="off"
                                aria-describedby="title_help @error('title') title_error @enderror" required>

                            <p id="title_help" class="invoice-form__help">
                                顧客が請求内容を判断しやすい名称を入力してください。
                            </p>

                            @error('title')
                                <p id="title_error" class="invoice-form__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="invoice-form__field">
                            <label for="description" class="invoice-form__label">
                                請求内容
                            </label>

                            <textarea id="description" name="description" rows="6"
                                class="invoice-form__control invoice-form__control--textarea @error('description') is-invalid @enderror"
                                maxlength="5000" placeholder="例：コーポレートサイトの設計、デザイン、コーディング一式"
                                aria-describedby="description_help @error('description') description_error @enderror">{{ old(
                'description',
                $isEdit
                ? $invoice->description
                : ''
            ) }}</textarea>

                            <p id="description_help" class="invoice-form__help">
                                作業範囲や内訳など、顧客へ伝える補足情報を入力できます。
                            </p>

                            @error('description')
                                <p id="description_error" class="invoice-form__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="invoice-form__section invoice-form__section--amount">
                    <div class="invoice-form__section-heading">
                        <span class="invoice-form__section-number">
                            03
                        </span>

                        <div>
                            <h3 class="invoice-form__section-title">
                                請求金額
                            </h3>

                            <p class="invoice-form__section-description">
                                小計を入力すると、消費税とご請求金額が自動計算されます。
                            </p>
                        </div>
                    </div>

                    <div class="invoice-form__amount-layout">
                        <div class="invoice-form__amount-input-panel">
                            <div class="invoice-form__amount-input-header">
                                <div>
                                    <p class="invoice-form__amount-input-eyebrow">
                                        Amount Input
                                    </p>

                                    <h4 class="invoice-form__amount-input-title">
                                        税抜金額を入力
                                    </h4>
                                </div>

                                <span class="invoice-form__tax-rate-badge">
                                    消費税 10％
                                </span>
                            </div>

                            <div class="invoice-form__field">
                                <label for="subtotal_display" class="invoice-form__label">
                                    小計

                                    <span class="invoice-form__required">
                                        必須
                                    </span>
                                </label>

                                <div class="invoice-form__subtotal-control">
                                    <span class="invoice-form__subtotal-prefix" aria-hidden="true">
                                        ¥
                                    </span>

                                    <input id="subtotal_display" type="text" value="{{ $formattedOldSubtotal }}"
                                        class="invoice-form__control invoice-form__subtotal-input @error('subtotal') is-invalid @enderror"
                                        inputmode="numeric" autocomplete="off" placeholder="100,000"
                                        data-invoice-subtotal-display-input
                                        aria-describedby="subtotal_help @error('subtotal') subtotal_error @enderror" required>
                                </div>

                                <input type="hidden" name="subtotal" value="{{ $oldSubtotal }}" data-invoice-subtotal>

                                <p id="subtotal_help" class="invoice-form__help invoice-form__amount-help">
                                    消費税を含まない金額を、1円単位で入力してください。
                                </p>

                                @error('subtotal')
                                    <p id="subtotal_error" class="invoice-form__error">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="invoice-form__tax-explanation">
                                <span class="invoice-form__tax-explanation-icon" aria-hidden="true">
                                    %
                                </span>

                                <div>
                                    <strong>
                                        消費税は自動計算されます
                                    </strong>

                                    <p>
                                        小計の10％を計算し、1円未満の端数は切り捨てます。
                                    </p>
                                </div>
                            </div>
                        </div>

                        <aside class="invoice-form__calculation" aria-labelledby="invoice-calculation-title">
                            <div class="invoice-form__calculation-header">
                                <div>
                                    <p class="invoice-form__calculation-eyebrow">
                                        Payment Summary
                                    </p>

                                    <h4 id="invoice-calculation-title" class="invoice-form__calculation-title">
                                        お支払い金額
                                    </h4>
                                </div>

                                <span class="invoice-form__calculation-icon" aria-hidden="true">
                                    ¥
                                </span>
                            </div>

                            <div class="invoice-form__calculation-body">
                                <div class="invoice-form__calculation-row">
                                    <span>
                                        小計
                                    </span>

                                    <strong data-invoice-subtotal-display>
                                        ¥0
                                    </strong>
                                </div>

                                <div class="invoice-form__calculation-row">
                                    <span>
                                        消費税（10％）
                                    </span>

                                    <strong data-invoice-tax-display>
                                        ¥0
                                    </strong>
                                </div>

                                <div class="invoice-form__calculation-divider"></div>

                                <div class="invoice-form__calculation-row invoice-form__calculation-row--total">
                                    <span>
                                        ご請求金額
                                    </span>

                                    <strong data-invoice-total-display>
                                        ¥0
                                    </strong>
                                </div>
                            </div>

                            <p class="invoice-form__calculation-note">
                                小計と消費税を合算した、顧客への最終請求金額です。
                            </p>

                            @error('tax_amount')
                                <p class="invoice-form__calculation-error">
                                    {{ $message }}
                                </p>
                            @enderror

                            @error('total_amount')
                                <p class="invoice-form__calculation-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </aside>
                    </div>

                    <input type="hidden" name="tax_amount" value="{{ old(
                'tax_amount',
                $isEdit
                ? $invoice->tax_amount
                : 0
            ) }}" data-invoice-tax>

                    <input type="hidden" name="total_amount" value="{{ old(
                'total_amount',
                $isEdit
                ? $invoice->total_amount
                : 0
            ) }}" data-invoice-total>
                </div>

                <div class="invoice-form__section">
                    <div class="invoice-form__section-heading">
                        <span class="invoice-form__section-number">
                            04
                        </span>

                        <div>
                            <h3 class="invoice-form__section-title">
                                発行日・支払期限
                            </h3>

                            <p class="invoice-form__section-description">
                                支払期限は発行日以降の日付を指定してください。
                            </p>
                        </div>
                    </div>

                    <div class="invoice-form__grid invoice-form__grid--two-columns">
                        <div class="invoice-form__field">
                            <label for="issued_at" class="invoice-form__label">
                                発行日

                                <span class="invoice-form__required">
                                    必須
                                </span>
                            </label>

                            <input id="issued_at" type="date" name="issued_at" value="{{ old(
                'issued_at',
                $isEdit
                ? $invoice->issued_at?->toDateString()
                : now()->toDateString()
            ) }}" class="invoice-form__control @error('issued_at') is-invalid @enderror" data-invoice-issued-at
                                aria-describedby="issued_at_help @error('issued_at') issued_at_error @enderror" required>

                            <p id="issued_at_help" class="invoice-form__help">
                                請求書を発行する日付です。
                            </p>

                            @error('issued_at')
                                <p id="issued_at_error" class="invoice-form__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="invoice-form__field">
                            <label for="due_date" class="invoice-form__label">
                                支払期限

                                <span class="invoice-form__required">
                                    必須
                                </span>
                            </label>

                            <input id="due_date" type="date" name="due_date" value="{{ old(
                'due_date',
                $isEdit
                ? $invoice->due_date?->toDateString()
                : now()->addMonth()->toDateString()
            ) }}" class="invoice-form__control @error('due_date') is-invalid @enderror" data-invoice-due-date
                                aria-describedby="due_date_help @error('due_date') due_date_error @enderror" required>

                            <p id="due_date_help" class="invoice-form__help">
                                顧客に支払いを依頼する期限です。
                            </p>

                            @error('due_date')
                                <p id="due_date_error" class="invoice-form__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <aside class="invoice-form__notice">
                    <h3 class="invoice-form__notice-title">
                        {{ $isEdit
                ? '更新時に自動処理される情報'
                : '保存時に自動設定される情報' }}
                    </h3>

                    <ul class="invoice-form__notice-list">
                        @if ($isEdit)
                            <li>
                                同じ発行月内の日付変更では、現在の請求書番号を維持します。
                            </li>

                            <li>
                                発行月を変更した場合は、新しい年月の請求書番号を自動採番します。
                            </li>

                            <li>
                                請求先情報は、選択した顧客の現在情報から再取得します。
                            </li>

                            <li>
                                ステータス、公開用トークン、送信回数などの管理情報は変更されません。
                            </li>
                        @else
                            <li>
                                請求書番号と公開用トークンは、システムが自動生成します。
                            </li>

                            <li>
                                請求先の会社名、担当者、メール、住所は、選択した顧客情報からコピーします。
                            </li>

                            <li>
                                作成直後のステータスは「下書き」になります。
                            </li>

                            <li>
                                通貨は日本円（JPY）で保存されます。
                            </li>
                        @endif
                    </ul>
                </aside>

                <div class="invoice-form__actions">
                    <a href="{{ $isEdit
                ? route('invoices.show', $invoice)
                : route('invoices.index') }}" class="button button--secondary">
                        キャンセル
                    </a>

                    <button type="submit" class="button button--primary">
                        {{ $isEdit
                ? '変更内容を保存'
                : '請求書を保存' }}
                    </button>
                </div>
            </form>
        @endif
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const invoiceForm = document.querySelector(
                '[data-invoice-form]'
            );

            const projectSelect = document.querySelector(
                '[data-invoice-project-select]'
            );

            const customerSelect = document.querySelector(
                '[data-invoice-customer-select]'
            );

            const customerHelp = document.querySelector(
                '[data-invoice-customer-help]'
            );

            const subtotalDisplayInput = document.querySelector(
                '[data-invoice-subtotal-display-input]'
            );

            const subtotalInput = document.querySelector(
                '[data-invoice-subtotal]'
            );

            const taxInput = document.querySelector(
                '[data-invoice-tax]'
            );

            const totalInput = document.querySelector(
                '[data-invoice-total]'
            );

            const subtotalDisplay = document.querySelector(
                '[data-invoice-subtotal-display]'
            );

            const taxDisplay = document.querySelector(
                '[data-invoice-tax-display]'
            );

            const totalDisplay = document.querySelector(
                '[data-invoice-total-display]'
            );

            const issuedAtInput = document.querySelector(
                '[data-invoice-issued-at]'
            );

            const dueDateInput = document.querySelector(
                '[data-invoice-due-date]'
            );

            const formatAmount = (amount) => {
                return new Intl.NumberFormat('ja-JP', {
                    style: 'currency',
                    currency: 'JPY',
                    maximumFractionDigits: 0,
                }).format(amount);
            };

            const formatPlainNumber = (amount) => {
                return new Intl.NumberFormat('ja-JP', {
                    maximumFractionDigits: 0,
                }).format(amount);
            };

            const parseAmount = (value) => {
                const numericValue = String(value ?? '')
                    .replace(/[^\d]/g, '');

                const amount = Number.parseInt(
                    numericValue,
                    10
                );

                return Number.isNaN(amount)
                    ? 0
                    : Math.max(amount, 0);
            };

            const updateAmounts = ({
                formatVisibleInput = false,
            } = {}) => {
                if (
                    !subtotalDisplayInput
                    || !subtotalInput
                    || !taxInput
                    || !totalInput
                ) {
                    return;
                }

                const subtotal = parseAmount(
                    subtotalDisplayInput.value
                );

                const taxRate = 0.1;

                const taxAmount = Math.floor(
                    subtotal * taxRate
                );

                const totalAmount = subtotal + taxAmount;

                subtotalInput.value = subtotal > 0
                    ? subtotal
                    : '';

                taxInput.value = taxAmount;
                totalInput.value = totalAmount;

                if (formatVisibleInput) {
                    subtotalDisplayInput.value = subtotal > 0
                        ? formatPlainNumber(subtotal)
                        : '';
                }

                if (subtotalDisplay) {
                    subtotalDisplay.textContent =
                        formatAmount(subtotal);
                }

                if (taxDisplay) {
                    taxDisplay.textContent =
                        formatAmount(taxAmount);
                }

                if (totalDisplay) {
                    totalDisplay.textContent =
                        formatAmount(totalAmount);
                }
            };

            const enableAllCustomerOptions = () => {
                if (!customerSelect) {
                    return;
                }

                Array.from(customerSelect.options).forEach(
                    (option) => {
                        option.disabled = false;
                    }
                );
            };

            const syncCustomerWithProject = (
                clearWhenUnlinked = false
            ) => {
                if (!projectSelect || !customerSelect) {
                    return;
                }

                const selectedProjectOption =
                    projectSelect.options[
                    projectSelect.selectedIndex
                    ];

                const linkedCustomerId =
                    selectedProjectOption?.dataset.customerId
                    ?? '';

                enableAllCustomerOptions();

                if (linkedCustomerId !== '') {
                    customerSelect.value = linkedCustomerId;

                    Array.from(customerSelect.options).forEach(
                        (option) => {
                            if (
                                option.value !== ''
                                && option.value !== linkedCustomerId
                            ) {
                                option.disabled = true;
                            }
                        }
                    );

                    if (customerHelp) {
                        customerHelp.textContent =
                            '案件に登録されている顧客を自動選択しました。';
                    }

                    return;
                }

                if (clearWhenUnlinked) {
                    customerSelect.value = '';
                }

                if (customerHelp) {
                    customerHelp.textContent =
                        'この案件には顧客が設定されていないため、請求先を選択してください。';
                }
            };

            if (projectSelect && customerSelect) {
                projectSelect.addEventListener(
                    'change',
                    () => {
                        syncCustomerWithProject(true);
                    }
                );

                syncCustomerWithProject(false);
            }

            if (
                subtotalDisplayInput
                && subtotalInput
                && taxInput
                && totalInput
            ) {
                subtotalDisplayInput.addEventListener(
                    'focus',
                    () => {
                        const subtotal = parseAmount(
                            subtotalDisplayInput.value
                        );

                        subtotalDisplayInput.value =
                            subtotal > 0
                                ? String(subtotal)
                                : '';
                    }
                );

                subtotalDisplayInput.addEventListener(
                    'input',
                    () => {
                        updateAmounts();
                    }
                );

                subtotalDisplayInput.addEventListener(
                    'blur',
                    () => {
                        updateAmounts({
                            formatVisibleInput: true,
                        });
                    }
                );

                updateAmounts({
                    formatVisibleInput: true,
                });
            }

            if (invoiceForm) {
                invoiceForm.addEventListener(
                    'submit',
                    () => {
                        updateAmounts();
                    }
                );
            }

            if (issuedAtInput && dueDateInput) {
                const updateDueDateMinimum = () => {
                    dueDateInput.min = issuedAtInput.value;

                    if (
                        dueDateInput.value !== ''
                        && dueDateInput.value
                        < issuedAtInput.value
                    ) {
                        dueDateInput.value =
                            issuedAtInput.value;
                    }
                };

                issuedAtInput.addEventListener(
                    'change',
                    updateDueDateMinimum
                );

                updateDueDateMinimum();
            }
        });
    </script>
@endsection