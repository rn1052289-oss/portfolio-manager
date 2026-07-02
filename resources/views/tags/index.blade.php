@extends('layouts.app')

@section('title', 'タグ管理 | ClientWork Manager')

@section('content')
    <section class="tag-page">
        <div class="tag-page__inner">
            <div class="tag-page__header">
                <div>
                    <h1 class="tag-page__title">タグ管理</h1>
                    <p class="tag-page__lead">
                        タグは複数付与でき、1つの案件に何度でも使用できます。案件の絞り込みや検索に利用されます。
                    </p>
                </div>
            </div>

            <div class="tag-page__notice">
                <p>
                    タグは案件の特徴を細かく分類するための機能です。
                    例：HTML、CSS、WordPress、Laravel、JavaScript、レスポンシブ、SEO
                </p>
            </div>

            @if (session('success'))
                <div class="tag-page__success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="tag-page__layout">
                <aside class="tag-form-card">
                    <div class="tag-form-card__header">
                        <h2 class="tag-form-card__title">
                            {{ $editingTag ? 'タグ編集' : 'タグ追加' }}
                        </h2>
                        <p class="tag-form-card__text">
                            {{ $editingTag ? '選択中のタグ名を変更できます。' : '案件に使うタグを追加できます。' }}
                        </p>
                    </div>

                    <form class="tag-form"
                        action="{{ $editingTag ? route('tags.update', $editingTag) : route('tags.store') }}" method="post">
                        @csrf

                        @if ($editingTag)
                            @method('PUT')
                        @endif

                        <div class="tag-form__group">
                            <label for="name" class="tag-form__label">タグ名</label>
                            <input type="text" id="name" name="name"
                                class="tag-form__input @error('name') is-error @enderror"
                                value="{{ old('name', $editingTag?->name) }}" placeholder="例）HTML">

                            @error('name')
                                <p class="tag-form__error">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="tag-form__help">
                            ※ 同じタグ名は重複して登録できません。
                        </p>

                        <button type="submit" class="tag-form__button">
                            {{ $editingTag ? '更新' : '保存' }}
                        </button>

                        @if ($editingTag)
                            <a href="{{ route('tags.index') }}" class="tag-form__cancel">
                                キャンセル
                            </a>
                        @endif
                    </form>
                </aside>

                <div class="tag-list-card">
                    <div class="tag-list-card__header">
                        <div>
                            <h2 class="tag-list-card__title">既存タグ一覧</h2>
                            <p class="tag-list-card__text">
                                登録済みタグと、使用されている案件数を確認できます。
                            </p>
                        </div>

                        <button type="button" class="tag-list-card__add-button" disabled>
                            タグ追加
                        </button>
                    </div>

                    @if ($tags->count() > 0)
                        <div class="tag-table-wrap">
                            <table class="tag-table">
                                <thead>
                                    <tr>
                                        <th>タグ名</th>
                                        <th>使用件数</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tags as $tag)
                                        <tr class="{{ $editingTag?->id === $tag->id ? 'is-editing' : '' }}">
                                            <td>
                                                <span class="tag-table__name">
                                                    {{ $tag->name }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $tag->projects_count }}件
                                            </td>
                                            <td>
                                                <div class="tag-table__actions">
                                                    <a href="{{ route('tags.edit', $tag) }}" class="tag-table__button">
                                                        編集
                                                    </a>
                                                    <button type="button" class="tag-table__button tag-table__button--danger"
                                                        disabled>
                                                        削除
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="tag-list-card__chips">
                            <p class="tag-list-card__chips-title">
                                タグ（チップ表示のサンプル）
                            </p>

                            <div class="tag-chip-list">
                                @foreach ($tags->take(6) as $tag)
                                    <span class="tag-chip">
                                        {{ $tag->name }}
                                        <span class="tag-chip__remove">×</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="tag-page__pagination">
                            {{ $tags->links() }}
                        </div>
                    @else
                        <div class="tag-empty">
                            <p class="tag-empty__title">まだタグがありません</p>
                            <p class="tag-empty__text">
                                次のステップでタグ登録機能を追加します。
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="tag-page__memo">
                <span class="tag-page__memo-icon">💡</span>
                <p>
                    メモ：タグは自由に追加・編集・削除できます。不要なタグを削除すると、既存案件からも自動的に解除されます。
                </p>
            </div>
        </div>
    </section>
@endsection