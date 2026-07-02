<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = auth()->user()
            ->categories()
            ->withCount('projects')
            ->latest()
            ->get();

        return view('categories.index', [
            'categories' => $categories,
            'editingCategory' => null,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        auth()->user()->categories()->create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'color' => '#2563eb',
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'カテゴリを登録しました。');
    }

    public function edit(Category $category): View
    {
        abort_if($category->user_id !== auth()->id(), 403);

        $categories = auth()->user()
            ->categories()
            ->withCount('projects')
            ->latest()
            ->get();

        return view('categories.index', [
            'categories' => $categories,
            'editingCategory' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'カテゴリを更新しました。');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->user_id !== auth()->id(), 403);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'カテゴリを削除しました。');
    }
}