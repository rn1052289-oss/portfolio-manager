<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\InvoiceNumberGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
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

        return view('invoices.form', [
            'invoice' => null,
            ...$this->formOptions((int) auth()->id()),
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
                ->findOrFail((int) $validated['project_id']);

            $customer = Customer::query()
                ->where('user_id', $userId)
                ->findOrFail((int) $validated['customer_id']);

            $issuedAt = Carbon::parse($validated['issued_at']);

            /*
             * ユーザー・発行年月単位の連番管理テーブルから、
             * 重複しない請求書番号を発行します。
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
                 */
                ...$this->customerSnapshot($customer),

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
             * 認証済みユーザーから設定します。
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
    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'project:id,title',
            'customer:id,name',
        ]);

        return view('invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * 請求書の編集画面を表示します。
     */
    public function edit(Invoice $invoice): View
    {
        /*
         * InvoicePolicyにより、
         * 所有者本人かつ下書きの場合だけ許可します。
         */
        $this->authorize('update', $invoice);

        return view('invoices.form', [
            'invoice' => $invoice,
            ...$this->formOptions((int) auth()->id()),
        ]);
    }

    /**
     * 下書き請求書を更新します。
     */
    public function update(
        UpdateInvoiceRequest $request,
        Invoice $invoice,
        InvoiceNumberGenerator $invoiceNumberGenerator
    ): RedirectResponse {
        $validated = $request->validated();
        $userId = (int) $request->user()->id;

        /*
         * 更新時もブラウザ側の計算結果は信用せず、
         * 小計からサーバー側で再計算します。
         */
        $subtotal = (int) $validated['subtotal'];
        $taxAmount = intdiv($subtotal, 10);
        $totalAmount = $subtotal + $taxAmount;

        DB::transaction(function () use ($invoice, $validated, $userId, $subtotal, $taxAmount, $totalAmount, $invoiceNumberGenerator): void {
            /*
             * 同時に送信・更新される競合を防ぐため、
             * 対象請求書を排他ロックして再取得します。
             */
            $lockedInvoice = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * FormRequest通過後に状態が変わった可能性も考慮し、
             * ロック取得後にもう一度Policyを確認します。
             */
            $this->authorize('update', $lockedInvoice);

            $project = Project::query()
                ->where('user_id', $userId)
                ->findOrFail((int) $validated['project_id']);

            $customer = Customer::query()
                ->where('user_id', $userId)
                ->findOrFail((int) $validated['customer_id']);

            $issuedAt = Carbon::parse($validated['issued_at']);

            $invoiceNumber = $lockedInvoice->invoice_number;

            /*
             * 発行月が変わる場合は、
             * 新しい発行年月の請求書番号を採番します。
             *
             * 同じ発行月内の日付変更では番号を維持します。
             */
            if (
                $lockedInvoice->issued_at?->format('Ym')
                !== $issuedAt->format('Ym')
            ) {
                $invoiceNumber = $invoiceNumberGenerator->generate(
                    $userId,
                    $issuedAt
                );
            }

            $lockedInvoice->fill([
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'invoice_number' => $invoiceNumber,

                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,

                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,

                /*
                 * 下書きは未送信のため、保存時点の顧客情報で
                 * 請求先スナップショットを更新します。
                 */
                ...$this->customerSnapshot($customer),

                'issued_at' => $issuedAt->toDateString(),
                'due_date' => $validated['due_date'],
            ]);

            $lockedInvoice->save();
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with(
                'success',
                '請求書を更新しました。'
            );
    }

    /**
     * 請求書を削除します。
     */
    public function destroy(Invoice $invoice)
    {
        //
    }

    /**
     * 請求書フォームで使用する案件と顧客を取得します。
     *
     * @return array{
     *     projects: Collection<int, Project>,
     *     customers: Collection<int, Customer>
     * }
     */
    private function formOptions(int $userId): array
    {
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

        return [
            'projects' => $projects,
            'customers' => $customers,
        ];
    }

    /**
     * Customerから請求先スナップショットを作成します。
     *
     * @return array<string, string|null>
     */
    private function customerSnapshot(Customer $customer): array
    {
        return [
            'billing_name' => $customer->name,
            'billing_contact_name' => $customer->contact_name,
            'billing_email' => $customer->email,
            'billing_phone' => $customer->phone,
            'billing_postal_code' => $customer->postal_code,
            'billing_address' => $customer->address,
        ];
    }
}
