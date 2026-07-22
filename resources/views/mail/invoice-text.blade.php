@php
    $recipientName = $invoice->billing_contact_name
        ?: $invoice->billing_name
        ?: 'ご担当者';

    $issuerName = $invoice->issuer_business_name
        ?: $invoice->issuer_representative_name
        ?: 'ClientWork Manager';

    $dueDate = $invoice->due_date?->format('Y年n月j日')
        ?? '未設定';

    $currency = strtolower($invoice->currency);

    $formattedAmount = $currency === 'jpy'
        ? '¥' . number_format((int) $invoice->total_amount)
        : strtoupper($currency) . ' ' . number_format((int) $invoice->total_amount);
@endphp
{{ $recipientName }} 様

{!! $messageBody !!}

━━━━━━━━━━━━━━━━━━━━━━━━━━
請求書情報
━━━━━━━━━━━━━━━━━━━━━━━━━━

請求書番号：{{ $invoice->invoice_number }}
請求内容：{{ $invoice->title }}
支払期限：{{ $dueDate }}
ご請求金額：{{ $formattedAmount }}

@if (!empty($paymentUrl))
    ━━━━━━━━━━━━━━━━━━━━━━━━━━
    オンラインでのお支払い
    ━━━━━━━━━━━━━━━━━━━━━━━━━━

    以下のURLから、Stripeの安全な決済フォームを使用した
    オンライン支払い画面へ移動できます。

    {{ $paymentUrl }}

    支払期限：{{ $dueDate }}

@endif
詳細な請求内容と振込先は、添付されているPDFをご確認ください。

よろしくお願いいたします。

{{ $issuerName }}

このメールには請求書PDFが添付されています。