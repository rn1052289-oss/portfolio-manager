<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Actions\Payment\CreateInvoicePaymentIntentAction;
use App\Contracts\Stripe\StripeServiceInterface;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use Stripe\PaymentIntent;
use Tests\Fakes\Stripe\FakeStripeService;
use Tests\TestCase;

final class CreateInvoicePaymentIntentActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Invoiceの金額と通貨を使ってPaymentIntentとPaymentを作成します。
     */
    public function test_action_creates_invoice_payment_intent_and_payment(): void
    {
        $invoice = Invoice::factory()
            ->sent()
            ->create([
                'invoice_number' => 'INV-202607-0001',
                'title' => 'Webサイト制作費',
                'total_amount' => 110_000,
                'currency' => 'jpy',
            ]);

        $fakePaymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_invoice_create_'.Str::uuid(),
            'object' => 'payment_intent',
            'amount' => 110_000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'description' => '請求書 INV-202607-0001：Webサイト制作費',
            'last_payment_error' => null,
        ]);

        $fakeStripeService = (new FakeStripeService)
            ->returnPaymentIntent($fakePaymentIntent);

        $this->app->instance(
            StripeServiceInterface::class,
            $fakeStripeService,
        );

        $result = $this->app
            ->make(CreateInvoicePaymentIntentAction::class)
            ->execute($invoice);

        $payment = $result['payment'];

        $this->assertFalse($result['reused']);

        $this->assertSame(
            $invoice->id,
            $payment->invoice_id
        );

        $this->assertSame(
            $invoice->user_id,
            $payment->user_id
        );

        $this->assertSame(
            $invoice->project_id,
            $payment->project_id
        );

        $this->assertSame(
            110_000,
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

        $this->assertSame(
            $fakePaymentIntent->id,
            $payment->stripe_payment_intent_id
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $invoice->user_id,
            'project_id' => $invoice->project_id,
            'invoice_id' => $invoice->id,
            'stripe_payment_intent_id' => $fakePaymentIntent->id,
            'amount' => 110_000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        ]);

        $calls = $fakeStripeService
            ->createPaymentIntentCalls();

        $this->assertCount(1, $calls);

        $this->assertSame(
            110_000,
            $calls[0]['amount']
        );

        $this->assertSame(
            'jpy',
            $calls[0]['currency']
        );

        $this->assertSame(
            [
                'payment_type' => 'invoice',
                'invoice_id' => (string) $invoice->id,
            ],
            $calls[0]['metadata']
        );

        /*
         * 公開ページへのアクセス鍵は、
         * Stripe metadataへ送信しません。
         */
        $this->assertArrayNotHasKey(
            'public_token',
            $calls[0]['metadata']
        );

        $this->assertTrue(
            Str::isUuid($calls[0]['idempotency_key'])
        );
    }

    /**
     * 支払い途中のPaymentがある場合は既存PaymentIntentを再利用します。
     */
    public function test_action_reuses_existing_payment_intent(): void
    {
        $invoice = Invoice::factory()
            ->sent()
            ->create([
                'total_amount' => 55_000,
                'currency' => 'jpy',
            ]);

        $existingPayment = $this->createPaymentForInvoice(
            invoice: $invoice,
            status: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            amount: 55_000,
        );

        $fakePaymentIntent = PaymentIntent::constructFrom([
            'id' => $existingPayment->stripe_payment_intent_id,
            'object' => 'payment_intent',
            'amount' => 55_000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'description' => $existingPayment->description,
            'last_payment_error' => null,
        ]);

        $fakeStripeService = (new FakeStripeService)
            ->returnPaymentIntent($fakePaymentIntent);

        $this->app->instance(
            StripeServiceInterface::class,
            $fakeStripeService,
        );

        $result = $this->app
            ->make(CreateInvoicePaymentIntentAction::class)
            ->execute($invoice);

        $this->assertTrue($result['reused']);

        $this->assertSame(
            $existingPayment->id,
            $result['payment']->id
        );

        /*
         * 新しいPaymentIntentは作成していません。
         */
        $this->assertCount(
            0,
            $fakeStripeService->createPaymentIntentCalls()
        );

        $this->assertSame(
            [$existingPayment->stripe_payment_intent_id],
            $fakeStripeService->retrievePaymentIntentIds()
        );

        $this->assertDatabaseCount('payments', 1);
    }

    /**
     * 下書き請求書ではPaymentIntentを作成しません。
     */
    public function test_action_rejects_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $fakeStripeService = new FakeStripeService;

        $this->app->instance(
            StripeServiceInterface::class,
            $fakeStripeService,
        );

        try {
            $this->app
                ->make(CreateInvoicePaymentIntentAction::class)
                ->execute($invoice);

            $this->fail(
                '下書き請求書の決済作成が拒否されませんでした。'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'この請求書は現在支払いできません。',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('payments', 0);

        $this->assertCount(
            0,
            $fakeStripeService->createPaymentIntentCalls()
        );
    }

    /**
     * 成功済みPaymentがある請求書では再決済を開始しません。
     */
    public function test_action_rejects_invoice_with_succeeded_payment(): void
    {
        $invoice = Invoice::factory()
            ->sent()
            ->create();

        $this->createPaymentForInvoice(
            invoice: $invoice,
            status: Payment::STATUS_SUCCEEDED,
            amount: (int) $invoice->total_amount,
        );

        $fakeStripeService = new FakeStripeService;

        $this->app->instance(
            StripeServiceInterface::class,
            $fakeStripeService,
        );

        try {
            $this->app
                ->make(CreateInvoicePaymentIntentAction::class)
                ->execute($invoice);

            $this->fail(
                '成功済み決済がある請求書の再決済が拒否されませんでした。'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'この請求書には成功済みの決済があります。',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('payments', 1);

        $this->assertCount(
            0,
            $fakeStripeService->createPaymentIntentCalls()
        );
    }

    /**
     * Stripeが異なる金額を返した場合はPaymentを保存しません。
     */
    public function test_action_rejects_mismatched_stripe_amount(): void
    {
        $invoice = Invoice::factory()
            ->sent()
            ->create([
                'total_amount' => 110_000,
                'currency' => 'jpy',
            ]);

        $fakePaymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_invoice_mismatch_'.Str::uuid(),
            'object' => 'payment_intent',
            'amount' => 99_000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'last_payment_error' => null,
        ]);

        $fakeStripeService = (new FakeStripeService)
            ->returnPaymentIntent($fakePaymentIntent);

        $this->app->instance(
            StripeServiceInterface::class,
            $fakeStripeService,
        );

        try {
            $this->app
                ->make(CreateInvoicePaymentIntentAction::class)
                ->execute($invoice);

            $this->fail(
                'Stripeとの金額不一致が拒否されませんでした。'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Stripeと請求書の決済情報が一致しません。',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('payments', 0);

        $this->assertCount(
            1,
            $fakeStripeService->createPaymentIntentCalls()
        );
    }

    /**
     * テスト用のPaymentをInvoiceへ紐付けて作成します。
     */
    private function createPaymentForInvoice(
        Invoice $invoice,
        string $status,
        int $amount,
    ): Payment {
        $payment = new Payment([
            'project_id' => $invoice->project_id,
            'stripe_payment_intent_id' => 'pi_invoice_'.Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => $amount,
            'currency' => $invoice->currency,
            'status' => $status,
            'description' => '請求書決済テスト',
            'failure_message' => null,
            'paid_at' => $status === Payment::STATUS_SUCCEEDED
                ? now()
                : null,
            'canceled_at' => $status === Payment::STATUS_CANCELED
                ? now()
                : null,
            'metadata' => [
                'payment_type' => 'invoice',
                'invoice_id' => (string) $invoice->id,
            ],
        ]);

        /*
         * user_idはfillableではないため、
         * 請求書所有者から明示的に設定します。
         */
        $payment->user_id = $invoice->user_id;

        $invoice->payments()->save($payment);

        return $payment->refresh();
    }
}
