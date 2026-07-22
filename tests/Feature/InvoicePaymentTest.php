<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\Stripe\StripeServiceInterface;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;
use Tests\Fakes\Stripe\FakeStripeService;
use Tests\TestCase;

final class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeService $fakeStripeService;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * 公開請求書画面ではStripeServiceInterfaceが注入されます。
         *
         * Paymentが存在しない表示テストではStripe通信しませんが、
         * 実際のStripeServiceが生成されないようFakeへ差し替えます。
         */
        $this->fakeStripeService = new FakeStripeService;

        $this->app->instance(
            StripeServiceInterface::class,
            $this->fakeStripeService,
        );
    }

    /**
     * 未ログインの顧客でも送信済み請求書を表示できます。
     */
    public function test_guest_can_view_sent_invoice_payment_page(): void
    {
        $invoice = Invoice::factory()
            ->sent()
            ->create([
                'invoice_number' => 'INV-202607-0001',
                'title' => 'Webサイト制作費',
                'billing_name' => 'テスト株式会社',
                'issuer_business_name' => 'ClientWork Studio',
                'total_amount' => 110_000,
                'currency' => 'jpy',
            ]);

        $response = $this->get(
            route('invoice-payments.show', [
                'publicToken' => $invoice->public_token,
            ])
        );

        $response
            ->assertOk()
            ->assertViewIs('invoice-payments.show')
            ->assertViewHas('invoice', function (Invoice $viewInvoice) use ($invoice): bool {
                return $viewInvoice->is($invoice);
            })
            ->assertViewHas('pageState', 'payable')
            ->assertSee('オンライン請求書')
            ->assertSee('ClientWork Studio')
            ->assertSee('INV-202607-0001')
            ->assertSee('Webサイト制作費')
            ->assertSee('テスト株式会社')
            ->assertSee('¥110,000')
            ->assertSee('お支払い受付中')
            ->assertSee('オンライン決済を開始する')
            ->assertDontSee('Stripe Payment Elementを読み込んでいます。');
    }

    /**
     * 期限超過の請求書も支払い可能な公開画面を表示します。
     */
    public function test_guest_can_view_overdue_invoice_payment_page(): void
    {
        $invoice = Invoice::factory()
            ->overdue()
            ->create();

        $response = $this->get(
            route('invoice-payments.show', [
                'publicToken' => $invoice->public_token,
            ])
        );

        $response
            ->assertOk()
            ->assertViewHas('pageState', 'payable')
            ->assertSee('支払期限超過')
            ->assertSee('支払期限を過ぎています')
            ->assertSee('この請求書はオンラインでお支払いいただけます。')
            ->assertSee('オンライン決済を開始する');
    }

    /**
     * 支払い済み請求書では再決済用の表示を出しません。
     */
    public function test_paid_invoice_displays_paid_message_without_payment_form(): void
    {
        $invoice = Invoice::factory()
            ->paid()
            ->create();

        $response = $this->get(
            route('invoice-payments.show', [
                'publicToken' => $invoice->public_token,
            ])
        );

        $response
            ->assertOk()
            ->assertViewHas('pageState', 'paid')
            ->assertSee('お支払い済みです')
            ->assertSee('再度お支払いいただく必要はありません。')
            ->assertDontSee('オンライン決済を開始する')
            ->assertDontSee('支払いを確定する');
    }

    /**
     * キャンセル済み請求書では決済できない案内を表示します。
     */
    public function test_canceled_invoice_displays_canceled_message_without_payment_form(): void
    {
        $invoice = Invoice::factory()
            ->canceled()
            ->create();

        $response = $this->get(
            route('invoice-payments.show', [
                'publicToken' => $invoice->public_token,
            ])
        );

        $response
            ->assertOk()
            ->assertViewHas('pageState', 'canceled')
            ->assertSee('この請求書はキャンセルされています')
            ->assertSee('このページからお支払いいただくことはできません。')
            ->assertDontSee('オンライン決済を開始する')
            ->assertDontSee('支払いを確定する');
    }

    /**
     * 下書き請求書は外部へ公開しません。
     */
    public function test_draft_invoice_returns_not_found(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->get(
            route('invoice-payments.show', [
                'publicToken' => $invoice->public_token,
            ])
        );

        $response->assertNotFound();
    }

    /**
     * 存在しないUUIDでは請求書を表示しません。
     */
    public function test_unknown_public_token_returns_not_found(): void
    {
        $response = $this->get(
            route('invoice-payments.show', [
                'publicToken' => (string) Str::uuid(),
            ])
        );

        $response->assertNotFound();
    }

    /**
     * UUID形式ではない値はルート制約で拒否します。
     */
    public function test_invalid_public_token_format_returns_not_found(): void
    {
        $response = $this->get('/invoice-payments/not-a-valid-uuid');

        $response->assertNotFound();
    }

    /**
     * 公開請求書ページをキャッシュ・検索登録させないヘッダーを返します。
     */
    public function test_public_invoice_page_returns_security_headers(): void
    {
        $invoice = Invoice::factory()
            ->sent()
            ->create();

        $response = $this->get(
            route('invoice-payments.show', [
                'publicToken' => $invoice->public_token,
            ])
        );

        $response
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader(
                'X-Robots-Tag',
                'noindex, nofollow, noarchive'
            );

        /*
         * Cache-Controlのディレクティブ順は、
         * Symfonyによって正規化される場合があります。
         *
         * 順序ではなく、必要な設定がすべて
         * 含まれていることを確認します。
         */
        $cacheControl = (string) $response->headers->get(
            'Cache-Control'
        );

        $this->assertStringContainsString(
            'no-store',
            $cacheControl
        );

        $this->assertStringContainsString(
            'private',
            $cacheControl
        );

        $this->assertStringContainsString(
            'max-age=0',
            $cacheControl
        );
    }

    /**
     * 顧客が決済を開始するとPaymentが作成され、
     * 公開ページへStripe Payment Elementが表示されます。
     */
    public function test_guest_can_start_invoice_payment_and_view_payment_element(): void
    {
        config([
            'services.stripe.key' => 'pk_test_public_invoice_payment',
        ]);

        $invoice = Invoice::factory()
            ->sent()
            ->create([
                'invoice_number' => 'INV-202607-0100',
                'title' => 'コーポレートサイト制作費',
                'total_amount' => 165_000,
                'currency' => 'jpy',
            ]);

        $paymentIntentId = 'pi_invoice_public_'.Str::uuid();
        $clientSecret = $paymentIntentId.'_secret_test';

        $fakePaymentIntent = PaymentIntent::constructFrom([
            'id' => $paymentIntentId,
            'object' => 'payment_intent',
            'amount' => 165_000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'description' => '請求書 INV-202607-0100：コーポレートサイト制作費',
            'client_secret' => $clientSecret,
            'last_payment_error' => null,
        ]);

        $this->fakeStripeService
            ->returnPaymentIntent($fakePaymentIntent);

        /*
         * 顧客が「オンライン決済を開始する」を押した状態です。
         *
         * 金額・通貨・Invoice IDなどはPOSTしていません。
         */
        $storeResponse = $this->post(
            route('invoice-payments.store', [
                'publicToken' => $invoice->public_token,
            ])
        );

        $storeResponse
            ->assertRedirect(
                route('invoice-payments.show', [
                    'publicToken' => $invoice->public_token,
                ])
            )
            ->assertSessionHas(
                'success',
                '安全な決済フォームを準備しました。'
            );

        $payment = Payment::query()
            ->where('invoice_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame(
            $invoice->user_id,
            $payment->user_id
        );

        $this->assertSame(
            $invoice->project_id,
            $payment->project_id
        );

        $this->assertSame(
            $paymentIntentId,
            $payment->stripe_payment_intent_id
        );

        $this->assertSame(
            165_000,
            $payment->amount
        );

        $this->assertSame(
            'jpy',
            $payment->currency
        );

        $this->assertSame(
            Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            $payment->status
        );

        /*
         * リダイレクト後の公開ページでは、
         * 作成済みPaymentIntentをStripeから再取得します。
         */
        $showResponse = $this->get(
            route('invoice-payments.show', [
                'publicToken' => $invoice->public_token,
            ])
        );

        $showResponse
            ->assertOk()
            ->assertViewHas('payment', function (mixed $viewPayment) use ($payment): bool {
                return $viewPayment instanceof Payment
                    && $viewPayment->is($payment);
            })
            ->assertViewHas('canContinuePayment', true)
            ->assertViewHas('clientSecret', $clientSecret)
            ->assertViewHas(
                'stripePublicKey',
                'pk_test_public_invoice_payment'
            )
            ->assertViewHas(
                'returnUrl',
                route('invoice-payments.result', [
                    'publicToken' => $invoice->public_token,
                ])
            )
            ->assertSee('お支払い情報')
            ->assertSee('Stripe Payment Elementを読み込んでいます。')
            ->assertSee('支払いを確定する')
            ->assertDontSee('オンライン決済を開始する');

        /*
         * Stripeへ渡した値がInvoice由来であることを確認します。
         */
        $createCalls = $this->fakeStripeService
            ->createPaymentIntentCalls();

        $this->assertCount(1, $createCalls);

        $this->assertSame(
            165_000,
            $createCalls[0]['amount']
        );

        $this->assertSame(
            'jpy',
            $createCalls[0]['currency']
        );

        $this->assertSame(
            [
                'payment_type' => 'invoice',
                'invoice_id' => (string) $invoice->id,
            ],
            $createCalls[0]['metadata']
        );

        $this->assertArrayNotHasKey(
            'public_token',
            $createCalls[0]['metadata']
        );

        $this->assertSame(
            [$paymentIntentId],
            $this->fakeStripeService
                ->retrievePaymentIntentIds()
        );
    }
}
