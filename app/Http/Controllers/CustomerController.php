<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
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

        return view(
            'customers.index',
            compact('customers')
        );
    }

    /**
     * 顧客登録画面を表示します。
     */
    public function create()
    {
        $this->authorize('create', Customer::class);

        return view('customers.create');
    }

    /**
     * 顧客を登録します。
     */
    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        auth()->user()
            ->customers()
            ->create($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', '顧客を登録しました。');
    }
}