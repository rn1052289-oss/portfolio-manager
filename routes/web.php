<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerCsvExportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectCsvExportController;
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

    /*
     * projects/{project}より先に定義する。
     *
     * 後ろに置くと「export」がProjectの
     * ルートモデルバインディング用パラメータとして
     * 解釈される可能性があるため。
     */
    Route::get(
        '/projects/export',
        ProjectCsvExportController::class
    )->name('projects.export');

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

    /*
     * customers/{customer}より先に定義する。
     *
     * 後ろに置くと「export」がCustomerの
     * ルートモデルバインディング用パラメータとして
     * 解釈される可能性があるため。
     */
    Route::get(
        '/customers/export',
        CustomerCsvExportController::class
    )->name('customers.export');

    Route::resource('customers', CustomerController::class)
        ->only([
            'index',
            'create',
            'store',
            'show',
            'edit',
            'update',
            'destroy',
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