<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>タスク期限のお知らせ</title>

    <style>
        @media only screen and (max-width: 640px) {
            .email-container {
                width: 100% !important;
            }

            .email-content {
                padding: 24px 14px !important;
            }

            .email-header {
                padding: 26px 20px !important;
            }

            .task-card {
                padding: 20px 16px !important;
            }

            .email-footer {
                padding: 22px 16px !important;
            }
        }
    </style>
</head>

<body style="
        margin: 0;
        padding: 0;
        background-color: #eef2f7;
        color: #0f172a;
        font-family:
            -apple-system,
            BlinkMacSystemFont,
            'Segoe UI',
            'Hiragino Sans',
            'Hiragino Kaku Gothic ProN',
            'Yu Gothic',
            Meiryo,
            sans-serif;
    ">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="
        width: 100%;
        background-color: #eef2f7;
        border-collapse: collapse;
    ">
        <tr>
            <td align="center" style="padding: 32px 12px;">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0"
                    class="email-container" style="
                    width: 100%;
                    max-width: 640px;
                    overflow: hidden;
                    background-color: #ffffff;
                    border-collapse: separate;
                    border-spacing: 0;
                    border-radius: 16px;
                    box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
                ">
                    <tr>
                        <td class="email-header" style="
                            padding: 30px 34px;
                            background-color: #0f172a;
                        ">
                            <p style="
                                margin: 0 0 6px;
                                color: #93c5fd;
                                font-size: 13px;
                                font-weight: 700;
                                letter-spacing: 0.08em;
                            ">
                                CLIENTWORK MANAGER
                            </p>

                            <h1 style="
                                margin: 0;
                                color: #ffffff;
                                font-size: 24px;
                                font-weight: 700;
                                line-height: 1.5;
                            ">
                                タスク期限のお知らせ
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-content" style="padding: 34px;">
                            <p style="
                                margin: 0 0 14px;
                                color: #0f172a;
                                font-size: 16px;
                                line-height: 1.8;
                            ">
                                {{ $recipientName }}さん
                            </p>

                            <p style="
                                margin: 0;
                                color: #475569;
                                font-size: 15px;
                                line-height: 1.9;
                            ">
                                期限の確認が必要なタスクが
                                <strong style="color: #2563eb;">
                                    {{ $taskCount }}件
                                </strong>
                                あります。
                                <br>
                                納期遅延を防ぐため、タスクの状況をご確認ください。
                            </p>

                            @foreach ($reminders as $reminder)
                                @php
                                    $notificationBackground = match (
                                    $reminder['notification_type']
                                    ) {
                                        \App\Models\TaskDueNotificationHistory::TYPE_OVERDUE
                                        => '#fef2f2',
                                        \App\Models\TaskDueNotificationHistory::TYPE_DUE_TODAY
                                        => '#fff7ed',
                                        \App\Models\TaskDueNotificationHistory::TYPE_ONE_DAY_BEFORE
                                        => '#fffbeb',
                                        default => '#eff6ff',
                                    };

                                    $notificationColor = match (
                                    $reminder['notification_type']
                                    ) {
                                        \App\Models\TaskDueNotificationHistory::TYPE_OVERDUE
                                        => '#b91c1c',
                                        \App\Models\TaskDueNotificationHistory::TYPE_DUE_TODAY
                                        => '#c2410c',
                                        \App\Models\TaskDueNotificationHistory::TYPE_ONE_DAY_BEFORE
                                        => '#a16207',
                                        default => '#1d4ed8',
                                    };

                                    $priorityColor = match (
                                    $reminder['priority']
                                    ) {
                                        \App\Models\Task::PRIORITY_HIGH
                                        => '#b91c1c',
                                        \App\Models\Task::PRIORITY_MEDIUM
                                        => '#c2410c',
                                        default => '#475569',
                                    };
                                @endphp

                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="
                                        width: 100%;
                                        margin-top: 26px;
                                        border-collapse: separate;
                                        border-spacing: 0;
                                        border: 1px solid #e2e8f0;
                                        border-radius: 12px;
                                    ">
                                    <tr>
                                        <td class="task-card" style="padding: 24px;">
                                            <span style="
                                                    display: inline-block;
                                                    margin-bottom: 16px;
                                                    padding: 5px 11px;
                                                    border-radius: 999px;
                                                    background-color:
                                                        {{ $notificationBackground }};
                                                    color:
                                                        {{ $notificationColor }};
                                                    font-size: 12px;
                                                    font-weight: 700;
                                                    line-height: 1.5;
                                                ">
                                                {{ $reminder['notification_label'] }}
                                            </span>

                                            <p style="
                                                    margin: 0 0 4px;
                                                    color: #64748b;
                                                    font-size: 12px;
                                                    font-weight: 600;
                                                    line-height: 1.6;
                                                ">
                                                案件
                                            </p>

                                            <p style="
                                                    margin: 0 0 18px;
                                                    color: #334155;
                                                    font-size: 14px;
                                                    font-weight: 700;
                                                    line-height: 1.7;
                                                    word-break: break-word;
                                                ">
                                                {{ $reminder['project_title'] }}
                                            </p>

                                            <h2 style="
                                                    margin: 0;
                                                    color: #0f172a;
                                                    font-size: 20px;
                                                    font-weight: 700;
                                                    line-height: 1.6;
                                                    word-break: break-word;
                                                ">
                                                {{ $reminder['task_title'] }}
                                            </h2>

                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                                border="0" style="
                                                    width: 100%;
                                                    margin-top: 20px;
                                                    border-collapse: collapse;
                                                ">
                                                <tr>
                                                    <td style="
                                                            width: 34%;
                                                            padding: 10px 8px 10px 0;
                                                            border-bottom:
                                                                1px solid #e2e8f0;
                                                            color: #64748b;
                                                            font-size: 13px;
                                                            vertical-align: top;
                                                        ">
                                                        期限
                                                    </td>

                                                    <td style="
                                                            padding: 10px 0;
                                                            border-bottom:
                                                                1px solid #e2e8f0;
                                                            color: #0f172a;
                                                            font-size: 14px;
                                                            font-weight: 700;
                                                            vertical-align: top;
                                                        ">
                                                        {{ $reminder['due_date'] }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td style="
                                                            width: 34%;
                                                            padding: 10px 8px 10px 0;
                                                            border-bottom:
                                                                1px solid #e2e8f0;
                                                            color: #64748b;
                                                            font-size: 13px;
                                                            vertical-align: top;
                                                        ">
                                                        優先度
                                                    </td>

                                                    <td style="
                                                            padding: 10px 0;
                                                            border-bottom:
                                                                1px solid #e2e8f0;
                                                            color:
                                                                {{ $priorityColor }};
                                                            font-size: 14px;
                                                            font-weight: 700;
                                                            vertical-align: top;
                                                        ">
                                                        {{ $reminder['priority_label'] }}
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td style="
                                                            width: 34%;
                                                            padding: 10px 8px 10px 0;
                                                            color: #64748b;
                                                            font-size: 13px;
                                                            vertical-align: top;
                                                        ">
                                                        ステータス
                                                    </td>

                                                    <td style="
                                                            padding: 10px 0;
                                                            color: #0f172a;
                                                            font-size: 14px;
                                                            font-weight: 700;
                                                            vertical-align: top;
                                                        ">
                                                        {{ $reminder['status_label'] }}
                                                    </td>
                                                </tr>
                                            </table>

                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                                border="0" style="
                                                    width: 100%;
                                                    margin-top: 22px;
                                                    border-collapse: collapse;
                                                ">
                                                <tr>
                                                    <td>
                                                        <a href="{{ $reminder['edit_url'] }}" style="
                                                                display: block;
                                                                box-sizing: border-box;
                                                                width: 100%;
                                                                padding: 13px 18px;
                                                                border-radius: 8px;
                                                                background-color: #2563eb;
                                                                color: #ffffff;
                                                                font-size: 14px;
                                                                font-weight: 700;
                                                                line-height: 1.5;
                                                                text-align: center;
                                                                text-decoration: none;
                                                            ">
                                                            タスクを確認・編集する
                                                        </a>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td style="
                                                            padding-top: 14px;
                                                            text-align: center;
                                                        ">
                                                        <a href="{{ $reminder['task_list_url'] }}" style="
                                                                color: #2563eb;
                                                                font-size: 13px;
                                                                line-height: 1.7;
                                                                text-decoration: underline;
                                                            ">
                                                            この案件のタスク一覧を開く
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="
                                width: 100%;
                                margin-top: 30px;
                                border-collapse: collapse;
                            ">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $dashboardUrl }}" style="
                                            display: inline-block;
                                            padding: 13px 24px;
                                            border: 1px solid #cbd5e1;
                                            border-radius: 8px;
                                            background-color: #ffffff;
                                            color: #334155;
                                            font-size: 14px;
                                            font-weight: 700;
                                            line-height: 1.5;
                                            text-decoration: none;
                                        ">
                                            ダッシュボードを開く
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="
                                margin: 30px 0 0;
                                padding-top: 24px;
                                border-top: 1px solid #e2e8f0;
                                color: #64748b;
                                font-size: 12px;
                                line-height: 1.8;
                            ">
                                このメールは、ClientWork Managerに登録された
                                タスク期限をもとに自動送信されています。
                                <br>
                                リンクを開いた際にログイン状態が切れている場合は、
                                ログイン画面が表示されます。
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-footer" style="
                            padding: 24px 34px;
                            background-color: #f8fafc;
                            color: #94a3b8;
                            font-size: 12px;
                            line-height: 1.7;
                            text-align: center;
                        ">
                            {{ config('app.name') }}
                            <br>
                            タスクと案件の進行を一元管理
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>