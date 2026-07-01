<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
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

        return view('categories.index', compact('categories'));
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
}