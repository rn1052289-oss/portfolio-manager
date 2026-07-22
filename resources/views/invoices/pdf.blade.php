@php
    $issuedAt = $invoice->issued_at?->format('Y年n月j日')
        ?? '未設定';

    $dueDate = $invoice->due_date?->format('Y年n月j日')
        ?? '未設定';

    $transactionDate = $invoice->transaction_date?->format('Y年n月j日')
        ?? $issuedAt;

    $description = trim((string) $invoice->description);
@endphp

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">

    <title>
        {{ $invoice->invoice_number }} | 請求書
    </title>

    <style>
        @font-face {
            font-family: "IPAPGothic";
            font-style: normal;
            font-weight: normal;
            src: url("file://{{ resource_path('fonts/ipagp.ttf') }}") format("truetype");
        }

        @page {
            margin: 8mm 11mm 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "IPAPGothic", sans-serif;
            font-size: 8.8pt;
            font-weight: normal;
            line-height: 1.35;
            color: #1f2937;
            background: #ffffff;
        }

        h1,
        h2,
        h3,
        strong,
        b,
        th {
            font-weight: normal;
        }

        p {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .document-header {
            margin-bottom: 8px;
        }

        .document-header__title-cell {
            width: 53%;
            vertical-align: top;
        }

        .document-header__meta-cell {
            width: 47%;
            vertical-align: top;
        }

        .document-label {
            margin-bottom: 2px;
            font-family: sans-serif;
            font-size: 7pt;
            letter-spacing: 0.16em;
            color: #2563eb;
        }

        .document-title {
            margin: 0;
            font-size: 22pt;
            font-weight: normal;
            letter-spacing: 0.12em;
            color: #0f172a;
        }

        .document-meta {
            border: 1px solid #cbd5e1;
        }

        .document-meta th,
        .document-meta td {
            padding: 3px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8pt;
            line-height: 1.3;
        }

        .document-meta tr:last-child th,
        .document-meta tr:last-child td {
            border-bottom: 0;
        }

        .document-meta th {
            width: 38%;
            background: #f8fafc;
            text-align: left;
            color: #475569;
        }

        .document-meta td {
            text-align: right;
            color: #0f172a;
        }

        .party-table {
            margin-top: 9px;
            margin-bottom: 9px;
        }

        .party-table__cell {
            width: 48%;
            padding: 8px 10px;
            vertical-align: top;
            border: 1px solid #cbd5e1;
        }

        .party-table__spacer {
            width: 4%;
            border: 0;
        }

        .party-label {
            margin-bottom: 4px;
            font-family: sans-serif;
            font-size: 6.8pt;
            letter-spacing: 0.1em;
            color: #64748b;
        }

        .party-name {
            margin-bottom: 4px;
            font-size: 11.5pt;
            line-height: 1.3;
            color: #0f172a;
        }

        .party-contact {
            margin-bottom: 2px;
            font-size: 8.3pt;
            line-height: 1.3;
            color: #334155;
        }

        .party-detail {
            margin-top: 1px;
            font-size: 7.8pt;
            line-height: 1.3;
            color: #475569;
            word-break: break-all;
        }

        .amount-box {
            margin-bottom: 8px;
            border: 1px solid #1d4ed8;
            background: #eff6ff;
        }

        .amount-box__label {
            width: 55%;
            padding: 7px 10px;
            font-size: 9.5pt;
            color: #1e3a8a;
        }

        .amount-box__value {
            width: 45%;
            padding: 6px 10px;
            text-align: right;
            font-size: 17pt;
            line-height: 1.2;
            color: #0f172a;
        }

        .schedule-table {
            margin-bottom: 8px;
            border: 1px solid #cbd5e1;
        }

        .schedule-table th,
        .schedule-table td {
            padding: 4px 7px;
            border-right: 1px solid #e2e8f0;
            font-size: 7.8pt;
            line-height: 1.3;
        }

        .schedule-table th:last-child,
        .schedule-table td:last-child {
            border-right: 0;
        }

        .schedule-table th {
            background: #f8fafc;
            color: #64748b;
            text-align: left;
        }

        .schedule-table td {
            color: #0f172a;
        }

        .line-items {
            margin-bottom: 8px;
            border: 1px solid #94a3b8;
        }

        .line-items th,
        .line-items td {
            padding: 5px 7px;
            border-right: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            line-height: 1.3;
        }

        .line-items th:last-child,
        .line-items td:last-child {
            border-right: 0;
        }

        .line-items thead th {
            background: #e2e8f0;
            font-size: 7.8pt;
            color: #334155;
            text-align: left;
        }

        .line-items tbody td {
            font-size: 8.5pt;
        }

        .line-items__description {
            width: 48%;
        }

        .line-items__quantity {
            width: 12%;
            text-align: right;
        }

        .line-items__unit-price {
            width: 20%;
            text-align: right;
        }

        .line-items__amount {
            width: 20%;
            text-align: right;
        }

        .line-items__subtotal-label,
        .line-items__tax-label,
        .line-items__total-label {
            text-align: right;
            background: #f8fafc;
            color: #475569;
        }

        .line-items__subtotal-value,
        .line-items__tax-value,
        .line-items__total-value {
            text-align: right;
        }

        .line-items__total-label,
        .line-items__total-value {
            padding-top: 6px;
            padding-bottom: 6px;
            background: #eff6ff;
            font-size: 10.5pt;
            color: #0f172a;
            border-bottom: 0;
        }

        .information-table {
            margin-top: 2px;
        }

        .information-table__cell {
            width: 48%;
            vertical-align: top;
        }

        .information-table__spacer {
            width: 4%;
        }

        .information-card {
            border: 1px solid #cbd5e1;
            page-break-inside: avoid;
        }

        .information-card__title {
            margin: 0;
            padding: 4px 7px;
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            font-size: 8.3pt;
            line-height: 1.3;
            color: #334155;
        }

        .information-card__body {
            padding: 6px 8px;
            font-size: 7.8pt;
            line-height: 1.35;
            color: #334155;
        }

        .information-card__row {
            margin-bottom: 2px;
        }

        .information-card__row:last-child {
            margin-bottom: 0;
        }

        .information-card__label {
            color: #64748b;
        }

        .notes {
            margin-top: 7px;
            border: 1px solid #cbd5e1;
        }

        .notes__title {
            margin: 0;
            padding: 4px 7px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8pt;
            line-height: 1.3;
            color: #475569;
        }

        .notes__body {
            min-height: 24px;
            padding: 5px 7px;
            font-size: 7.8pt;
            line-height: 1.35;
            color: #334155;
        }

        .footer {
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-family: "IPAPGothic", sans-serif;
            font-size: 6.5pt;
            line-height: 1.2;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <table class="document-header">
        <tr>
            <td class="document-header__title-cell">
                <p class="document-label">
                    INVOICE
                </p>

                <h1 class="document-title">
                    請求書
                </h1>
            </td>

            <td class="document-header__meta-cell">
                <table class="document-meta">
                    <tr>
                        <th>
                            請求書番号
                        </th>

                        <td>
                            {{ $invoice->invoice_number }}
                        </td>
                    </tr>

                    <tr>
                        <th>
                            発行日
                        </th>

                        <td>
                            {{ $issuedAt }}
                        </td>
                    </tr>

                    <tr>
                        <th>
                            支払期限
                        </th>

                        <td>
                            {{ $dueDate }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="party-table">
        <tr>
            <td class="party-table__cell">
                <p class="party-label">
                    BILL TO
                </p>

                <p class="party-name">
                    {{ $invoice->billing_name ?: '請求先未設定' }} 御中
                </p>

                @if ($invoice->billing_contact_name)
                    <p class="party-contact">
                        {{ $invoice->billing_contact_name }} 様
                    </p>
                @endif

                @if ($invoice->billing_postal_code)
                    <p class="party-detail">
                        〒{{ $invoice->billing_postal_code }}
                    </p>
                @endif

                @if ($invoice->billing_address)
                    <p class="party-detail">
                        {{ $invoice->billing_address }}
                    </p>
                @endif

                @if ($invoice->billing_email)
                    <p class="party-detail">
                        {{ $invoice->billing_email }}
                    </p>
                @endif

                @if ($invoice->billing_phone)
                    <p class="party-detail">
                        {{ $invoice->billing_phone }}
                    </p>
                @endif
            </td>

            <td class="party-table__spacer"></td>

            <td class="party-table__cell">
                <p class="party-label">
                    FROM
                </p>

                <p class="party-name">
                    {{ $invoice->issuer_business_name ?: '発行者未設定' }}
                </p>

                @if ($invoice->issuer_representative_name)
                    <p class="party-contact">
                        代表者：{{ $invoice->issuer_representative_name }}
                    </p>
                @endif

                @if ($invoice->issuer_postal_code)
                    <p class="party-detail">
                        〒{{ $invoice->issuer_postal_code }}
                    </p>
                @endif

                @if ($invoice->issuer_address)
                    <p class="party-detail">
                        {{ $invoice->issuer_address }}
                    </p>
                @endif

                @if ($invoice->issuer_email)
                    <p class="party-detail">
                        {{ $invoice->issuer_email }}
                    </p>
                @endif

                @if ($invoice->issuer_phone)
                    <p class="party-detail">
                        {{ $invoice->issuer_phone }}
                    </p>
                @endif

                @if ($invoice->issuer_registration_number)
                    <p class="party-detail">
                        登録番号：{{ $invoice->issuer_registration_number }}
                    </p>
                @endif
            </td>
        </tr>
    </table>

    <table class="amount-box">
        <tr>
            <td class="amount-box__label">
                ご請求金額
            </td>

            <td class="amount-box__value">
                {{ number_format($invoice->total_amount) }}円
            </td>
        </tr>
    </table>

    <table class="schedule-table">
        <tr>
            <th>
                取引日
            </th>

            <th>
                税率
            </th>

            <th>
                通貨
            </th>
        </tr>

        <tr>
            <td>
                {{ $transactionDate }}
            </td>

            <td>
                {{ number_format($invoice->tax_rate) }}%
            </td>

            <td>
                {{ strtoupper($invoice->currency) }}
            </td>
        </tr>
    </table>

    <table class="line-items">
        <thead>
            <tr>
                <th class="line-items__description">
                    品目
                </th>

                <th class="line-items__quantity">
                    数量
                </th>

                <th class="line-items__unit-price">
                    単価
                </th>

                <th class="line-items__amount">
                    金額
                </th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>
                    {{ $invoice->title }}
                </td>

                <td class="line-items__quantity">
                    1
                </td>

                <td class="line-items__unit-price">
                    {{ number_format($invoice->subtotal) }}円
                </td>

                <td class="line-items__amount">
                    {{ number_format($invoice->subtotal) }}円
                </td>
            </tr>

            <tr>
                <td colspan="3" class="line-items__subtotal-label">
                    小計
                </td>

                <td class="line-items__subtotal-value">
                    {{ number_format($invoice->subtotal) }}円
                </td>
            </tr>

            <tr>
                <td colspan="3" class="line-items__tax-label">
                    消費税（{{ number_format($invoice->tax_rate) }}%）
                </td>

                <td class="line-items__tax-value">
                    {{ number_format($invoice->tax_amount) }}円
                </td>
            </tr>

            <tr>
                <td colspan="3" class="line-items__total-label">
                    合計
                </td>

                <td class="line-items__total-value">
                    {{ number_format($invoice->total_amount) }}円
                </td>
            </tr>
        </tbody>
    </table>

    <table class="information-table">
        <tr>
            <td class="information-table__cell">
                <section class="information-card">
                    <h2 class="information-card__title">
                        振込先
                    </h2>

                    <div class="information-card__body">
                        <p class="information-card__row">
                            <span class="information-card__label">
                                金融機関：
                            </span>

                            {{ $invoice->issuer_bank_name ?: '未設定' }}
                        </p>

                        <p class="information-card__row">
                            <span class="information-card__label">
                                支店：
                            </span>

                            {{ $invoice->issuer_bank_branch_name ?: '未設定' }}
                        </p>

                        <p class="information-card__row">
                            <span class="information-card__label">
                                口座：
                            </span>

                            {{ $invoice->issuer_bank_account_type ?: '未設定' }}
                            {{ $invoice->issuer_bank_account_number ?: '' }}
                        </p>

                        <p class="information-card__row">
                            <span class="information-card__label">
                                名義：
                            </span>

                            {{ $invoice->issuer_bank_account_holder ?: '未設定' }}
                        </p>
                    </div>
                </section>
            </td>

            <td class="information-table__spacer"></td>

            <td class="information-table__cell">
                <section class="information-card">
                    <h2 class="information-card__title">
                        お支払いについて
                    </h2>

                    <div class="information-card__body">
                        <p class="information-card__row">
                            支払期限：
                            {{ $dueDate }}
                        </p>

                        <p class="information-card__row">
                            振込手数料はご負担をお願いいたします。
                        </p>

                        <p class="information-card__row">
                            請求書番号をお控えください。
                        </p>
                    </div>
                </section>
            </td>
        </tr>
    </table>

    <section class="notes">
        <h2 class="notes__title">
            請求内容・備考
        </h2>

        <div class="notes__body">
            @if ($description !== '')
                {!! nl2br(e($description)) !!}
            @else
                備考はありません。
            @endif
        </div>
    </section>

    {{ $invoice->invoice_number }}
    /
    {{ $invoice->issuer_business_name ?: 'ClientWork Manager' }}
</body>

</html>