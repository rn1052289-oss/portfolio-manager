@extends('layouts.app')

@section('content')
    <div class="category-page">
        @if (session('success'))
            <div class="flash-message">
                {{ session('success') }}
            </div>
        @endif

        <div class="category-page__header">
            <div>
                <p class="category-page__label">Category</p>

                <h1 class="category-page__title">
                    カテゴリ管理
                </h1>
            </div>
        </div>

        <div class="category-page__divider"></div>

        <div class="category-layout">
            <section class="category-panel category-panel--form">
                <div class="category-panel__header">
                    <h2 class="category-panel__title">
                        {{ $editingCategory ? 'カテゴリ編集' : 'カテゴリ追加' }}
                    </h2>
                </div>

                <form class="category-form"
                    action="{{ $editingCategory ? route('categories.update', $editingCategory) : route('categories.store') }}"
                    method="POST">
                    @csrf

                    @if ($editingCategory)
                        @method('PUT')
                    @endif

                    <div class="form-group">
                        <label class="form-label" for="name">
                            カテゴリ名
                            <span class="form-label__required">
                                必須
                            </span>
                        </label>

                        <input id="name" class="form-input @error('name') is-invalid @enderror" type="text" name="name"
                            value="{{ old('name', $editingCategory?->name) }}" placeholder="例）Webサイト制作">

                        @error('name')
                            <p class="form-error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">
                            説明
                        </label>

                        <textarea id="description" class="form-textarea @error('description') is-invalid @enderror"
                            name="description" rows="6"
                            placeholder="例）Webサイトの企画・デザイン・コーディングなど">{{ old('description', $editingCategory?->description) }}</textarea>

                        @error('description')
                            <p class="form-error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button class="category-form__button" type="submit">
                        {{ $editingCategory ? '更新する' : '保存する' }}
                    </button>

                    @if ($editingCategory)
                        <a href="{{ route('categories.index') }}" class="category-form__cancel">
                            編集をキャンセル
                        </a>
                    @endif
                </form>
            </section>

            <section class="category-panel category-panel--list">
                <div class="category-panel__header category-panel__header--between">
                    <h2 class="category-panel__title">
                        既存カテゴリ一覧
                    </h2>

                    <a href="#" class="category-panel__add-button">
                        ＋ カテゴリ追加
                    </a>
                </div>

                @if ($categories->isEmpty())
                    <div class="category-empty">
                        <p class="category-empty__title">
                            カテゴリがまだありません
                        </p>

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
                                    @php
                                        $hasProjects = $category->projects_count > 0;

                                        $filteredProjectsUrl = route('projects.index', [
                                            'category_id' => $category->id,
                                        ]);
                                    @endphp

                                    <tr>
                                        <td>
                                            <div class="category-name">
                                                <span class="category-name__color"
                                                    style="background-color: {{ $category->color }}"></span>

                                                @if ($hasProjects)
                                                    <a href="{{ $filteredProjectsUrl }}" class="category-name__link"
                                                        aria-label="{{ $category->name }}カテゴリの案件一覧を表示">
                                                        {{ $category->name }}
                                                    </a>
                                                @else
                                                    <span class="category-name__text category-name__text--empty">
                                                        {{ $category->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="category-table__count">
                                            @if ($hasProjects)
                                                <a href="{{ $filteredProjectsUrl }}" class="category-count-link"
                                                    aria-label="{{ $category->name }}カテゴリの{{ $category->projects_count }}件の案件を表示">
                                                    {{ $category->projects_count }}件
                                                </a>
                                            @else
                                                <span class="category-count-link category-count-link--empty">
                                                    0件
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            {{ $category->description ?? '説明はまだ登録されていません。' }}
                                        </td>

                                        <td>
                                            <div class="category-actions">
                                                <a href="{{ route('categories.edit', $category) }}"
                                                    class="category-actions__button">
                                                    編集
                                                </a>

                                                <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                                    onsubmit="return confirm('このカテゴリを削除しますか？案件自体は削除されません。');">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button class="category-actions__button category-actions__button--danger"
                                                        type="submit">
                                                        削除
                                                    </button>
                                                </form>
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
                <div class="category-guide__icon">
                    !
                </div>

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