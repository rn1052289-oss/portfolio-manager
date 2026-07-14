<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProjectSearchService
{
    /**
     * 検索で許可する案件ステータスです。
     *
     * @var array<int, string>
     */
    private const ALLOWED_STATUSES = [
        'not_started',
        'in_progress',
        'completed',
        'paused',
    ];

    /**
     * 検索で許可する並び順です。
     *
     * @var array<int, string>
     */
    private const ALLOWED_SORTS = [
        'latest',
        'deadline_asc',
        'deadline_desc',
        'oldest',
        'budget_desc',
        'budget_asc',
    ];

    /**
     * ログインユーザーが所有する案件を対象に、
     * 検索条件と並び順を適用したクエリを返します。
     *
     * このServiceでは、paginate()やget()は実行しません。
     * 一覧・CSVなど、呼び出し側が用途に応じて取得方法を決めます。
     *
     * @param  array<string, mixed>  $filters
     */
    public function build(User $user, array $filters = []): Builder
    {
        $query = $user->projects()->getQuery();

        $this->applyKeywordFilter(
            $query,
            $filters['keyword'] ?? null
        );

        $this->applyCategoryFilter(
            $query,
            $user,
            $filters['category_id'] ?? null
        );

        $this->applyTagFilter(
            $query,
            $user,
            $filters['tag_id'] ?? null
        );

        $this->applyStatusFilter(
            $query,
            $filters['status'] ?? null
        );

        $this->applySort(
            $query,
            $filters['sort'] ?? 'latest'
        );

        return $query;
    }

    /**
     * 案件名・クライアント名のキーワード検索を適用します。
     */
    private function applyKeywordFilter(
        Builder $query,
        mixed $keyword
    ): void {
        if (!is_string($keyword)) {
            return;
        }

        $keyword = trim($keyword);

        if ($keyword === '') {
            return;
        }

        $query->where(function (Builder $query) use ($keyword) {
            $query->where('title', 'like', "%{$keyword}%")
                ->orWhere('client_name', 'like', "%{$keyword}%");
        });
    }

    /**
     * カテゴリ絞り込みを適用します。
     *
     * ログインユーザーが所有していないカテゴリIDが指定された場合は、
     * 結果を0件にします。
     */
    private function applyCategoryFilter(
        Builder $query,
        User $user,
        mixed $categoryId
    ): void {
        if (!$this->hasFilterValue($categoryId)) {
            return;
        }

        $categoryId = filter_var(
            $categoryId,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($categoryId === false) {
            $this->forceEmptyResult($query);

            return;
        }

        $ownsCategory = $user->categories()
            ->whereKey($categoryId)
            ->exists();

        if (!$ownsCategory) {
            $this->forceEmptyResult($query);

            return;
        }

        $query->where('category_id', $categoryId);
    }

    /**
     * タグ絞り込みを適用します。
     *
     * ログインユーザーが所有していないタグIDが指定された場合は、
     * 結果を0件にします。
     */
    private function applyTagFilter(
        Builder $query,
        User $user,
        mixed $tagId
    ): void {
        if (!$this->hasFilterValue($tagId)) {
            return;
        }

        $tagId = filter_var(
            $tagId,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($tagId === false) {
            $this->forceEmptyResult($query);

            return;
        }

        $ownsTag = $user->tags()
            ->whereKey($tagId)
            ->exists();

        if (!$ownsTag) {
            $this->forceEmptyResult($query);

            return;
        }

        $query->whereHas('tags', function (Builder $tagQuery) use ($tagId) {
            $tagQuery->where('tags.id', $tagId);
        });
    }

    /**
     * ステータス絞り込みを適用します。
     *
     * 許可されていない値は検索条件として使用しません。
     */
    private function applyStatusFilter(
        Builder $query,
        mixed $status
    ): void {
        if (!is_string($status)) {
            return;
        }

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return;
        }

        $query->where('status', $status);
    }

    /**
     * 並び順を適用します。
     *
     * 許可されていない値の場合は、新しい順を使用します。
     */
    private function applySort(
        Builder $query,
        mixed $sort
    ): void {
        if (
            !is_string($sort)
            || !in_array($sort, self::ALLOWED_SORTS, true)
        ) {
            $sort = 'latest';
        }

        match ($sort) {
            'deadline_asc' => $query
                ->orderByRaw('deadline IS NULL')
                ->orderBy('deadline', 'asc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc'),

            'deadline_desc' => $query
                ->orderByRaw('deadline IS NULL')
                ->orderBy('deadline', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc'),

            'oldest' => $query
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc'),

            'budget_desc' => $query
                ->orderByRaw('budget IS NULL')
                ->orderBy('budget', 'desc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc'),

            'budget_asc' => $query
                ->orderByRaw('budget IS NULL')
                ->orderBy('budget', 'asc')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc'),

            default => $query
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc'),
        };
    }

    /**
     * GETパラメータに絞り込み値が入っているか判定します。
     */
    private function hasFilterValue(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    /**
     * 検索結果を必ず0件にします。
     */
    private function forceEmptyResult(Builder $query): void
    {
        $query->whereRaw('1 = 0');
    }
}