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
                    案件の基本情報、進行状況、予算、スケジュールを登録します。
                </p>
            </div>

            <a href="{{ route('projects.index') }}" class="button button--secondary">
                案件一覧へ戻る
            </a>
        </div>

        <div class="project-editor-layout">
            <form action="{{ route('projects.store') }}" method="POST" class="project-editor-form">
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
                            <textarea id="description" name="description" rows="5"
                                placeholder="例）5ページ構成のWebサイト制作。お問い合わせフォーム、レスポンシブ対応、基本SEOを含む。">{{ old('description') }}</textarea>
                            <div class="form-help-row">
                                <p class="form-help">案件内容を簡潔に入力してください。</p>
                                <p class="form-count">0 / 500</p>

                                @error('description')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
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

                <section class="editor-section editor-section--disabled">
                    <div class="editor-section__side">
                        <span class="editor-section__number">3</span>
                        <h2>カテゴリ・タグ</h2>
                    </div>

                    <div class="editor-section__body">
                        <div class="coming-soon-box">
                            <p class="coming-soon-box__label">Coming Soon</p>
                            <h3>カテゴリ・タグ機能は次の段階で追加します</h3>
                            <p>
                                今後、Web制作・デザイン・システム開発などで案件を分類できるようにします。
                            </p>
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
                            <h3>画像アップロード・メモ機能は後で追加します</h3>
                            <p>
                                提案資料、参考画像、社内メモなどを案件に紐づける機能として拡張予定です。
                            </p>
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
                        ステータス、予算、開始日、納期をまとめて管理します。
                    </p>
                </div>

                <div class="guide-step guide-step--muted">
                    <span>3</span>
                    <h3>カテゴリ・タグ</h3>
                    <p>
                        今後、検索や絞り込みに使う情報として追加します。
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