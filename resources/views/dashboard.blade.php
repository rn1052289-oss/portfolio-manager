<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード | ClientWork Manager</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <main style="min-height: 100vh; padding: 40px; background: #f4f7fb;">
        <section
            style="max-width: 880px; margin: 0 auto; padding: 32px; border-radius: 24px; background: #ffffff; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);">
            <p style="margin: 0 0 8px; color: #2563eb; font-size: 13px; font-weight: 700; letter-spacing: 0.08em;">
                DASHBOARD
            </p>

            <h1 style="margin: 0 0 12px; color: #0f172a; font-size: 32px;">
                ClientWork Manager
            </h1>

            <p style="margin: 0 0 24px; color: #64748b; line-height: 1.8;">
                ログインに成功しました。ここは認証済みユーザーだけが見られるページです。
            </p>

            <div style="padding: 20px; border: 1px solid #dbe3ef; border-radius: 16px; background: #f8fafc;">
                <p style="margin: 0 0 8px; color: #64748b;">ログイン中のユーザー</p>
                <p style="margin: 0; color: #0f172a; font-weight: 700;">
                    {{ auth()->user()->name }} / {{ auth()->user()->email }}
                </p>
            </div>

            <form action="{{ route('logout') }}" method="POST" style="margin-top: 24px;">
                @csrf

                <button type="submit"
                    style="display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: 0 18px; border: none; border-radius: 12px; color: #ffffff; font-weight: 700; background: #0f172a; cursor: pointer;">
                    ログアウト
                </button>
            </form>
        </section>
    </main>
</body>

</html>