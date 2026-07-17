<?php

namespace Tests\Feature\Api\Payment;

use App\Contracts\Stripe\StripeServiceInterface;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Stripe\PaymentIntent;
use Tests\Fakes\Stripe\FakeStripeService;
use Tests\TestCase;

class ConfirmPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証ユーザーは決済確定APIを実行できないことを確認します。
     */
    public function test_unauthenticated_user_cannot_confirm_payment(): void
    {
        $user = User::factory()->create();

        $payment = $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_test_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'description' => '未認証テスト用決済',
        ]);

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [
                'payment_method' => 'pm_card_visa',
            ],
            [
                'Idempotency-Key' => (string) Str::uuid(),
            ],
        );

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'paid_at' => null,
        ]);
    }

    /**
     * 他ユーザーが所有する決済は確定できないことを確認します。
     */
    public function test_user_cannot_confirm_another_users_payment(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_feature_test',
        ]);

        $authenticatedUser = User::factory()->create();
        $paymentOwner = User::factory()->create();

        $payment = $paymentOwner->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_policy_test_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'description' => '所有権テスト用決済',
        ]);

        Sanctum::actingAs(
            $authenticatedUser,
            ['payments:confirm']
        );

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [
                'payment_method' => 'pm_card_visa',
            ],
            [
                'Idempotency-Key' => (string) Str::uuid(),
            ],
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $paymentOwner->id,
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'paid_at' => null,
        ]);
    }

    /**
     * payments:confirm abilityを持たないトークンでは
     * 決済確定APIを実行できないことを確認します。
     */
    public function test_token_without_confirm_ability_cannot_confirm_payment(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_feature_test',
        ]);

        $user = User::factory()->create();

        $payment = $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_ability_test_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'description' => 'abilityテスト用決済',
        ]);

        Sanctum::actingAs(
            $user,
            [
                'payments:create',
                'payments:read',
            ]
        );

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [
                'payment_method' => 'pm_card_visa',
            ],
            [
                'Idempotency-Key' => (string) Str::uuid(),
            ],
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $user->id,
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'paid_at' => null,
        ]);
    }

    /**
     * payment_methodがない場合は
     * バリデーションエラーになることを確認します。
     */
    public function test_payment_method_is_required(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_feature_test',
        ]);

        $user = User::factory()->create();

        $payment = $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_validation_test_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'description' => 'バリデーションテスト用決済',
        ]);

        Sanctum::actingAs(
            $user,
            ['payments:confirm']
        );

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [],
            [
                'Idempotency-Key' => (string) Str::uuid(),
            ],
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'payment_method',
            ])
            ->assertJsonPath(
                'errors.payment_method.0',
                '支払い方法を指定してください。'
            );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'paid_at' => null,
        ]);
    }

    /**
     * Idempotency-Keyヘッダーがない場合は
     * バリデーションエラーになることを確認します。
     */
    public function test_idempotency_key_header_is_required(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_feature_test',
        ]);

        $user = User::factory()->create();

        $payment = $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_idempotency_validation_test_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'description' => '冪等性キーバリデーションテスト用決済',
        ]);

        Sanctum::actingAs(
            $user,
            ['payments:confirm']
        );

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [
                'payment_method' => 'pm_card_visa',
            ],
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'idempotency_key',
            ])
            ->assertJsonPath(
                'errors.idempotency_key.0',
                'Idempotency-Keyヘッダーを指定してください。'
            );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            'paid_at' => null,
        ]);
    }

    /**
     * processing状態の決済は再確定できず、
     * 409 Conflictになることを確認します。
     */
    public function test_processing_payment_cannot_be_confirmed(): void
    {
        config([
            'services.stripe.secret' => 'invalid_feature_test_secret',
        ]);

        $user = User::factory()->create();

        $payment = $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_processing_test_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_PROCESSING,
            'description' => '処理中決済テスト',
            'failure_message' => null,
            'paid_at' => null,
            'canceled_at' => null,
        ])->refresh();

        $originalUpdatedAt = $payment->updated_at?->toIso8601String();

        Sanctum::actingAs(
            $user,
            ['payments:confirm']
        );

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [
                'payment_method' => 'pm_card_visa',
            ],
            [
                'Idempotency-Key' => (string) Str::uuid(),
            ],
        );

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                '現在の決済状態では確定処理を実行できません。'
            )
            ->assertJsonPath(
                'errors.status.0',
                '現在のステータスはprocessingです。'
            );

        $this->assertArrayNotHasKey(
            'data',
            $response->json()
        );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_PROCESSING,
            $payment->status
        );

        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);

        $this->assertSame(
            $originalUpdatedAt,
            $payment->updated_at?->toIso8601String()
        );
    }

    /**
     * 成功済みの決済はStripeへ再送せず、
     * 既存の決済結果を返すことを確認します。
     */
    public function test_succeeded_payment_returns_existing_result_without_reconfirmation(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_feature_test',
        ]);

        $user = User::factory()->create();

        $payment = $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_succeeded_test_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_SUCCEEDED,
            'description' => '成功済み決済テスト',
            'failure_message' => null,
            'paid_at' => now()->subMinute()->startOfSecond(),
            'canceled_at' => null,
        ])->refresh();

        $originalUpdatedAt = $payment->updated_at?->toIso8601String();
        $expectedPaidAt = $payment->paid_at?->toIso8601String();

        Sanctum::actingAs(
            $user,
            ['payments:confirm']
        );

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [
                'payment_method' => 'pm_card_visa',
            ],
            [
                'Idempotency-Key' => (string) Str::uuid(),
            ],
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'この決済はすでに完了しています。'
            )
            ->assertJsonPath(
                'data.payment.id',
                $payment->id
            )
            ->assertJsonPath(
                'data.payment.stripe_payment_intent_id',
                $payment->stripe_payment_intent_id
            )
            ->assertJsonPath(
                'data.payment.amount',
                5000
            )
            ->assertJsonPath(
                'data.payment.currency',
                'jpy'
            )
            ->assertJsonPath(
                'data.payment.status',
                Payment::STATUS_SUCCEEDED
            )
            ->assertJsonPath(
                'data.payment.paid_at',
                $expectedPaidAt
            );

        $paymentData = $response->json('data.payment');

        $this->assertIsArray($paymentData);
        $this->assertArrayNotHasKey(
            'client_secret',
            $paymentData
        );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status
        );

        $this->assertSame(
            $expectedPaidAt,
            $payment->paid_at?->toIso8601String()
        );

        $this->assertSame(
            $originalUpdatedAt,
            $payment->updated_at?->toIso8601String()
        );
    }

    /**
     * requires_action状態の決済は追加認証などが必要なため、
     * 決済確定APIから再確定できないことを確認します。
     */
    public function test_requires_action_payment_cannot_be_confirmed(): void
    {
        config([
            'services.stripe.secret' => 'invalid_feature_test_secret',
        ]);

        $user = User::factory()->create();

        $payment = $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_requires_action_test_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_ACTION,
            'description' => '追加認証待ち決済テスト',
            'failure_message' => null,
            'paid_at' => null,
            'canceled_at' => null,
        ])->refresh();

        $originalUpdatedAt = $payment->updated_at?->toIso8601String();

        Sanctum::actingAs(
            $user,
            ['payments:confirm']
        );

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [
                'payment_method' => 'pm_card_visa',
            ],
            [
                'Idempotency-Key' => (string) Str::uuid(),
            ],
        );

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                '現在の決済状態では確定処理を実行できません。'
            )
            ->assertJsonPath(
                'errors.status.0',
                '現在のステータスはrequires_actionです。'
            );

        $this->assertArrayNotHasKey(
            'data',
            $response->json()
        );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_REQUIRES_ACTION,
            $payment->status
        );

        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);

        $this->assertSame(
            $originalUpdatedAt,
            $payment->updated_at?->toIso8601String()
        );
    }

    /**
     * canceled状態の決済はすでに取り消されているため、
     * 決済確定APIから再確定できないことを確認します。
     */
    public function test_canceled_payment_cannot_be_confirmed(): void
    {
        config([
            'services.stripe.secret' => 'invalid_feature_test_secret',
        ]);

        $user = User::factory()->create();

        $canceledAt = now()->subMinute()->startOfSecond();

        $payment = $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_canceled_test_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_CANCELED,
            'description' => 'キャンセル済み決済テスト',
            'failure_message' => null,
            'paid_at' => null,
            'canceled_at' => $canceledAt,
        ])->refresh();

        $originalUpdatedAt = $payment->updated_at?->toIso8601String();
        $expectedCanceledAt = $payment->canceled_at?->toIso8601String();

        Sanctum::actingAs(
            $user,
            ['payments:confirm']
        );

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [
                'payment_method' => 'pm_card_visa',
            ],
            [
                'Idempotency-Key' => (string) Str::uuid(),
            ],
        );

        $response
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                '現在の決済状態では確定処理を実行できません。'
            )
            ->assertJsonPath(
                'errors.status.0',
                '現在のステータスはcanceledです。'
            );

        $this->assertArrayNotHasKey(
            'data',
            $response->json()
        );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_CANCELED,
            $payment->status
        );

        $this->assertNull($payment->paid_at);

        $this->assertSame(
            $expectedCanceledAt,
            $payment->canceled_at?->toIso8601String()
        );

        $this->assertSame(
            $originalUpdatedAt,
            $payment->updated_at?->toIso8601String()
        );
    }

    /**
     * Stripeがsucceededを返した場合、
     * Paymentが支払い完了状態へ同期されることを確認する。
     */
    public function test_payment_can_be_confirmed_successfully(): void
    {
        config([
            'services.stripe.secret' => 'sk_test_feature_test',
        ]);

        $user = User::factory()->create();

        $payment = $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_confirm_success_' . Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 5000,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_CONFIRMATION,
            'description' => '決済確定正常系テスト',
            'failure_message' => null,
            'paid_at' => null,
            'canceled_at' => null,
        ]);

        $fakePaymentIntent = PaymentIntent::constructFrom([
            'id' => $payment->stripe_payment_intent_id,
            'object' => 'payment_intent',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => Payment::STATUS_SUCCEEDED,
            'description' => $payment->description,
            'last_payment_error' => null,
        ]);

        $fakeStripeService = (new FakeStripeService)
            ->returnPaymentIntent($fakePaymentIntent);

        $this->app->instance(
            StripeServiceInterface::class,
            $fakeStripeService,
        );

        Sanctum::actingAs(
            $user,
            ['payments:confirm'],
        );

        $idempotencyKey = (string) Str::uuid();

        $response = $this->postJson(
            route('api.payments.confirm', [
                'payment' => $payment,
            ]),
            [
                'payment_method' => 'pm_card_visa',
            ],
            [
                'Idempotency-Key' => $idempotencyKey,
            ],
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                '決済が完了しました。',
            )
            ->assertJsonPath(
                'data.payment.id',
                $payment->id,
            )
            ->assertJsonPath(
                'data.payment.status',
                Payment::STATUS_SUCCEEDED,
            )
            ->assertJsonPath(
                'data.payment.amount',
                5000,
            )
            ->assertJsonPath(
                'data.payment.currency',
                'jpy',
            )
            ->assertJsonMissingPath(
                'data.client_secret',
            )
            ->assertJsonMissingPath(
                'client_secret',
            );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status,
        );

        $this->assertNotNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);
        $this->assertNull($payment->failure_message);
    }
}
