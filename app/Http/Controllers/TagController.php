<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TagController extends Controller
{
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
    public function store(StoreTagRequest $request): RedirectResponse
    {
        auth()->user()
            ->tags()
            ->create($request->validated());

        return redirect()
            ->route('tags.index')
            ->with('success', 'タグを登録しました。');
    }
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
    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        abort_if($tag->user_id !== auth()->id(), 403);

        $tag->update($request->validated());

        return redirect()
            ->route('tags.index')
            ->with('success', 'タグを更新しました。');
    }
    public function destroy(Tag $tag): RedirectResponse
    {
        abort_if($tag->user_id !== auth()->id(), 403);

        $tag->delete();

        return redirect()
            ->route('tags.index')
            ->with('success', 'タグを削除しました。');
    }
}