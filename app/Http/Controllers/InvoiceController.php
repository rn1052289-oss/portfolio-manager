<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * ログインユーザーの請求書一覧を表示します。
     */
    public function index()
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->where('user_id', auth()->id())
            ->with([
                'project:id,title',
                'customer:id,name',
            ])
            ->latest('id')
            ->paginate(10);

        return view('invoices.index', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * 請求書の新規作成画面を表示します。
     */
    public function create()
    {
        $this->authorize('create', Invoice::class);

        $userId = auth()->id();

        $projects = Project::query()
            ->where('user_id', $userId)
            ->select([
                'id',
                'customer_id',
                'title',
                'client_name',
            ])
            ->orderBy('title')
            ->get();

        $customers = Customer::query()
            ->where('user_id', $userId)
            ->select([
                'id',
                'name',
                'contact_name',
            ])
            ->orderBy('name')
            ->get();

        return view('invoices.create', [
            'projects' => $projects,
            'customers' => $customers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}
