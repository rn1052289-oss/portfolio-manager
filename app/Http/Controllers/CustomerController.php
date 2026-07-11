<?php

namespace App\Http\Controllers;

use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * ログインユーザーが所有する顧客一覧を表示します。
     */
    public function index()
    {
        $this->authorize('viewAny', Customer::class);

        $customers = auth()->user()
            ->customers()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('customers.index', compact('customers'));
    }
}