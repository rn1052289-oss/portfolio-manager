<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ClientWork Manager')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-sidebar__brand">
                <a href="/dashboard" class="admin-sidebar__brand-link">
                    <span class="admin-sidebar__logo">C</span>
                    <span class="admin-sidebar__brand-text">
                        ClientWork
                        <span>Manager</span>
                    </span>
                </a>
            </div>

            <nav class="admin-sidebar__nav" aria-label="管理画面メニュー">
                <a href="{{ route('dashboard') }}"
                    class="admin-sidebar__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__link-marker"></span>
                    <span>ダッシュボード</span>
                </a>

                <a href="{{ route('projects.index') }}"
                    class="admin-sidebar__link {{ request()->routeIs('projects.*') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__link-marker"></span>
                    <span>案件管理</span>
                </a>

                <a href="{{ route('categories.index') }}"
                    class="admin-sidebar__link {{ request()->routeIs('categories.*') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__link-marker"></span>
                    <span>カテゴリ管理</span>
                </a>

                <a href="#" class="admin-sidebar__link">
                    <span class="admin-sidebar__link-marker"></span>
                    <span>顧客管理</span>
                </a>

                <a href="{{ route('tags.index') }}" class="admin-sidebar__link" {{ request()->routeIs('tags.*') ? 'is-active' : '' }}">
                    <span class="admin-sidebar__link-marker"></span>
                    <span>タグ管理</span>
                </a>
            </nav>

            <div class="admin-sidebar__footer">
                <p class="admin-sidebar__user-label">ログイン中</p>
                <p class="admin-sidebar__user-name">
                    {{ auth()->user()?->name ?? 'ユーザー' }}
                </p>
            </div>
        </aside>

        <div class="admin-layout__body">
            <header class="admin-header">
                <div class="admin-header__content">
                    <p class="admin-header__label">
                        @yield('page-label', 'Workspace')
                    </p>

                    <h1 class="admin-header__title">
                        @yield('page-title', 'ClientWork Manager')
                    </h1>

                    <p class="admin-header__description">
                        @yield('page-description', '案件・顧客・タスクを整理して、日々の仕事を見える化します。')
                    </p>
                </div>

                <div class="admin-header__actions">
                    @yield('page-actions')

                    <div class="admin-user">
                        <div class="admin-user__avatar">
                            {{ mb_substr(auth()->user()?->name ?? 'U', 0, 1) }}
                        </div>

                        <div class="admin-user__body">
                            <p class="admin-user__name">
                                {{ auth()->user()?->name ?? 'ユーザー' }}
                            </p>
                            <p class="admin-user__role">
                                Owner
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="admin-logout">
                        @csrf
                        <button type="submit" class="admin-logout__button">
                            ログアウト
                        </button>
                    </form>
                </div>
            </header>

            <main class="admin-main">
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>