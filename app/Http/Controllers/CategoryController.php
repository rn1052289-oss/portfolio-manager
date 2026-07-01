<?php

namespace App\Http\Controllers;

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
}