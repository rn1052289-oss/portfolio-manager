@extends('layouts.auth')

@section('title', '新規登録 | ClientWork Manager')

@section('content')
    <div class="auth-card">
        <div class="auth-card__header">
            <div class="auth-logo">
                <span class="auth-logo__mark">C</span>
                <span class="auth-logo__text">ClientWork Manager</span>
            </div>

            <p class="auth-card__label">Create account</p>
            <h1 class="auth-card__title">新規登録</h1>
            <p class="auth-card__lead">
                案件・顧客・タスクを一元管理するためのアカウントを作成します。
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

        <form action="{{ route('register') }}" method="POST" class="auth-form">
            @csrf

            <div class="form-field">
                <label for="name" class="form-label">お名前</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                    class="form-input @error('name') is-error @enderror" placeholder="山田 太郎" autocomplete="name" autofocus
                    required>
                @error('name')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="email" class="form-label">メールアドレス</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    class="form-input @error('email') is-error @enderror" placeholder="example@example.com"
                    autocomplete="email" required>
                @error('email')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password" class="form-label">パスワード</label>
                <input id="password" type="password" name="password"
                    class="form-input @error('password') is-error @enderror" placeholder="8文字以上で入力"
                    autocomplete="new-password" required>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="password_confirmation" class="form-label">パスワード確認</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-input"
                    placeholder="もう一度パスワードを入力" autocomplete="new-password" required>
            </div>

            <button type="submit" class="auth-button">
                アカウントを作成
            </button>
        </form>

        <p class="auth-switch">
            すでにアカウントをお持ちの方は
            <a href="{{ route('login') }}">ログイン</a>
        </p>
    </div>
@endsection