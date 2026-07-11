@extends('layouts.app')

@section('title', $customer->name . ' | 顧客詳細')
@section('page-label', 'Customer Detail')
@section('page-title', '顧客詳細')
@section('page-description', '顧客の基本情報、連絡先、名刺画像を確認できます。')

@section('page-actions')
    <a href="{{ route('customers.index') }}" class="customer-show-header-action">
        顧客一覧へ戻る
    </a>
@endsection

@section('content')
    <section class="customer-show">
        @if (session('success'))
            <div class="customer-show__flash" role="status">
                {{ session('success') }}
            </div>
        @endif

        <div class="customer-show__hero">
            <div class="customer-show__avatar" aria-hidden="true">
                {{ mb_substr($customer->name, 0, 1) }}
            </div>

            <div class="customer-show__hero-body">
                <p class="customer-show__number">
                    Customer #{{ $customer->id }}
                </p>

                <h2 class="customer-show__name">
                    {{ $customer->name }}
                </h2>

                <p class="customer-show__hero-description">
                    登録されている顧客情報と名刺画像を確認できます。
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
                                {{ $customer->contact_name ?: '未設定' }}
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
                                {{ $customer->postal_code ?: '未設定' }}
                            </dd>
                        </div>

                        <div class="customer-information__item customer-information__item--full">
                            <dt class="customer-information__label">
                                住所
                            </dt>

                            <dd class="customer-information__value">
                                {{ $customer->address ?: '未設定' }}
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
                                <time datetime="{{ $customer->created_at->toDateString() }}">
                                    {{ $customer->created_at->format('Y年m月d日 H:i') }}
                                </time>
                            </dd>
                        </div>

                        <div class="customer-record__item">
                            <dt>
                                最終更新
                            </dt>

                            <dd>
                                <time datetime="{{ $customer->updated_at->toDateString() }}">
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
    </section>
@endsection