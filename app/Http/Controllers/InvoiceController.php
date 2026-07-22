<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\BusinessProfile;
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
        $taxAmount = $this->calculateTaxAmount($subtotal);
        $totalAmount = $subtotal + $taxAmount;

        DB::transaction(function () use ($validated, $userId, $subtotal, $taxAmount, $totalAmount, $invoiceNumberGenerator): void {
            $project = Project::query()
                ->where('user_id', $userId)
                ->findOrFail((int) $validated['project_id']);

            $customer = Customer::query()
                ->where('user_id', $userId)
                ->findOrFail((int) $validated['customer_id']);

            /*
             * 発行者情報はログインユーザーに紐づく
             * BusinessProfileから取得します。
             *
             * リクエストから発行者情報を受け取らないため、
             * 他ユーザーの情報や改ざん値を保存できません。
             */
            $businessProfile = BusinessProfile::query()
                ->where('user_id', $userId)
                ->first();

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

                /*
                 * 発行者情報と振込先を請求書作成時点の内容として
                 * Invoiceへスナップショット保存します。
                 */
                ...$this->issuerSnapshot($businessProfile),

                /*
                 * 現時点では取引日を発行日と同じ日にします。
                 *
                 * 将来、納品日や役務提供日をフォームから
                 * 個別に指定できるよう拡張できます。
                 */
                'transaction_date' => $issuedAt->toDateString(),

                /*
                 * 税率と端数処理も請求書へ保存し、
                 * どの計算仕様を使用したか残します。
                 */
                'tax_rate' => Invoice::DEFAULT_TAX_RATE,
                'tax_rounding_method' => Invoice::TAX_ROUNDING_FLOOR,

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
        ])->loadCount('payments');

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
        $taxAmount = $this->calculateTaxAmount($subtotal);
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

            /*
             * 下書き請求書の更新時点に登録されている
             * 発行者情報を取得します。
             */
            $businessProfile = BusinessProfile::query()
                ->where('user_id', $userId)
                ->first();

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

                /*
                 * 下書き請求書の更新時は、
                 * 現在の事業者プロフィールで発行者情報と
                 * 振込先スナップショットを更新します。
                 */
                ...$this->issuerSnapshot($businessProfile),

                'transaction_date' => $issuedAt->toDateString(),
                'tax_rate' => Invoice::DEFAULT_TAX_RATE,
                'tax_rounding_method' => Invoice::TAX_ROUNDING_FLOOR,

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
     * 下書き請求書を削除します。
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        /*
         * 所有者本人かつ下書きかをPolicyで確認します。
         */
        $this->authorize('delete', $invoice);

        $result = DB::transaction(function () use ($invoice): string {
            /*
             * 編集・送信・決済開始などとの競合を避けるため、
             * 削除対象の請求書を排他ロックして再取得します。
             */
            $lockedInvoice = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * 最初の認可後にステータスが変わった可能性を考慮し、
             * ロック取得後にもう一度削除権限を確認します。
             */
            $this->authorize('delete', $lockedInvoice);

            /*
             * Paymentが存在する請求書を削除すると、
             * payments.invoice_idがnullになり、
             * 決済履歴と請求書の対応が切れてしまいます。
             *
             * 監査性を保つため、1件でも決済履歴があれば
             * 下書きであっても削除を拒否します。
             */
            if ($lockedInvoice->payments()->exists()) {
                return 'has_payments';
            }

            $lockedInvoice->delete();

            return 'deleted';
        });

        if ($result === 'has_payments') {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with(
                    'error',
                    '決済履歴が紐づいている請求書は削除できません。'
                );
        }

        return redirect()
            ->route('invoices.index')
            ->with(
                'success',
                '請求書を削除しました。'
            );
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

    /**
     * BusinessProfileから発行者スナップショットを作成します。
     *
     * BusinessProfileが未登録の場合はnullを保存します。
     * PDF生成時には必要な発行者情報が揃っているか、
     * 別の処理で確認します。
     *
     * @return array<string, string|null>
     */
    private function issuerSnapshot(
        ?BusinessProfile $businessProfile
    ): array {
        return [
            'issuer_business_name' => $businessProfile?->business_name,

            'issuer_representative_name' => $businessProfile?->representative_name,

            'issuer_email' => $businessProfile?->email,

            'issuer_phone' => $businessProfile?->phone,

            'issuer_postal_code' => $businessProfile?->postal_code,

            'issuer_address' => $businessProfile?->address,

            'issuer_registration_number' => $businessProfile?->invoice_registration_number,

            'issuer_bank_name' => $businessProfile?->bank_name,

            'issuer_bank_branch_name' => $businessProfile?->bank_branch_name,

            'issuer_bank_account_type' => $businessProfile?->bank_account_type,

            /*
             * Model経由で取得しているため、
             * BusinessProfile側で復号された値です。
             *
             * Invoiceへ保存するときはInvoiceモデルの
             * encrypted castにより再暗号化されます。
             */
            'issuer_bank_account_number' => $businessProfile?->bank_account_number,

            'issuer_bank_account_holder' => $businessProfile?->bank_account_holder,
        ];
    }

    /**
     * 現在の税率と端数処理方法で消費税を計算します。
     */
    private function calculateTaxAmount(int $subtotal): int
    {
        return intdiv(
            $subtotal * Invoice::DEFAULT_TAX_RATE,
            100
        );
    }
}
