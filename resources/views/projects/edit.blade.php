@extends('layouts.app')

@section('title', '案件編集 | ClientWork Manager')

@section('content')
    <section class="page-section">
        <div class="page-section__header page-section__header--compact">
            <div>
                <div class="breadcrumb">
                    <a href="{{ route('projects.index') }}">案件一覧</a>
                    <span>/</span>
                    <a href="{{ route('projects.show', $project) }}">案件詳細</a>
                    <span>/</span>
                    <span>案件編集</span>
                </div>

                <p class="section-label">Project Edit</p>
                <h1 class="section-title">案件編集</h1>
                <p class="section-description">
                    案件情報を更新します。金額・納期・ステータスの変更漏れに注意してください。
                </p>
            </div>

            <div>
                <a href="{{ route('projects.show', $project) }}" class="button button--secondary">
                    詳細へ戻る
                </a>
            </div>
        </div>

        <form action="{{ route('projects.update', $project) }}" method="POST" class="project-editor-layout">
            @csrf
            @method('PUT')

            <div class="project-editor-form">
                <section class="editor-section">
                    <div class="editor-section__side">
                        <span class="editor-section__number">1</span>
                        <div>
                            <h2>基本情報</h2>
                        </div>
                    </div>

                    <div class="editor-section__body">
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="title">
                                    案件名
                                    <span>必須</span>
                                </label>

                                <input id="title" type="text" name="title" value="{{ old('title', $project->title) }}"
                                    placeholder="例：コーポレートサイト制作">

                                @error('title')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="client_name">
                                    クライアント名
                                    <span>必須</span>
                                </label>

                                <input id="client_name" type="text" name="client_name"
                                    value="{{ old('client_name', $project->client_name) }}" placeholder="例：株式会社サンプル">

                                @error('client_name')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="status">
                                    ステータス
                                    <span>必須</span>
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
                                        停止中
                                    </option>
                                </select>

                                @error('status')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="budget">
                                    見積金額
                                </label>

                                <div class="input-with-unit">
                                    <input id="budget" type="number" name="budget"
                                        value="{{ old('budget', $project->budget) }}" min="0" placeholder="150000">
                                    <span>円</span>
                                </div>

                                @error('budget')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="start_date">
                                    開始日
                                </label>

                                <input id="start_date" type="date" name="start_date"
                                    value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}">

                                @error('start_date')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label for="deadline">
                                    納期
                                </label>

                                <input id="deadline" type="date" name="deadline"
                                    value="{{ old('deadline', optional($project->deadline)->format('Y-m-d')) }}">

                                @error('deadline')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="editor-section">
                    <div class="editor-section__side">
                        <span class="editor-section__number">2</span>
                        <div>
                            <h2>案件概要</h2>
                        </div>
                    </div>

                    <div class="editor-section__body">
                        <div class="form-field">
                            <div class="form-help-row">
                                <label for="description">
                                    案件概要
                                </label>

                                <p class="form-count">
                                    最大500文字
                                </p>
                            </div>

                            <textarea id="description" name="description" rows="7"
                                placeholder="案件内容、制作範囲、注意点などを入力してください。">{{ old('description', $project->description) }}</textarea>

                            <p class="form-help">
                                制作範囲、納品物、クライアント要望などを残しておくと、あとから確認しやすくなります。
                            </p>

                            @error('description')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="editor-section">
                    <div class="editor-section__side">
                        <span class="editor-section__number">3</span>
                        <div>
                            <h2>カテゴリ</h2>
                        </div>
                    </div>

                    <div class="editor-section__body">
                        @php
                            $selectedCategoryId = old('category_id', $project->category_id);
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
                                案件の分類を変更できます。未分類に戻すこともできます。
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
                    </div>
                </section>

                <section class="editor-section editor-section--disabled">
                    <div class="editor-section__side">
                        <span class="editor-section__number">4</span>
                        <div>
                            <h2>画像・メモ</h2>
                        </div>
                    </div>

                    <div class="editor-section__body">
                        <div class="coming-soon-box">
                            <p class="coming-soon-box__label">Coming Soon</p>
                            <h3>画像アップロード・メモ機能は今後追加予定です</h3>
                            <p>
                                案件イメージ、参考資料、クライアントとのやり取りを残せるようにします。
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
            </div>

            <aside class="project-editor-guide">
                <section class="guide-panel">
                    <p class="guide-panel__label">Edit Guide</p>
                    <h2>編集時の注意</h2>
                    <p>
                        ステータス・金額・納期は、案件管理で特に重要です。変更後は詳細画面で内容を確認しましょう。
                    </p>
                </section>

                <section class="guide-step">
                    <span>1</span>
                    <h3>変更内容を確認</h3>
                    <p>
                        既存データが入った状態で表示されます。必要な項目だけ修正してください。
                    </p>
                </section>

                <section class="guide-step">
                    <span>2</span>
                    <h3>納期と金額に注意</h3>
                    <p>
                        実務では納期と金額のミスがトラブルにつながりやすいです。
                    </p>
                </section>

                <section class="guide-step">
                    <span>3</span>
                    <h3>カテゴリを整理</h3>
                    <p>
                        案件の種類を分類しておくと、一覧表示や検索機能を拡張しやすくなります。
                    </p>
                </section>
            </aside>
        </form>
    </section>
@endsection