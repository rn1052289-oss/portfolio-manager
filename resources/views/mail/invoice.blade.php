<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        請求書のご案内
    </title>
</head>

<body style="
        margin: 0;
        padding: 0;
        color: #1e293b;
        background-color: #f1f5f9;
        font-family:
            -apple-system,
            BlinkMacSystemFont,
            'Segoe UI',
            sans-serif;
    ">
    @php
        $recipientName = $invoice->billing_contact_name
            ?: $invoice->billing_name
            ?: 'ご担当者';

        $issuerName = $invoice->issuer_business_name
            ?: $invoice->issuer_representative_name
            ?: 'ClientWork Manager';

        $dueDate = $invoice->due_date?->format('Y年n月j日')
            ?? '未設定';
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="
            width: 100%;
            background-color: #f1f5f9;
        ">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="
                        width: 100%;
                        max-width: 640px;
                        overflow: hidden;
                        background-color: #ffffff;
                        border: 1px solid #e2e8f0;
                        border-radius: 16px;
                    ">
                    <tr>
                        <td style="
                                padding: 28px 32px;
                                color: #ffffff;
                                background-color: #172554;
                            ">
                            <p style="
                                    margin: 0;
                                    color: #93c5fd;
                                    font-size: 11px;
                                    font-weight: 700;
                                    letter-spacing: 0.12em;
                                    text-transform: uppercase;
                                ">
                                Invoice
                            </p>

                            <h1 style="
                                    margin: 8px 0 0;
                                    font-size: 24px;
                                    line-height: 1.4;
                                ">
                                請求書のご案内
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px;">
                            <p style="
                                    margin: 0;
                                    font-size: 15px;
                                    font-weight: 700;
                                    line-height: 1.8;
                                ">
                                {{ $recipientName }} 様
                            </p>

                            <div style="
                                    margin-top: 20px;
                                    color: #475569;
                                    font-size: 14px;
                                    line-height: 1.9;
                                    overflow-wrap: anywhere;
                                ">
                                {!! nl2br(e($messageBody)) !!}
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="
                                    width: 100%;
                                    margin-top: 28px;
                                    border-collapse: collapse;
                                    background-color: #f8fafc;
                                    border: 1px solid #e2e8f0;
                                    border-radius: 12px;
                                ">
                                <tr>
                                    <td colspan="2" style="
                                            padding: 18px 20px;
                                            color: #0f172a;
                                            font-size: 15px;
                                            font-weight: 700;
                                            border-bottom: 1px solid #e2e8f0;
                                        ">
                                        請求書情報
                                    </td>
                                </tr>

                                <tr>
                                    <td style="
                                            width: 38%;
                                            padding: 13px 20px;
                                            color: #64748b;
                                            font-size: 12px;
                                            font-weight: 700;
                                            border-bottom: 1px solid #e2e8f0;
                                        ">
                                        請求書番号
                                    </td>

                                    <td style="
                                            padding: 13px 20px;
                                            color: #1e293b;
                                            font-size: 13px;
                                            font-weight: 700;
                                            text-align: right;
                                            border-bottom: 1px solid #e2e8f0;
                                            overflow-wrap: anywhere;
                                        ">
                                        {{ $invoice->invoice_number }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="
                                            padding: 13px 20px;
                                            color: #64748b;
                                            font-size: 12px;
                                            font-weight: 700;
                                            border-bottom: 1px solid #e2e8f0;
                                        ">
                                        請求内容
                                    </td>

                                    <td style="
                                            padding: 13px 20px;
                                            color: #1e293b;
                                            font-size: 13px;
                                            font-weight: 700;
                                            text-align: right;
                                            border-bottom: 1px solid #e2e8f0;
                                            overflow-wrap: anywhere;
                                        ">
                                        {{ $invoice->title }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="
                                            padding: 13px 20px;
                                            color: #64748b;
                                            font-size: 12px;
                                            font-weight: 700;
                                            border-bottom: 1px solid #e2e8f0;
                                        ">
                                        支払期限
                                    </td>

                                    <td style="
                                            padding: 13px 20px;
                                            color: #1e293b;
                                            font-size: 13px;
                                            font-weight: 700;
                                            text-align: right;
                                            border-bottom: 1px solid #e2e8f0;
                                        ">
                                        {{ $dueDate }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="
                                            padding: 16px 20px;
                                            color: #0f172a;
                                            font-size: 13px;
                                            font-weight: 700;
                                        ">
                                        ご請求金額
                                    </td>

                                    <td style="
                                            padding: 16px 20px;
                                            color: #1d4ed8;
                                            font-size: 22px;
                                            font-weight: 800;
                                            text-align: right;
                                        ">
                                        ¥{{ number_format((int) $invoice->total_amount) }}
                                    </td>
                                </tr>
                            </table>

                            <div style="
                                    margin-top: 24px;
                                    padding: 16px 18px;
                                    color: #475569;
                                    font-size: 12px;
                                    line-height: 1.8;
                                    background-color: #eff6ff;
                                    border: 1px solid #bfdbfe;
                                    border-radius: 10px;
                                ">
                                詳細な請求内容と振込先は、添付されているPDFをご確認ください。
                            </div>

                            <p style="
                                    margin: 28px 0 0;
                                    color: #475569;
                                    font-size: 13px;
                                    line-height: 1.8;
                                ">
                                よろしくお願いいたします。
                            </p>

                            <p style="
                                    margin: 8px 0 0;
                                    color: #0f172a;
                                    font-size: 13px;
                                    font-weight: 700;
                                    line-height: 1.7;
                                ">
                                {{ $issuerName }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="
                                padding: 18px 32px;
                                color: #94a3b8;
                                font-size: 10px;
                                line-height: 1.7;
                                background-color: #f8fafc;
                                border-top: 1px solid #e2e8f0;
                                text-align: center;
                            ">
                            このメールには請求書PDFが添付されています。
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>