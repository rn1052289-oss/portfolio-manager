<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\InvoiceNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class InvoiceController extends Controller
{
    /**
     * ログインユーザーの請求書一覧を表示します。
     */
    public function index(): View
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
     * 請求書の新規作成画面を表示します。
     */
    public function create(): View
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
     * 新しい請求書を下書きとして保存します。
     */
    public function store(
        StoreInvoiceRequest $request,
        InvoiceNumberGenerator $invoiceNumberGenerator
    ): RedirectResponse {
        $validated = $request->validated();
        $userId = (int) $request->user()->id;

        /*
         * ブラウザから送信された税額と合計金額は信用せず、
         * 小計からサーバー側で再計算します。
         */
        $subtotal = (int) $validated['subtotal'];
        $taxAmount = intdiv($subtotal, 10);
        $totalAmount = $subtotal + $taxAmount;

        DB::transaction(function () use ($validated, $userId, $subtotal, $taxAmount, $totalAmount, $invoiceNumberGenerator): void {
            $project = Project::query()
                ->where('user_id', $userId)
                ->findOrFail(
                    (int) $validated['project_id']
                );

            $customer = Customer::query()
                ->where('user_id', $userId)
                ->findOrFail(
                    (int) $validated['customer_id']
                );

            $issuedAt = Carbon::parse(
                $validated['issued_at']
            );

            /*
             * ユーザー・発行年月単位の連番管理テーブルから、
             * 重複しない請求書番号を発行します。
             *
             * この採番と請求書保存は、同じトランザクション内で
             * 確定またはロールバックされます。
             */
            $invoiceNumber = $invoiceNumberGenerator->generate(
                $userId,
                $issuedAt
            );

            $invoice = new Invoice([
                'project_id' => $project->id,
                'customer_id' => $customer->id,

                'invoice_number' => $invoiceNumber,
                'public_token' => (string) Str::uuid(),

                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,

                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => 'jpy',

                /*
                 * 顧客情報を請求書作成時点の内容として保存します。
                 *
                 * 後からCustomerが変更されても、
                 * 過去の請求書に表示する請求先情報は変わりません。
                 */
                'billing_name' => $customer->name,
                'billing_contact_name' => $customer->contact_name,
                'billing_email' => $customer->email,
                'billing_phone' => $customer->phone,
                'billing_postal_code' => $customer->postal_code,
                'billing_address' => $customer->address,

                'status' => Invoice::STATUS_DRAFT,

                'issued_at' => $issuedAt->toDateString(),
                'due_date' => $validated['due_date'],

                'sent_at' => null,
                'paid_at' => null,
                'canceled_at' => null,

                'pdf_path' => null,
                'last_sent_to' => null,
                'send_count' => 0,
            ]);

            /*
             * user_idはリクエストから受け取らず、
             * 認証済みユーザーから明示的に設定します。
             */
            $invoice->user_id = $userId;

            $invoice->save();
        });

        return redirect()
            ->route('invoices.index')
            ->with(
                'success',
                '請求書を下書きとして保存しました。'
            );
    }

    /**
     * 請求書の詳細画面を表示します。
     */
    public function show(Invoice $invoice)
    {
        //
    }

    /**
     * 請求書の編集画面を表示します。
     */
    public function edit(Invoice $invoice)
    {
        //
    }

    /**
     * 請求書を更新します。
     */
    public function update(
        Request $request,
        Invoice $invoice
    ) {
        //
    }

    /**
     * 請求書を削除します。
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}
