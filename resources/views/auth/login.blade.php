@extends('layouts.auth')

@section('title', 'ログイン | ClientWork Manager')

@section('content')
    <div class="auth-card">
        <div class="auth-card__header">
            <div class="auth-logo">
                <span class="auth-logo__mark">C</span>
                <span class="auth-logo__text">ClientWork Manager</span>
            </div>

            <p class="auth-card__label">Welcome back</p>
            <h1 class="auth-card__title">ログイン</h1>
            <p class="auth-card__lead">
                案件・顧客・タスクをまとめて管理しましょう。
            </p>
        </div>

        @if ($errors->any())
            <div class="auth-alert" role="alert">
                <p class="auth-alert__title">入力内容を確認してください</p>
                <ul class="auth-alert__list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="auth-form">
            @csrf

            <div class="form-field">
                <label for="email" class="form-label">メールアドレス</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    class="form-input @error('email') is-error @enderror" placeholder="example@example.com"
                    autocomplete="email" autofocus required>
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password" class="form-label">パスワード</label>
                <input id="password" type="password" name="password"
                    class="form-input @error('password') is-error @enderror" placeholder="パスワードを入力"
                    autocomplete="current-password" required>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="auth-form__row">
                <label class="form-check">
                    <input type="checkbox" name="remember" value="1" class="form-check__input">
                    <span>ログイン状態を保持する</span>
                </label>
            </div>

            <button type="submit" class="auth-button">
                ログイン
            </button>
        </form>

        <p class="auth-switch">
            アカウントをお持ちでない方は
            <a href="{{ route('register') }}">新規登録</a>
        </p>
    </div>
@endsection