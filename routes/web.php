<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerCsvExportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentCheckoutController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectCsvExportController;
use App\Http\Controllers\ProjectTaskCsvExportController;
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
| Profile
|--------------------------------------------------------------------------
*/

    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile.show');

    Route::put(
        '/profile/business-profile',
        [ProfileController::class, 'updateBusinessProfile']
    )->name('profile.business-profile.update');

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

    /*
     * 案件に紐づくブラウザ決済ルート。
     *
     * GETでは決済内容を表示し、
     * POSTではStripe PaymentIntentを作成する。
     */
    Route::get(
        '/projects/{project}/payments/create',
        [PaymentCheckoutController::class, 'create']
    )->name('projects.payments.create');

    Route::post(
        '/projects/{project}/payments',
        [PaymentCheckoutController::class, 'store']
    )->name('projects.payments.store');

    /*
     * 作成済みPaymentIntentを使った決済入力画面。
     *
     * PaymentPolicyにより、決済所有者本人だけが閲覧できる。
     */
    Route::get(
        '/payments/{payment}/checkout',
        [PaymentCheckoutController::class, 'checkout']
    )->name('payments.checkout');

    /*
     * Stripeで決済を確定したあとに戻るURL。
     *
     * 現段階では所有者確認後、最新のStripe状態を取得する
     * 決済入力画面へ安全に戻す。
     */
    Route::get(
        '/payments/{payment}/result',
        [PaymentCheckoutController::class, 'result']
    )->name('payments.result');

    Route::resource('projects', ProjectController::class);

    /*
    |--------------------------------------------------------------------------
    | Project Tasks
    |--------------------------------------------------------------------------
    */

    /*
     * タスクCSVは、タスクのリソースルートより先に定義する。
     *
     * 固定文字列の「export」がタスクIDとして
     * 解釈される可能性を避けるため。
     */
    Route::get(
        '/projects/{project}/tasks/export',
        ProjectTaskCsvExportController::class
    )->name('projects.tasks.export');

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
| Invoices
|--------------------------------------------------------------------------
*/

    Route::resource('invoices', InvoiceController::class)
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
