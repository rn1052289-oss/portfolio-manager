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
    {{-- =========================================
        Mobile Topbar
        PCでは非表示、768px以下で表示
    ========================================== --}}
    <header class="mobile-topbar">
        <a
            href="{{ route('dashboard') }}"
            class="mobile-topbar__logo-link"
            aria-label="ダッシュボードへ移動"
        >
            <span class="mobile-topbar__logo" aria-hidden="true">
                C
            </span>
        </a>

        <p class="mobile-topbar__title">
            @yield('page-title', 'ClientWork Manager')
        </p>

        <details class="mobile-user-menu">
    <summary
        class="mobile-user-menu__trigger"
        aria-label="ユーザーメニューを開く"
    >
        <svg
            class="mobile-user-menu__icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <circle cx="12" cy="8" r="4"></circle>
            <path d="M4.5 21a7.5 7.5 0 0 1 15 0"></path>
        </svg>
    </summary>

    <div class="mobile-user-menu__dropdown">
        <div class="mobile-user-menu__profile">
            <p class="mobile-user-menu__label">
                ログイン中
            </p>

            <p class="mobile-user-menu__name">
                {{ auth()->user()?->name ?? 'ユーザー' }}
            </p>

            <p class="mobile-user-menu__role">
                Owner
            </p>
        </div>

        <form
            action="{{ route('logout') }}"
            method="POST"
            class="mobile-user-menu__logout"
        >
            @csrf

            <button
                type="submit"
                class="mobile-user-menu__logout-button"
            >
                <svg
                    class="mobile-user-menu__logout-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M10 17l5-5-5-5"></path>
                    <path d="M15 12H3"></path>
                    <path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"></path>
                </svg>

                <span>
                    ログアウト
                </span>
            </button>
        </form>
    </div>
</details>
    </header>

    <div class="admin-layout">
        {{-- =========================================
            PC Sidebar
        ========================================== --}}
        <aside class="admin-sidebar">
            <div class="admin-sidebar__brand">
                <a
                    href="{{ route('dashboard') }}"
                    class="admin-sidebar__brand-link"
                >
                    <span class="admin-sidebar__logo">
                        C
                    </span>

                    <span class="admin-sidebar__brand-text">
                        ClientWork
                        <span>Manager</span>
                    </span>
                </a>
            </div>

            <nav
                class="admin-sidebar__nav"
                aria-label="管理画面メニュー"
            >
                <a
                    href="{{ route('dashboard') }}"
                    class="admin-sidebar__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
                    @if (request()->routeIs('dashboard'))
                        aria-current="page"
                    @endif
                >
                    <span class="admin-sidebar__link-marker"></span>
                    <span>ダッシュボード</span>
                </a>

                <a
                    href="{{ route('projects.index') }}"
                    class="admin-sidebar__link {{ request()->routeIs('projects.*') ? 'is-active' : '' }}"
                    @if (request()->routeIs('projects.*'))
                        aria-current="page"
                    @endif
                >
                    <span class="admin-sidebar__link-marker"></span>
                    <span>案件管理</span>
                </a>

                <a
                    href="{{ route('categories.index') }}"
                    class="admin-sidebar__link {{ request()->routeIs('categories.*') ? 'is-active' : '' }}"
                    @if (request()->routeIs('categories.*'))
                        aria-current="page"
                    @endif
                >
                    <span class="admin-sidebar__link-marker"></span>
                    <span>カテゴリ管理</span>
                </a>

                <a
    href="{{ route('customers.index') }}"
    class="admin-sidebar__link {{ request()->routeIs('customers.*') ? 'is-active' : '' }}"
    @if (request()->routeIs('customers.*'))
        aria-current="page"
    @endif
>
    <span class="admin-sidebar__link-marker"></span>
    <span>顧客管理</span>
