@extends('layouts.app')

@section('title', 'タスク登録 | ClientWork Manager')

@section('page-label', 'Create Task')

@section('page-title', 'タスク登録')

@section('page-description', '案件に紐づく新しいタスクを登録します。')

@section('content')
    <section class="page-section task-create-page">
        {{-- 関連案件 --}}
        <section class="task-project-card">
            <div class="task-project-card__content">
                <span class="task-project-card__label">
                    関連案件
                </span>

                <div class="task-project-card__body">
                    <h2 class="task-project-card__title">
                        {{ $project->title }}
                    </h2>

                    <p class="task-project-card__client">
                        {{ $project->client_name }}
                    </p>
                </div>
            </div>

            <div class="task-project-card__actions">
                <a href="{{ route('projects.tasks.index', $project) }}" class="button button--secondary">
                    タスク一覧へ戻る
                </a>
            </div>
        </section>

        {{-- タスク登録フォーム --}}
        <section class="task-create-card">
            <div class="task-create-card__header">
                <div>
                    <p class="section-label">
                        New Task
                    </p>

                    <h2 class="section-title">
                        新しいタスクを登録
                    </h2>
                </div>

                <span class="task-create-card__required-note">
                    <span aria-hidden="true">*</span>
                    必須項目
                </span>
            </div>

            <form action="{{ route('projects.tasks.store', $project) }}" method="POST" class="task-form" novalidate>
                @csrf

                {{-- 全体エラー --}}
                @if ($errors->any())
                    <div class="task-form-alert" role="alert">
                        <p class="task-form-alert__title">
                            入力内容を確認してください
                        </p>

                        <ul class="task-form-alert__list">
                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="task-form__body">
                    {{-- タスク名 --}}
                    <div class="task-form-group task-form-group--full">
                        <label for="title" class="task-form-label">
                            タスク名

                            <span class="task-form-label__required">
                                *
                            </span>
                        </label>

                        <input type="text" id="title" name="title" value="{{ old('title') }}"
                            class="task-form-control @error('title') task-form-control--error @enderror" maxlength="255"
                            required autocomplete="off" placeholder="例：トップページをコーディングする">

                        <div class="task-form-meta">
                            <p class="task-form-help">
                                作業内容がひと目で分かる名前を入力してください。
                            </p>

                            <span class="task-form-limit">
                                最大255文字
                            </span>
                        </div>

                        @error('title')
                            <p class="task-form-error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- ステータス --}}
                    <div class="task-form-group">
                        <label for="status" class="task-form-label">
                            ステータス

                            <span class="task-form-label__required">
                                *
                            </span>
                        </label>

                        <div class="task-form-select-wrap">
                            <select id="status" name="status"
                                class="task-form-control task-form-select @error('status') task-form-control--error @enderror"
                                required>
                                <option value="not_started" @selected(old('status', 'not_started') === 'not_started')>
                                    未着手
                                </option>

                                <option value="in_progress" @selected(old('status') === 'in_progress')>
                                    進行中
                                </option>

                                <option value="completed" @selected(old('status') === 'completed')>
                                    完了
                                </option>
                            </select>
                        </div>

                        @error('status')
                            <p class="task-form-error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- 優先度 --}}
                    <div class="task-form-group">
                        <label for="priority" class="task-form-label">
                            優先度

                            <span class="task-form-label__required">
                                *
                            </span>
                        </label>

                        <div class="task-form-select-wrap">
                            <select id="priority" name="priority"
                                class="task-form-control task-form-select @error('priority') task-form-control--error @enderror"
                                required>
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
                        </div>

                        @error('priority')
                            <p class="task-form-error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- 期限 --}}
                    <div class="task-form-group task-form-group--full">
                        <label for="due_date" class="task-form-label">
                            期限
                        </label>

                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}"
                            class="task-form-control task-form-control--date @error('due_date') task-form-control--error @enderror">

                        <p class="task-form-help">
                            未定の場合は空欄のまま登録できます。
                        </p>

                        @error('due_date')
                            <p class="task-form-error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- メモ --}}
                    <div class="task-form-group task-form-group--full">
                        <label for="memo" class="task-form-label">
                            メモ
                        </label>

                        <textarea id="memo" name="memo" rows="6" maxlength="2000"
                            class="task-form-control task-form-textarea @error('memo') task-form-control--error @enderror"
                            placeholder="例：PC版から実装し、完了後にスマホ対応を進める">{{ old('memo') }}</textarea>

                        <div class="task-form-meta">
                            <p class="task-form-help">
                                作業手順や注意点など、補足情報を入力できます。
                            </p>

                            <span class="task-form-limit">
                                最大2000文字
                            </span>
                        </div>

                        @error('memo')
                            <p class="task-form-error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- フォーム操作 --}}
                <div class="task-form__actions">
                    <a href="{{ route('projects.tasks.index', $project) }}" class="task-form-cancel">
                        キャンセル
                    </a>

                    <button type="submit" class="task-form-submit">
                        タスクを登録する
                    </button>
                </div>
            </form>
        </section>
    </section>
@endsection