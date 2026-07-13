@extends('layouts.app')

@section('title', $customer->name . 'を編集 | ClientWork Manager')
@section('page-label', 'Customer Management')
@section('page-title', '顧客編集')
@section('page-description', '登録済みの顧客情報と名刺画像を編集します。')

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
            enctype="multipart/form-data"
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
                        顧客の基本情報と名刺画像を変更できます。
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
                        名刺画像の差し替えについて
                    </p>

                    <p class="customer-edit__notice-text">
                        新しい画像を選択した面だけ差し替えます。未選択の面は、現在登録されている画像を維持します。
                    </p>
                </div>
            </div>

            <div class="customer-form__sections">
                @include('customers.partials.form-fields', [
                    'customer' => $customer,
                ])

                <section
                    class="customer-form-section"
                    aria-labelledby="customer-business-card-edit-title"
                >
                    <header class="customer-form-section__header">
                        <h3
                            id="customer-business-card-edit-title"
                            class="customer-form-section__title"
                        >
                            名刺画像
                        </h3>

                        <p class="customer-form-section__description">
                            現在の画像を確認し、差し替えたい面だけ新しい画像を選択してください。
                        </p>
                    </header>

                    <div class="customer-edit-business-card__grid">
                        <article class="customer-edit-business-card">
                            <div class="customer-edit-business-card__header">
                                <p class="customer-edit-business-card__label">
                                    現在の名刺表面
                                </p>
                            </div>

                            <div class="customer-edit-business-card__preview">
                                <x-customers.business-card-image
                                    :path="$customer->business_card_front_path"
                                    side="名刺表面"
                                    :alt="$customer->name . 'の名刺表面'"
                                />
                            </div>

                            <div class="customer-edit-business-card__field">
                                <label
                                    for="business_card_front"
                                    class="customer-field__label"
                                >
                                    新しい表面画像
                                </label>

                                <input
                                    type="file"
                                    id="business_card_front"
                                    name="business_card_front"
                                    class="customer-field__control customer-field__file @error('business_card_front') is-error @enderror"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    aria-describedby="business-card-front-edit-help"
                                >

                                <p
                                    id="business-card-front-edit-help"
                                    class="customer-field__help customer-field__help--file"
                                >
                                    JPG・JPEG・PNG・WebP／最大5MB
                                    <br>
                                    未選択の場合は、現在の表面画像を維持します。
                                </p>

                                @error('business_card_front')
                                    <p class="customer-field__error">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </article>

                        <article class="customer-edit-business-card">
                            <div class="customer-edit-business-card__header">
                                <p class="customer-edit-business-card__label">
                                    現在の名刺裏面
                                </p>
                            </div>

                            <div class="customer-edit-business-card__preview">
                                <x-customers.business-card-image
                                    :path="$customer->business_card_back_path"
                                    side="名刺裏面"
                                    :alt="$customer->name . 'の名刺裏面'"
                                />
                            </div>

                            <div class="customer-edit-business-card__field">
                                <label
                                    for="business_card_back"
                                    class="customer-field__label"
                                >
                                    新しい裏面画像
                                </label>

                                <input
                                    type="file"
                                    id="business_card_back"
                                    name="business_card_back"
                                    class="customer-field__control customer-field__file @error('business_card_back') is-error @enderror"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    aria-describedby="business-card-back-edit-help"
                                >

                                <p
                                    id="business-card-back-edit-help"
                                    class="customer-field__help customer-field__help--file"
                                >
                                    JPG・JPEG・PNG・WebP／最大5MB
                                    <br>
                                    未選択の場合は、現在の裏面画像を維持します。
                                </p>

                                @error('business_card_back')
                                    <p class="customer-field__error">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </article>
                    </div>

                    <p class="customer-edit-business-card__validation-note">
                        バリデーションエラーが発生した場合、セキュリティ上の理由により選択した画像は保持されません。再度画像を選択してください。
                    </p>
                </section>
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