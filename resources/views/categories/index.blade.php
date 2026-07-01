@extends('layouts.app')

@section('content')
    <div class="category-page">
        <div class="category-page__header">
            <div>
                <p class="category-page__label">Category</p>
                <h1 class="category-page__title">カテゴリ管理</h1>
            </div>
        </div>

        <div class="category-page__divider"></div>

        <div class="category-layout">
            <section class="category-panel category-panel--form">
                <div class="category-panel__header">
                    <h2 class="category-panel__title">カテゴリ追加 / 編集</h2>
                </div>

                <form class="category-form" action="#" method="POST">
                    <div class="form-group">
                        <label class="form-label" for="name">
                            カテゴリ名 <span class="form-label__required">必須</span>
                        </label>

                        <input id="name" class="form-input" type="text" name="name" placeholder="例）Webサイト制作" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">
                            説明
                        </label>

                        <textarea id="description" class="form-textarea" name="description" rows="6"
                            placeholder="例）Webサイトの企画・デザイン・コーディングなど" disabled></textarea>
                    </div>

                    <button class="category-form__button" type="button">
                        保存
                    </button>

                    <p class="category-form__note">
                        ※ 登録処理は次のStepで実装します。
                    </p>
                </form>
            </section>

            <section class="category-panel category-panel--list">
                <div class="category-panel__header category-panel__header--between">
                    <h2 class="category-panel__title">既存カテゴリ一覧</h2>

                    <a href="#" class="category-panel__add-button">
                        ＋ カテゴリ追加
                    </a>
                </div>

                @if ($categories->isEmpty())
                    <div class="category-empty">
                        <p class="category-empty__title">カテゴリがまだありません</p>
                        <p class="category-empty__text">
                            最初のカテゴリを登録すると、案件を分類できるようになります。
                        </p>
                    </div>
                @else
                    <div class="category-table-wrap">
                        <table class="category-table">
                            <thead>
                                <tr>
                                    <th>カテゴリ名</th>
                                    <th>案件数</th>
                                    <th>説明</th>
                                    <th>操作</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($categories as $category)
                                    <tr>
                                        <td>
                                            <div class="category-name">
                                                <span class="category-name__color"
                                                    style="background-color: {{ $category->color }}"></span>
                                                <span class="category-name__text">
                                                    {{ $category->name }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="category-table__count">
                                            {{ $category->projects_count }}
                                        </td>

                                        <td>
                                            {{ $category->description ?? '説明はまだ登録されていません。' }}
                                        </td>

                                        <td>
                                            <div class="category-actions">
                                                <button class="category-actions__button" type="button">
                                                    編集
                                                </button>

                                                <button class="category-actions__button category-actions__button--danger"
                                                    type="button">
                                                    削除
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <section class="category-guide">
            <div class="category-guide__content">
                <div class="category-guide__icon">!</div>

                <p class="category-guide__text">
                    <span class="category-guide__strong">
                        カテゴリは1案件につき1つの分類を表します。
                    </span>
                    <br>
                    例：Webサイト制作、LP制作、WordPress、Laravel開発、保守対応 など
                </p>
            </div>

            <div class="category-guide__badge">
                カテゴリは1案件につき1つ
            </div>
        </section>
    </div>
@endsection