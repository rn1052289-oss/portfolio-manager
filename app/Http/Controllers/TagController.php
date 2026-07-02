<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TagController extends Controller
{
    /**
     * タグ管理画面を表示する
     */
    public function index(): View
    {
        $tags = auth()->user()
            ->tags()
            ->withCount('projects')
            ->latest()
            ->paginate(10);

        $editingTag = null;

        return view('tags.index', compact('tags', 'editingTag'));
    }

    /**
     * タグを登録する
     */
    public function store(StoreTagRequest $request): RedirectResponse
    {
        auth()->user()
            ->tags()
            ->create($request->validated());

        return redirect()
            ->route('tags.index')
            ->with('success', 'タグを登録しました。');
    }

    /**
     * タグ編集画面を表示する
     */
    public function edit(Tag $tag): View
    {
        abort_if($tag->user_id !== auth()->id(), 403);

        $tags = auth()->user()
            ->tags()
            ->withCount('projects')
            ->latest()
            ->paginate(10);

        $editingTag = $tag;

        return view('tags.index', compact('tags', 'editingTag'));
    }

    /**
     * タグを更新する
     */
    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        abort_if($tag->user_id !== auth()->id(), 403);

        $tag->update($request->validated());

        return redirect()
            ->route('tags.index')
            ->with('success', 'タグを更新しました。');
    }
}