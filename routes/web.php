<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */

    Route::get('/projects', [ProjectController::class, 'index'])
        ->name('projects.index');

    Route::get('/projects/create', [ProjectController::class, 'create'])
        ->name('projects.create');

    Route::post('/projects', [ProjectController::class, 'store'])
        ->name('projects.store');

    /*
|--------------------------------------------------------------------------
| Project Tasks
|--------------------------------------------------------------------------
*/

    Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])
        ->name('projects.tasks.index');

    Route::get('/projects/{project}/tasks/create', [TaskController::class, 'create'])
        ->name('projects.tasks.create');

    Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])
        ->name('projects.tasks.store');

    Route::get('/projects/{project}/tasks/{task}/edit', [TaskController::class, 'edit'])
        ->scopeBindings()
        ->name('projects.tasks.edit');

    Route::patch('/projects/{project}/tasks/reorder', TaskOrderController::class)
        ->name('projects.tasks.reorder');

    Route::patch('/projects/{project}/tasks/{task}', [TaskController::class, 'update'])
        ->scopeBindings()
        ->name('projects.tasks.update');

    Route::delete('/projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])
        ->scopeBindings()
        ->name('projects.tasks.destroy');

    /*
    |--------------------------------------------------------------------------
    | Project Detail / Edit / Delete
    |--------------------------------------------------------------------------
    */

    Route::get('/projects/{project}', [ProjectController::class, 'show'])
        ->name('projects.show');

    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])
        ->name('projects.edit');

    Route::put('/projects/{project}', [ProjectController::class, 'update'])
        ->name('projects.update');

    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
        ->name('projects.destroy');

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    Route::get('/categories', [CategoryController::class, 'index'])
        ->name('categories.index');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->name('categories.store');

    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])
        ->name('categories.edit');

    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->name('categories.update');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->name('categories.destroy');

    /*
    |--------------------------------------------------------------------------
    | Tags
    |--------------------------------------------------------------------------
    */

    Route::get('/tags', [TagController::class, 'index'])
        ->name('tags.index');

    Route::post('/tags', [TagController::class, 'store'])
        ->name('tags.store');

    Route::get('/tags/{tag}/edit', [TagController::class, 'edit'])
        ->name('tags.edit');

    Route::put('/tags/{tag}', [TagController::class, 'update'])
        ->name('tags.update');

    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])
        ->name('tags.destroy');
});