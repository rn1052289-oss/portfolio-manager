<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
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
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */

    Route::resource('projects', ProjectController::class);

    /*
    |--------------------------------------------------------------------------
    | Project Tasks
    |--------------------------------------------------------------------------
    */

    /*
     * tasks/{task}より先に定義する。
     *
     * 後ろに置くと「reorder」がtaskパラメータとして
     * 解釈される可能性があるため。
     */
    Route::patch(
        '/projects/{project}/tasks/reorder',
        TaskOrderController::class
    )->name('projects.tasks.reorder');

    Route::scopeBindings()->group(function () {
        Route::resource('projects.tasks', TaskController::class)
            ->only([
                'index',
                'create',
                'store',
                'edit',
                'update',
                'destroy',
            ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */

    Route::resource('customers', CustomerController::class)
        ->only([
            'index',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    Route::resource('categories', CategoryController::class)
        ->only([
            'index',
            'store',
            'edit',
            'update',
            'destroy',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Tags
    |--------------------------------------------------------------------------
    */

    Route::resource('tags', TagController::class)
        ->only([
            'index',
            'store',
            'edit',
            'update',
            'destroy',
        ]);
});