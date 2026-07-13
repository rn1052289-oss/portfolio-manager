@extends('layouts.app')

@section('title', $customer->name . ' | 顧客詳細')
@section('page-label', 'Customer Detail')
@section('page-title', '顧客詳細')
@section('page-description', '顧客の基本情報、連絡先、名刺画像を確認できます。')

@section('page-actions')
    <div class="customer-show-header-actions">
        <a href="{{ route('customers.index') }}" class="customer-show-header-action">
            顧客一覧へ戻る
        </a>

        <a href="{{ route('customers.edit', $customer) }}"
            class="customer-show-header-action customer-show-header-action--primary">
            顧客情報を編集
        </a>
    </div>
@endsection

@section('content')
    <section class="customer-show">
        @if (session('success'))
            <div class="customer-show__flash" role="status" aria-live="polite">
                <svg class="customer-show__flash-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="m8.5 12 2.2 2.2 4.8-5"></path>
                </svg>

                <span>
                    {{ session('success') }}
                </span>
            </div>
        @endif

        <div class="customer-show__hero">
            <div class="customer-show__avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M4 21v-9a2 2 0 0 1 2-2h4"></path>
                    <path d="M14 5h4a2 2 0 0 1 2 2v14"></path>
                    <path d="M8 10V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v16"></path>
                    <path d="M3 21h18"></path>
                </svg>
            </div>

            <div class="customer-show__hero-body">
                <p class="customer-show__number">
                    Customer #{{ $customer->id }}
                </p>

                <h2 class="customer-show__name">
                    {{ $customer->name }}
                </h2>

                <p class="customer-show__hero-description">
                    連絡先、住所、メモ、名刺画像、更新履歴をまとめて確認できます。
                </p>
            </div>
        </div>

        <div class="customer-show__layout">
            <div class="customer-show__main">
                <section class="customer-show-panel">
                    <header class="customer-show-panel__header">
                        <div>
                            <p class="customer-show-panel__eyebrow">
                                Profile
                            </p>

                            <h3 class="customer-show-panel__title">
                                基本情報
                            </h3>
                        </div>
                    </header>

                    <dl class="customer-information">
                        <div class="customer-information__item">
                            <dt class="customer-information__label">
                                顧客名
                            </dt>

                            <dd class="customer-information__value">
                                {{ $customer->name }}
                            </dd>
                        </div>

                        <div class="customer-information__item">
                            <dt class="customer-information__label">
                                担当者名
                            </dt>

                            <dd class="customer-information__value">
                                @if ($customer->contact_name)
                                    {{ $customer->contact_name }}
                                @else
                                    <span class="customer-information__empty">
                                        未設定
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div class="customer-information__item">
                            <dt class="customer-information__label">
                                メールアドレス
                            </dt>

                            <dd class="customer-information__value">
                                @if ($customer->email)
                                    <a href="mailto:{{ $customer->email }}" class="customer-information__link">
                                        {{ $customer->email }}
                                    </a>
                                @else
                                    <span class="customer-information__empty">
                                        未設定
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div class="customer-information__item">
                            <dt class="customer-information__label">
                                電話番号
                            </dt>

                            <dd class="customer-information__value">
                                @if ($customer->phone)
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $customer->phone) }}"
                                        class="customer-information__link">
                                        {{ $customer->phone }}
                                    </a>
                                @else
                                    <span class="customer-information__empty">
                                        未設定
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div class="customer-information__item">
                            <dt class="customer-information__label">
                                郵便番号
                            </dt>

                            <dd class="customer-information__value">
                                @if ($customer->postal_code)
                                    〒{{ $customer->postal_code }}
                                @else
                                    <span class="customer-information__empty">
                                        未設定
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div class="customer-information__item customer-information__item--full">
                            <dt class="customer-information__label">
                                住所
                            </dt>

                            <dd class="customer-information__value">
                                @if ($customer->address)
                                    {{ $customer->address }}
                                @else
                                    <span class="customer-information__empty">
                                        未設定
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="customer-show-panel">
                    <header class="customer-show-panel__header">
                        <div>
                            <p class="customer-show-panel__eyebrow">
                                Notes
                            </p>

                            <h3 class="customer-show-panel__title">
                                顧客メモ
                            </h3>
                        </div>
                    </header>

                    @if ($customer->memo)
                        <p class="customer-show__memo">
                            {{ $customer->memo }}
                        </p>
                    @else
                        <p class="customer-show__empty-text">
                            顧客メモはまだ登録されていません。
                        </p>
                    @endif
                </section>

                <section class="customer-show-panel">
                    <header class="customer-show-panel__header">
                        <div>
                            <p class="customer-show-panel__eyebrow">
                                Record
                            </p>

                            <h3 class="customer-show-panel__title">
                                登録情報
                            </h3>
                        </div>
                    </header>

                    <dl class="customer-record">
                        <div class="customer-record__item">
                            <dt>
                                登録日
                            </dt>

                            <dd>
                                <time datetime="{{ $customer->created_at->toIso8601String() }}">
                                    {{ $customer->created_at->format('Y年m月d日 H:i') }}
                                </time>
                            </dd>
                        </div>

                        <div class="customer-record__item">
                            <dt>
                                最終更新
                            </dt>

                            <dd>
                                <time datetime="{{ $customer->updated_at->toIso8601String() }}">
                                    {{ $customer->updated_at->format('Y年m月d日 H:i') }}
                                </time>
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            <aside class="customer-show__side">
                <section class="customer-show-panel customer-show-panel--business-card">
                    <header class="customer-show-panel__header">
                        <div>
                            <p class="customer-show-panel__eyebrow">
                                Business Card
                            </p>

                            <h3 class="customer-show-panel__title">
                                名刺画像
                            </h3>
                        </div>
                    </header>

                    <div class="business-card-list">
                        <x-customers.business-card-image :path="$customer->business_card_front_path" side="名刺表面"
                            :alt="$customer->name . 'の名刺表面'" />

                        <x-customers.business-card-image :path="$customer->business_card_back_path" side="名刺裏面"
                            :alt="$customer->name . 'の名刺裏面'" />
                    </div>
                </section>
            </aside>
        </div>

        <section class="customer-show-danger" aria-labelledby="customer-delete-title">
            <div class="customer-show-danger__content">
                <div>
                    <p class="customer-show-danger__eyebrow">
                        Danger Zone
                    </p>

                    <h3 id="customer-delete-title" class="customer-show-danger__title">
                        顧客を削除
                    </h3>

                    <p class="customer-show-danger__description">
                        顧客情報と登録済みの名刺画像を削除します。
                        関連する案件は削除されませんが、顧客との紐付けは解除されます。
                        この操作は元に戻せません。
                    </p>
                </div>

                <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="customer-show-danger__form"
                    onsubmit="return confirm('「{{ $customer->name }}」を削除しますか？\n\n顧客情報と名刺画像が削除されます。\n関連案件は残りますが、顧客との紐付けは解除されます。\nこの操作は元に戻せません。');">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="customer-show-danger__button">
                        顧客を削除
                    </button>
                </form>
            </div>
        </section>
    </section>
@endsection