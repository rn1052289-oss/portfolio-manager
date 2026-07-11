@extends('layouts.app')

@section('title', '顧客登録 | ClientWork Manager')
@section('page-label', 'Customer Management')
@section('page-title', '顧客登録')
@section('page-description', '顧客の基本情報や連絡先を登録します。')

@section('page-actions')
    <a href="{{ route('customers.index') }}" class="customer-create-header-action">
        顧客一覧へ戻る
    </a>
@endsection

@section('content')
    <section class="customer-create">
        <form action="{{ route('customers.store') }}" method="POST" class="customer-form" novalidate>
            @csrf

            @if ($errors->any())
                <div class="customer-form__error-summary" role="alert">
                    <p class="customer-form__error-title">
                        入力内容を確認してください。
                    </p>

                    <ul class="customer-form__error-list">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="customer-form__intro">
                <div>
                    <p class="customer-form__eyebrow">
                        Customer Profile
                    </p>

                    <h2 class="customer-form__heading">
                        顧客情報
                    </h2>

                    <p class="customer-form__description">
                        顧客名は必須です。担当者名・連絡先・住所・メモは任意で登録できます。
                    </p>
                </div>

                <span class="customer-form__required-guide">
                    <span aria-hidden="true">*</span>
                    必須項目
                </span>
            </div>

            <div class="customer-form__sections">
                {{-- 基本情報 --}}
                <fieldset class="customer-form-section">
                    <legend class="customer-form-section__legend">
                        基本情報
                    </legend>

                    <p class="customer-form-section__description">
                        顧客の会社名や担当者名を入力します。
                    </p>

                    <div class="customer-form-grid">
                        <div class="customer-field customer-field--full">
                            <label for="name" class="customer-field__label">
                                顧客名
                                <span class="customer-field__required" aria-label="必須">
                                    *
                                </span>
                            </label>

                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                class="customer-field__control @error('name') is-error @enderror" maxlength="255"
                                autocomplete="organization" placeholder="例：株式会社サンプル" required>

                            @error('name')
                                <p class="customer-field__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="customer-field customer-field--full">
                            <label for="contact_name" class="customer-field__label">
                                担当者名
                            </label>

                            <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}"
                                class="customer-field__control @error('contact_name') is-error @enderror" maxlength="255"
                                autocomplete="name" placeholder="例：山田 太郎">

                            @error('contact_name')
                                <p class="customer-field__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </fieldset>

                {{-- 連絡先 --}}
                <fieldset class="customer-form-section">
                    <legend class="customer-form-section__legend">
                        連絡先
                    </legend>

                    <p class="customer-form-section__description">
                        メールアドレスや電話番号を登録します。
                    </p>

                    <div class="customer-form-grid">
                        <div class="customer-field">
                            <label for="email" class="customer-field__label">
                                メールアドレス
                            </label>

                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                class="customer-field__control @error('email') is-error @enderror" maxlength="255"
                                autocomplete="email" inputmode="email" placeholder="例：customer@example.com">

                            @error('email')
                                <p class="customer-field__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="customer-field">
                            <label for="phone" class="customer-field__label">
                                電話番号
                            </label>

                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                class="customer-field__control @error('phone') is-error @enderror" maxlength="50"
                                autocomplete="tel" inputmode="tel" placeholder="例：03-1234-5678">

                            @error('phone')
                                <p class="customer-field__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </fieldset>

                {{-- 住所 --}}
                <fieldset class="customer-form-section">
                    <legend class="customer-form-section__legend">
                        住所
                    </legend>

                    <p class="customer-form-section__description">
                        請求書や郵送物に使用する住所を登録できます。
                    </p>

                    <div class="customer-form-grid">
                        <div class="customer-field">
                            <label for="postal_code" class="customer-field__label">
                                郵便番号
                            </label>

                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}"
                                class="customer-field__control @error('postal_code') is-error @enderror" maxlength="20"
                                autocomplete="postal-code" inputmode="numeric" placeholder="例：100-0001">

                            @error('postal_code')
                                <p class="customer-field__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="customer-field customer-field--full">
                            <label for="address" class="customer-field__label">
                                住所
                            </label>

                            <input type="text" id="address" name="address" value="{{ old('address') }}"
                                class="customer-field__control @error('address') is-error @enderror" maxlength="255"
                                autocomplete="street-address" placeholder="例：東京都千代田区千代田1-1">

                            @error('address')
                                <p class="customer-field__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </fieldset>

                {{-- メモ --}}
                <fieldset class="customer-form-section">
                    <legend class="customer-form-section__legend">
                        メモ
                    </legend>

                    <p class="customer-form-section__description">
                        顧客対応で共有しておきたい情報を記録できます。
                    </p>

                    <div class="customer-field customer-field--full">
                        <label for="memo" class="customer-field__label">
                            顧客メモ
                        </label>

                        <textarea id="memo" name="memo"
                            class="customer-field__control customer-field__textarea @error('memo') is-error @enderror"
                            rows="6" maxlength="2000" placeholder="例：連絡はメールを希望。毎週水曜日は定休日。">{{ old('memo') }}</textarea>

                        <div class="customer-field__footer">
                            <p class="customer-field__help">
                                連絡方法や注意事項などを入力してください。
                            </p>

                            <span class="customer-field__limit">
                                2000文字以内
                            </span>
                        </div>

                        @error('memo')
                            <p class="customer-field__error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </fieldset>
            </div>

            <div class="customer-form__actions">
                <a href="{{ route('customers.index') }}" class="customer-form__button customer-form__button--secondary">
                    キャンセル
                </a>

                <button type="submit" class="customer-form__button customer-form__button--primary">
                    顧客を登録する
                </button>
            </div>
        </form>
    </section>
@endsection