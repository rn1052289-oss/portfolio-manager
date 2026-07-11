@extends('layouts.app')

@section('title', '顧客一覧 | ClientWork Manager')
@section('page-label', 'Customer Management')
@section('page-title', '顧客管理')
@section('page-description', '顧客の基本情報や連絡先を一覧で確認できます。')

@section('page-actions')
    <a href="{{ route('customers.create') }}" class="customer-index-action">
        ＋ 顧客を登録
    </a>
@endsection

@section('content')
    <section class="customer-index">
        @if (session('success'))
            <div class="customer-index__flash" role="status">
                {{ session('success') }}
            </div>
        @endif

        <div class="customer-index__summary">
            <div>
                <p class="customer-index__eyebrow">
                    Customer List
                </p>

                <h2 class="customer-index__heading">
                    顧客一覧
                </h2>

                <p class="customer-index__description">
                    登録されている顧客の会社名、担当者、連絡先を確認できます。
                </p>
            </div>

            <div class="customer-index__count">
                <span class="customer-index__count-label">
                    登録件数
                </span>

                <strong class="customer-index__count-value">
                    {{ number_format($customers->total()) }}
                </strong>

                <span class="customer-index__count-unit">
                    件
                </span>
            </div>
        </div>

        @if ($customers->isEmpty())
            <div class="customer-empty">
                <div class="customer-empty__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M4 21v-9a2 2 0 0 1 2-2h4"></path>
                        <path d="M14 5h4a2 2 0 0 1 2 2v14"></path>
                        <path d="M8 10V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v16"></path>
                        <path d="M3 21h18"></path>
                        <path d="M11 7h2"></path>
                        <path d="M11 11h2"></path>
                        <path d="M11 15h2"></path>
                        <path d="M17 9h1"></path>
                        <path d="M17 13h1"></path>
                    </svg>
                </div>

                <h3 class="customer-empty__title">
                    顧客はまだ登録されていません
                </h3>

                <p class="customer-empty__text">
                    顧客を登録すると、会社情報や担当者、連絡先をまとめて管理できます。
                </p>
            </div>
        @else
            {{-- PC・ノートPC用テーブル --}}
            <div class="customer-table-panel">
                <div class="customer-table-panel__scroll">
                    <table class="customer-table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    顧客名
                                </th>

                                <th scope="col">
                                    担当者
                                </th>

                                <th scope="col">
                                    メールアドレス
                                </th>

                                <th scope="col">
                                    電話番号
                                </th>

                                <th scope="col">
                                    住所
                                </th>

                                <th scope="col">
                                    更新日
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($customers as $customer)
                                <tr>
                                    <td>
                                        <div class="customer-table__primary">
                                            <span class="customer-table__avatar" aria-hidden="true">
                                                {{ mb_substr($customer->name, 0, 1) }}
                                            </span>

                                            <div class="customer-table__primary-body">
                                                <p class="customer-table__name">
                                                    {{ $customer->name }}
                                                </p>

                                                <p class="customer-table__id">
                                                    Customer #{{ $customer->id }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="customer-table__value">
                                            {{ $customer->contact_name ?: '未設定' }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($customer->email)
                                            <a href="mailto:{{ $customer->email }}" class="customer-table__link">
                                                {{ $customer->email }}
                                            </a>
                                        @else
                                            <span class="customer-table__muted">
                                                未設定
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($customer->phone)
                                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $customer->phone) }}"
                                                class="customer-table__link">
                                                {{ $customer->phone }}
                                            </a>
                                        @else
                                            <span class="customer-table__muted">
                                                未設定
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="customer-table__address">
                                            {{ $customer->address ?: '未設定' }}
                                        </span>
                                    </td>

                                    <td>
                                        <time datetime="{{ $customer->updated_at->toDateString() }}" class="customer-table__date">
                                            {{ $customer->updated_at->format('Y/m/d') }}
                                        </time>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- スマートフォン用カード --}}
            <div class="customer-card-list">
                @foreach ($customers as $customer)
                    <article class="customer-card">
                        <header class="customer-card__header">
                            <span class="customer-card__avatar" aria-hidden="true">
                                {{ mb_substr($customer->name, 0, 1) }}
                            </span>

                            <div class="customer-card__heading">
                                <h3 class="customer-card__name">
                                    {{ $customer->name }}
                                </h3>

                                <p class="customer-card__id">
                                    Customer #{{ $customer->id }}
                                </p>
                            </div>
                        </header>

                        <dl class="customer-card__details">
                            <div class="customer-card__detail">
                                <dt>
                                    担当者
                                </dt>

                                <dd>
                                    {{ $customer->contact_name ?: '未設定' }}
                                </dd>
                            </div>

                            <div class="customer-card__detail">
                                <dt>
                                    メール
                                </dt>

                                <dd>
                                    @if ($customer->email)
                                        <a href="mailto:{{ $customer->email }}">
                                            {{ $customer->email }}
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </dd>
                            </div>

                            <div class="customer-card__detail">
                                <dt>
                                    電話番号
                                </dt>

                                <dd>
                                    @if ($customer->phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $customer->phone) }}">
                                            {{ $customer->phone }}
                                        </a>
                                    @else
                                        未設定
                                    @endif
                                </dd>
                            </div>

                            <div class="customer-card__detail">
                                <dt>
                                    住所
                                </dt>

                                <dd>
                                    {{ $customer->address ?: '未設定' }}
                                </dd>
                            </div>
                        </dl>

                        <footer class="customer-card__footer">
                            <span>
                                最終更新
                            </span>

                            <time datetime="{{ $customer->updated_at->toDateString() }}">
                                {{ $customer->updated_at->format('Y/m/d') }}
                            </time>
                        </footer>
                    </article>
                @endforeach
            </div>

            @if ($customers->hasPages())
                <div class="customer-pagination">
                    {{ $customers->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection