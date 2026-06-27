<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ClientWork Manager')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <main class="auth-page">
        @yield('content')
    </main>
</body>

</html>