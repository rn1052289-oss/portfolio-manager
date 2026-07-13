@extends('layouts.app')

@section('title', $customer->name . 'を編集 | ClientWork Manager')
@section('page-label', 'Customer Management')
@section('page-title', '顧客編集')
@section('page-description', '登録済みの顧客情報を編集します。')

@section('page-actions')
    <a
        href="{{ route('customers.show', $customer) }}"
        class="customer-edit-header-action"
    >
        顧客詳細へ戻る
    </a>
@endsection

@section('content')
    <section class="customer-edit">
        <form
            action="{{ route('customers.update', $customer) }}"
            method="POST"
            class="customer-form"
            novalidate
        >
            @csrf
            @method('PATCH')

            <div class="customer-form__intro">
                <div>
                    <p class="customer-form__eyebrow">
                        Customer Profile
                    </p>

                    <h2 class="customer-form__heading">
                        顧客情報を編集
                    </h2>

                    <p class="customer-form__description">
                        顧客名、担当者名、連絡先、住所、メモを変更できます。
                    </p>
                </div>

                <span class="customer-form__required-guide">
                    <span aria-hidden="true">*</span>
                    必須項目
                </span>
            </div>

            @if ($errors->any())
                <div
                    class="customer-form__error-summary"
                    role="alert"
                    aria-labelledby="customer-edit-error-title"
                >
                    <p
                        id="customer-edit-error-title"
                        class="customer-form__error-title"
                    >
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

            <div class="customer-edit__notice" role="note">
                <svg
                    class="customer-edit__notice-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 11v5"></path>
                    <path d="M12 8h.01"></path>
                </svg>

                <div>
                    <p class="customer-edit__notice-title">
                        名刺画像について
                    </p>

                    <p class="customer-edit__notice-text">
                        この画面では名刺画像を変更しません。登録済みの表面・裏面画像はそのまま維持されます。
                    </p>
                </div>
            </div>

            <div class="customer-form__sections">
                @include('customers.partials.form-fields', [
                    'customer' => $customer,
                ])
            </div>

            <div class="customer-form__actions">
                <p class="customer-form__actions-note">
                    保存後、顧客詳細画面へ戻ります。
                </p>

                <div class="customer-form__action-buttons">
                    <a
                        href="{{ route('customers.show', $customer) }}"
                        class="customer-form__button customer-form__button--secondary"
                    >
                        キャンセル
                    </a>

                    <button
                        type="submit"
                        class="customer-form__button customer-form__button--primary"
                    >
                        変更を保存する
                    </button>
                </div>
            </div>
        </form>
    </section>
@endsection
