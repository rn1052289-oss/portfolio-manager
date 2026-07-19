@extends('layouts.app')

@section('title', '案件作成 | ClientWork Manager')

@section('content')
    <section class="page-section">
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">ホーム</a>
            <span>/</span>
            <a href="{{ route('projects.index') }}">案件一覧</a>
            <span>/</span>
            <span>案件作成</span>
        </div>

        <div class="page-section__header page-section__header--compact">
            <div>
                <p class="section-label">Project Form</p>
                <h1 class="section-title">案件作成</h1>
                <p class="section-description">
                    案件の基本情報、進行状況、優先度、予算、スケジュール、カテゴリ・タグを登録します。
                </p>
            </div>

            <a href="{{ route('projects.index') }}" class="button button--secondary">
                案件一覧へ戻る
            </a>
        </div>

        <div class="project-editor-layout">
            <form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data"
                class="project-editor-form">
                @csrf

                <section class="editor-section">
                    <div class="editor-section__side">
                        <span class="editor-section__number">1</span>
                        <h2>基本情報</h2>
                    </div>

                    <div class="editor-section__body">
                        <div class="form-field">
                            <label for="title">
                                案件名 <span>必須</span>
                            </label>

                            <input id="title" type="text" name="title" value="{{ old('title') }}"
                                placeholder="例）Webサイト制作案件">

                            <p class="form-help">案件一覧で最も目立つ名前です。</p>

                            @error('title')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label for="client_name">
                                クライアント名 <span>必須</span>
                            </label>

                            <input id="client_name" type="text" name="client_name" value="{{ old('client_name') }}"
                                placeholder="例）株式会社サンプル">

                            <p class="form-help">正式名称で登録すると、あとから検索しやすくなります。</p>

                            @error('client_name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label for="description">概要</label>

                            <textarea id="description" name="description" rows="5" maxlength="500"
                                placeholder="例）5ページ構成のWebサイト制作。お問い合わせフォーム、レスポンシブ対応、基本SEOを含む。">{{ old('description') }}</textarea>

                            <div class="form-help-row">
                                <p class="form-help">案件内容を簡潔に入力してください。</p>
                                <p class="form-count">0 / 500</p>
                            </div>

                            @error('description')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="editor-section">
                    <div class="editor-section__side">
                        <span class="editor-section__number">2</span>
                        <h2>進捗・条件</h2>
                    </div>

                    <div class="editor-section__body">
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="status">
                                    ステータス <span>必須</span>
                                </label>

                                <select id="status" name="status">
                                    <option value="not_started" @selected(old('status') === 'not_started')>
                                        未着手
                                    </option>
                                    <option value="in_progress" @selected(old('status', 'in_progress') === 'in_progress')>
                                        進行中
                                    </option>
                                    <option value="completed" @selected(old('status') === 'completed')>
                                        完了
                                    </option>
                                    <option value="paused" @selected(old('status') === 'paused')>
                                        保留
                                    </option>
                                </select>

                                <p class="form-help">現在の進行状況を選択してください。</p>

                                @error('status')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="priority">
                                    優先度 <span>必須</span>
                                </label>

                                <select id="priority" name="priority">
                                    <option value="high" @selected(old('priority') === 'high')>
                                        高
                                    </option>

                                    <option value="medium" @selected(old('priority', 'medium') === 'medium')>
                                        中
                                    </option>

                                    <option value="low" @selected(old('priority') === 'low')>
                                        低
                                    </option>
                                </select>

                                <p class="form-help">
                                    対応する順番や緊急度に合わせて選択してください。
                                </p>

                                @error('priority')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="budget">予算</label>

                                <div class="input-with-unit">
                                    <input id="budget" type="number" name="budget" value="{{ old('budget') }}"
                                        placeholder="例）120000" min="0">
                                    <span>円</span>
                                </div>

                                <p class="form-help">税込・税抜の管理方針は後で統一します。</p>

                                @error('budget')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="start_date">開始日</label>

                                <input id="start_date" type="date" name="start_date" value="{{ old('start_date') }}">

                                <p class="form-help">着手予定日または実際の開始日を入力します。</p>

                                @error('start_date')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="deadline">納期</label>

                                <input id="deadline" type="date" name="deadline" value="{{ old('deadline') }}">

                                <p class="form-help">納期がある場合は必ず入力しましょう。</p>

                                @error('deadline')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="editor-section">
                    <div class="editor-section__side">
                        <span class="editor-section__number">3</span>
                        <h2>カテゴリ・タグ</h2>
                    </div>

                    <div class="editor-section__body">
                        <div class="form-field">
                            <label for="category_id">カテゴリ</label>

                            <select id="category_id" name="category_id">
                                <option value="" @selected(old('category_id') === null || old('category_id') === '')>
                                    未分類
                                </option>

                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="form-help">
                                案件を種類別に整理できます。未分類のままでも登録できます。
                            </p>

                            @if ($categories->isEmpty())
                                <p class="form-help">
                                    まだカテゴリがありません。必要な場合はカテゴリ管理画面から追加してください。
                                </p>
                            @endif

                            @error('category_id')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-field form-field--tags">
                            <div class="form-label-row">
                                <label>タグ</label>
                                <span class="form-label-badge">複数選択可</span>
                            </div>

                            @if ($tags->isEmpty())
                                <div class="empty-inline">
                                    <p class="empty-inline__title">まだタグがありません</p>
                                    <p class="empty-inline__text">
                                        タグ管理画面でタグを作成すると、案件登録時に選択できるようになります。
                                    </p>
                                </div>
                            @else
                                @php
                                    $selectedTagIds = collect(old('tag_ids', []))
                                        ->map(fn($id) => (string) $id)
                                        ->all();
                                @endphp

                                <div class="tag-check-list">
                                    @foreach ($tags as $tag)
                                        <label class="tag-check">
                                            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" class="tag-check__input"
                                                @checked(in_array((string) $tag->id, $selectedTagIds, true))>

                                            <span class="tag-check__box"></span>

                                            <span class="tag-check__text">
                                                {{ $tag->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>

                                <p class="form-help">
                                    タグを選択すると、あとから検索・絞り込みで案件を探しやすくなります。
                                </p>
                            @endif

                            @error('tag_ids')
                                <p class="form-error">{{ $message }}</p>
                            @enderror

                            @error('tag_ids.*')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="editor-section">
                    <div class="editor-section__side">
                        <span class="editor-section__number">4</span>
                        <h2>案件画像</h2>
                    </div>

                    <div class="editor-section__body">
                        <div class="form-field">
                            <div class="form-label-row">
                                <label for="cover_image">代表画像</label>
                                <span class="form-label-badge">任意</span>
                            </div>

                            <div class="project-image-upload">
                                <div class="project-image-upload__intro">
                                    <div class="project-image-upload__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M12 16V4M12 4L7.5 8.5M12 4L16.5 8.5" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M5 14V18C5 19.1046 5.89543 20 7 20H17C18.1046 20 19 19.1046 19 18V14"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                        </svg>
                                    </div>

                                    <div>
                                        <p class="project-image-upload__title">
                                            案件の代表画像を選択
                                        </p>

                                        <p class="project-image-upload__text">
                                            一覧・詳細画面で案件を識別しやすくするための画像です。
                                        </p>
                                    </div>
                                </div>

                                <input id="cover_image" type="file" name="cover_image" class="project-image-upload__input"
                                    accept="image/jpeg,image/png,image/webp" aria-describedby="cover_image_help">
                            </div>

                            <p id="cover_image_help" class="form-help">
                                JPG、JPEG、PNG、WebP形式に対応しています。最大5MBです。
                            </p>

                            @error('cover_image')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div class="editor-actions">
                    <a href="{{ route('projects.index') }}" class="button button--secondary">
                        戻る
                    </a>

                    <button type="submit" class="button button--primary">
                        保存
                    </button>
                </div>
            </form>

            <aside class="project-editor-guide">
                <div class="guide-panel">
                    <p class="guide-panel__label">Form Guide</p>
                    <h2>なぜフィールドを分けるのか</h2>
                    <p>
                        関連する情報をグループ化することで、入力のしやすさ・見やすさ・管理のしやすさが上がります。
                    </p>
                </div>

                <div class="guide-step">
                    <span>1</span>
                    <h3>基本情報</h3>
                    <p>
                        案件名・クライアント名・概要など、案件を識別するための情報です。
                    </p>
                </div>

                <div class="guide-step">
                    <span>2</span>
                    <h3>進捗・条件</h3>
                    <p>
                        ステータス、優先度、予算、開始日、納期をまとめて管理します。
                    </p>
                </div>

                <div class="guide-step">
                    <span>3</span>
                    <h3>カテゴリ・タグ</h3>
                    <p>
                        案件を分類し、タグで特徴を追加できます。あとから検索や絞り込みにも使いやすくなります。
                    </p>
                </div>

                <div class="guide-step">
                    <span>4</span>
                    <h3>案件画像</h3>
                    <p>
                        案件を視覚的に識別しやすくする代表画像を登録できます。画像なしでも案件作成は可能です。
                    </p>
                </div>
            </aside>
        </div>
    </section>
@endsection