</a>

                <a
                    href="{{ route('tags.index') }}"
                    class="admin-sidebar__link {{ request()->routeIs('tags.*') ? 'is-active' : '' }}"
                    @if (request()->routeIs('tags.*'))
                        aria-current="page"
                    @endif
                >
                    <span class="admin-sidebar__link-marker"></span>
                    <span>タグ管理</span>
                </a>
            </nav>

            <div class="admin-sidebar__footer">
                <p class="admin-sidebar__user-label">
                    ログイン中
                </p>

                <p class="admin-sidebar__user-name">
                    {{ auth()->user()?->name ?? 'ユーザー' }}
                </p>
            </div>
        </aside>

        {{-- =========================================
            Main Area
        ========================================== --}}
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
                        @yield(
                            'page-description',
                            '案件・顧客・タスクを整理して、日々の仕事を見える化します。'
                        )
                    </p>
                </div>

                <div class="admin-header__actions">
                    @hasSection('page-actions')
                        <div class="admin-header__page-actions">
                            @yield('page-actions')
                        </div>
                    @endif

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

                    <form
                        action="{{ route('logout') }}"
                        method="POST"
                        class="admin-logout"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="admin-logout__button"
                        >
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

    {{-- =========================================
        Mobile Bottom Navigation
        PCでは非表示、768px以下で表示
    ========================================== --}}
    <nav
        class="mobile-bottom-nav"
        aria-label="スマートフォン用メインメニュー"
    >
        <a
            href="{{ route('dashboard') }}"
            class="mobile-bottom-nav__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
            @if (request()->routeIs('dashboard'))
                aria-current="page"
            @endif
        >
            <svg
                class="mobile-bottom-nav__icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M3 10.5 12 3l9 7.5"></path>
                <path d="M5 9.5V21h14V9.5"></path>
                <path d="M9 21v-6h6v6"></path>
            </svg>

            <span class="mobile-bottom-nav__label">
                ホーム
            </span>
        </a>

        <a
            href="{{ route('projects.index') }}"
            class="mobile-bottom-nav__link {{ request()->routeIs('projects.*') ? 'is-active' : '' }}"
            @if (request()->routeIs('projects.*'))
                aria-current="page"
            @endif
        >
            <svg
                class="mobile-bottom-nav__icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M3 7.5h6l2 2H21v10.5H3z"></path>
                <path d="M3 7.5V5h6l2 2"></path>
            </svg>

            <span class="mobile-bottom-nav__label">
                案件
            </span>
        </a>

        <a
            href="{{ route('categories.index') }}"
            class="mobile-bottom-nav__link {{ request()->routeIs('categories.*') ? 'is-active' : '' }}"
            @if (request()->routeIs('categories.*'))
                aria-current="page"
            @endif
        >
            <svg
                class="mobile-bottom-nav__icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <rect
                    x="3"
                    y="3"
                    width="7"
                    height="7"
                    rx="2"
                ></rect>

                <rect
                    x="14"
                    y="3"
                    width="7"
                    height="7"
                    rx="2"
                ></rect>

                <rect
                    x="3"
                    y="14"
                    width="7"
                    height="7"
                    rx="2"
                ></rect>

                <rect
                    x="14"
                    y="14"
                    width="7"
                    height="7"
                    rx="2"
                ></rect>
            </svg>

            <span class="mobile-bottom-nav__label">
                カテゴリ
            </span>
        </a>

        <a
            href="{{ route('tags.index') }}"
            class="mobile-bottom-nav__link {{ request()->routeIs('tags.*') ? 'is-active' : '' }}"
            @if (request()->routeIs('tags.*'))
                aria-current="page"
            @endif
        >
            <svg
                class="mobile-bottom-nav__icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="M20.5 13.5 13.5 20.5a2.1 2.1 0 0 1-3 0l-7-7A2 2 0 0 1 3 12V5a2 2 0 0 1 2-2h7a2 2 0 0 1 1.5.5l7 7a2.1 2.1 0 0 1 0 3z"></path>

                <circle
                    cx="8.5"
                    cy="8.5"
                    r="1.2"
                ></circle>
            </svg>

            <span class="mobile-bottom-nav__label">
                タグ
            </span>
        </a>

        <a
    href="{{ route('customers.index') }}"
    class="mobile-bottom-nav__link {{ request()->routeIs('customers.*') ? 'is-active' : '' }}"
    @if (request()->routeIs('customers.*'))
        aria-current="page"
    @endif
>
    <svg
        class="mobile-bottom-nav__icon"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path d="M4 21v-9a2 2 0 0 1 2-2h4"></path>
        <path d="M14 5h4a2 2 0 0 1 2 2v16"></path>
        <path d="M8 10V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v16"></path>
        <path d="M3 21h18"></path>
        <path d="M11 7h2"></path>
        <path d="M11 11h2"></path>
        <path d="M11 15h2"></path>
        <path d="M17 9h1"></path>
        <path d="M17 13h1"></path>
    </svg>

    <span class="mobile-bottom-nav__label">
        顧客
    </span>
</a>
    </nav>
</body>

</html>