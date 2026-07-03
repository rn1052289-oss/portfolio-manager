@extends('layouts.app')

@section('title', '案件編集 | ClientWork Manager')

@section('content')
    <section class="page-section">
        <div class="breadcrumb">
            <a href="{{ route('dashboard') }}">ホーム</a>
            <span>/</span>
            <a href="{{ route('projects.index') }}">案件一覧</a>
            <span>/</span>
            <a href="{{ route('projects.show', $project) }}">案件詳細</a>
            <span>/</span>
            <span>案件編集</span>
        </div>

        <div class="page-section__header page-section__header--compact">
            <div>
                <p class="section-label">Project Edit</p>
                <h1 class="section-title">案件編集</h1>
                <p class="section-description">
                    案件の基本情報、進行状況、予算、スケジュール、カテゴリ・タグを更新します。
                </p>
            </div>

            <a href="{{ route('projects.show', $project) }}" class="button button--secondary">
                詳細へ戻る
            </a>
        </div>

        <div class="project-editor-layout">
            <form action="{{ route('projects.update', $project) }}" method="POST" class="project-editor-form">
                @csrf
                @method('PUT')

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

                            <input id="title" type="text" name="title" value="{{ old('title', $project->title) }}"
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

                            <input id="client_name" type="text" name="client_name"
                                value="{{ old('client_name', $project->client_name) }}" placeholder="例）株式会社サンプル">

                            <p class="form-help">正式名称で登録すると、あとから検索しやすくなります。</p>

                            @error('client_name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-field">
                            <label for="description">概要</label>

                            <textarea id="description" name="description" rows="5" maxlength="500"
                                placeholder="例）5ページ構成のWebサイト制作。お問い合わせフォーム、レスポンシブ対応、基本SEOを含む。">{{ old('description', $project->description) }}</textarea>

                            <div class="form-help-row">
                                <p class="form-help">案件内容を簡潔に入力してください。</p>
                                <p class="form-count">最大500文字</p>
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
                                    <option value="not_started" @selected(old('status', $project->status) === 'not_started')>
                                        未着手
                                    </option>
                                    <option value="in_progress" @selected(old('status', $project->status) === 'in_progress')>
                                        進行中
                                    </option>
                                    <option value="completed" @selected(old('status', $project->status) === 'completed')>
                                        完了
                                    </option>
                                    <option value="paused" @selected(old('status', $project->status) === 'paused')>
                                        保留
                                    </option>
                                </select>

                                <p class="form-help">現在の進行状況を選択してください。</p>

                                @error('status')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="budget">予算</label>

                                <div class="input-with-unit">
                                    <input id="budget" type="number" name="budget"
                                        value="{{ old('budget', $project->budget) }}" placeholder="例）120000" min="0">
                                    <span>円</span>
                                </div>

                                <p class="form-help">税込・税抜の管理方針は後で統一します。</p>

                                @error('budget')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="start_date">開始日</label>

                                <input id="start_date" type="date" name="start_date"
                                    value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}">

                                <p class="form-help">着手予定日または実際の開始日を入力します。</p>

                                @error('start_date')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="deadline">納期</label>

                                <input id="deadline" type="date" name="deadline"
                                    value="{{ old('deadline', optional($project->deadline)->format('Y-m-d')) }}">

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
                        @php
                            $selectedCategoryId = old('category_id', $project->category_id);

                            $selectedTagIds = collect(old('tag_ids', $project->tags->pluck('id')->all()))
                                ->map(fn($id) => (string) $id)
                                ->all();
                        @endphp

                        <div class="form-field">
                            <label for="category_id">カテゴリ</label>

                            <select id="category_id" name="category_id">
                                <option value="" @selected(blank($selectedCategoryId))>
                                    未分類
                                </option>

                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="form-help">
                                案件を種類別に整理できます。未分類に戻すこともできます。
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
                                        タグ管理画面でタグを作成すると、案件編集時に選択できるようになります。
                                    </p>
                                </div>
                            @else
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
                                    タグを変更すると、あとから検索・絞り込みで案件を探しやすくなります。
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

                <section class="editor-section editor-section--disabled">
                    <div class="editor-section__side">
                        <span class="editor-section__number">4</span>
                        <h2>画像・メモ</h2>
                    </div>

                    <div class="editor-section__body">
                        <div class="coming-soon-box">
                            <p class="coming-soon-box__label">Coming Soon</p>
                            <h3>参考画像・補足メモ機能は追加予定です</h3>
                            <p>
                                今後、案件ごとの参考画像アップロードや社内メモを管理できるように拡張します。
                            </p>
                        </div>
                    </div>
                </section>

                <div class="editor-actions">
                    <a href="{{ route('projects.show', $project) }}" class="button button--secondary">
                        キャンセル
                    </a>

                    <button type="submit" class="button button--primary">
                        更新する
                    </button>
                </div>
            </form>

            <aside class="project-editor-guide">
                <div class="guide-panel">
                    <p class="guide-panel__label">Edit Guide</p>
                    <h2>編集時の注意</h2>
                    <p>
                        ステータス・金額・納期・タグは、案件管理で特に重要です。変更後は詳細画面で内容を確認しましょう。
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
                        ステータス、予算、開始日、納期をまとめて管理します。
                    </p>
                </div>

                <div class="guide-step">
                    <span>3</span>
                    <h3>カテゴリ・タグ</h3>
                    <p>
                        案件分類と特徴タグを更新できます。検索・絞り込みの精度を高めるための情報です。
                    </p>
                </div>

                <div class="guide-step guide-step--muted">
                    <span>4</span>
                    <h3>画像・メモ</h3>
                    <p>
                        参考画像や補足メモを管理する拡張機能として追加予定です。
                    </p>
                </div>
            </aside>
        </div>
    </section>
@endsection