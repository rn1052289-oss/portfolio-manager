@extends('layouts.app')

@section('title', '顧客登録 | ClientWork Manager')
@section('page-label', 'Customer Management')
@section('page-title', '顧客登録')
@section('page-description', '顧客の基本情報や連絡先を登録します。')

@section('page-actions')
    <a
        href="{{ route('customers.index') }}"
        class="customer-create-header-action"
    >
        顧客一覧へ戻る
    </a>
@endsection

@section('content')
    <section class="customer-create">
        <form
            action="{{ route('customers.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="customer-form"
            novalidate
        >
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
                @include('customers.partials.form-fields', [
                    'customer' => null,
                ])

                {{-- 名刺画像 --}}
                <fieldset class="customer-form-section">
                    <legend class="customer-form-section__legend">
                        名刺画像
                    </legend>

                    <p class="customer-form-section__description">
                        名刺の表面・裏面をそれぞれ登録できます。どちらも任意です。
                    </p>

                    <div class="customer-form-grid">
                        <div class="customer-field">
                            <label
                                for="business_card_front"
                                class="customer-field__label"
                            >
                                名刺表面
                            </label>

                            <input
                                type="file"
                                id="business_card_front"
                                name="business_card_front"
                                class="customer-field__control customer-field__file @error('business_card_front') is-error @enderror"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                            <p class="customer-field__help customer-field__help--file">
                                JPG・JPEG・PNG・WebP／最大5MB
                            </p>

                            @error('business_card_front')
                                <p class="customer-field__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="customer-field">
                            <label
                                for="business_card_back"
                                class="customer-field__label"
                            >
                                名刺裏面
                            </label>

                            <input
                                type="file"
                                id="business_card_back"
                                name="business_card_back"
                                class="customer-field__control customer-field__file @error('business_card_back') is-error @enderror"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                            <p class="customer-field__help customer-field__help--file">
                                JPG・JPEG・PNG・WebP／最大5MB
                            </p>

                            @error('business_card_back')
                                <p class="customer-field__error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </fieldset>
            </div>

            <div class="customer-form__actions">
                <a
                    href="{{ route('customers.index') }}"
                    class="customer-form__button customer-form__button--secondary"
                >
                    キャンセル
                </a>

                <button
                    type="submit"
                    class="customer-form__button customer-form__button--primary"
                >
                    顧客を登録する
                </button>
            </div>
        </form>
    </section>
@endsection