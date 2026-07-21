<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceNumberSequence;
use App\Models\Project;
use App\Models\User;
use App\Services\InvoiceNumberGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Tests\TestCase;

final class InvoiceNumberGeneratorTest extends TestCase
{
    use DatabaseMigrations;

    private InvoiceNumberGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = app(
            InvoiceNumberGenerator::class
        );
    }

    /**
     * 初回の請求書番号は0001から始まります。
     */
    public function test_first_invoice_number_starts_at_0001(): void
    {
        $user = User::factory()->create();

        $issuedAt = CarbonImmutable::parse(
            '2026-07-21'
        );

        $invoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $user->id,
                $issuedAt
            )
        );

        $this->assertSame(
            'INV-202607-0001',
            $invoiceNumber
        );

        $this->assertDatabaseHas(
            'invoice_number_sequences',
            [
                'user_id' => $user->id,
                'period' => '202607',
                'last_number' => 1,
            ]
        );
    }

    /**
     * 同じユーザー・同じ月の2件目は0002になります。
     */
    public function test_second_invoice_number_in_same_month_is_0002(): void
    {
        $user = User::factory()->create();

        $issuedAt = CarbonImmutable::parse(
            '2026-07-21'
        );

        $firstInvoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $user->id,
                $issuedAt
            )
        );

        $secondInvoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $user->id,
                $issuedAt
            )
        );

        $this->assertSame(
            'INV-202607-0001',
            $firstInvoiceNumber
        );

        $this->assertSame(
            'INV-202607-0002',
            $secondInvoiceNumber
        );

        $this->assertDatabaseHas(
            'invoice_number_sequences',
            [
                'user_id' => $user->id,
                'period' => '202607',
                'last_number' => 2,
            ]
        );
    }

    /**
     * 発行月が変わると連番は0001へ戻ります。
     */
    public function test_invoice_number_resets_when_period_changes(): void
    {
        $user = User::factory()->create();

        $julyInvoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $user->id,
                CarbonImmutable::parse('2026-07-31')
            )
        );

        $augustInvoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $user->id,
                CarbonImmutable::parse('2026-08-01')
            )
        );

        $this->assertSame(
            'INV-202607-0001',
            $julyInvoiceNumber
        );

        $this->assertSame(
            'INV-202608-0001',
            $augustInvoiceNumber
        );

        $this->assertDatabaseHas(
            'invoice_number_sequences',
            [
                'user_id' => $user->id,
                'period' => '202607',
                'last_number' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'invoice_number_sequences',
            [
                'user_id' => $user->id,
                'period' => '202608',
                'last_number' => 1,
            ]
        );
    }

    /**
     * ユーザーが異なる場合は、それぞれ0001から始まります。
     */
    public function test_invoice_number_sequence_is_independent_for_each_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $issuedAt = CarbonImmutable::parse(
            '2026-07-21'
        );

        $firstUserInvoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $firstUser->id,
                $issuedAt
            )
        );

        $secondUserInvoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $secondUser->id,
                $issuedAt
            )
        );

        $this->assertSame(
            'INV-202607-0001',
            $firstUserInvoiceNumber
        );

        $this->assertSame(
            'INV-202607-0001',
            $secondUserInvoiceNumber
        );

        $this->assertDatabaseHas(
            'invoice_number_sequences',
            [
                'user_id' => $firstUser->id,
                'period' => '202607',
                'last_number' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'invoice_number_sequences',
            [
                'user_id' => $secondUser->id,
                'period' => '202607',
                'last_number' => 1,
            ]
        );
    }

    /**
     * トランザクション外からの採番は禁止されます。
     */
    public function test_generator_throws_exception_outside_transaction(): void
    {
        $user = User::factory()->create();

        $this->expectException(
            LogicException::class
        );

        $this->expectExceptionMessage(
            '請求書番号の生成はDBトランザクション内で実行してください。'
        );

        $this->generator->generate(
            $user->id,
            CarbonImmutable::parse('2026-07-21')
        );
    }

    /**
     * トランザクション失敗時は連番の更新も取り消されます。
     */
    public function test_sequence_increment_is_rolled_back_when_transaction_fails(): void
    {
        $user = User::factory()->create();

        $issuedAt = CarbonImmutable::parse(
            '2026-07-21'
        );

        $firstInvoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $user->id,
                $issuedAt
            )
        );

        $this->assertSame(
            'INV-202607-0001',
            $firstInvoiceNumber
        );

        try {
            DB::transaction(function () use ($user, $issuedAt): void {
                $invoiceNumber = $this->generator->generate(
                    $user->id,
                    $issuedAt
                );

                $this->assertSame(
                    'INV-202607-0002',
                    $invoiceNumber
                );

                throw new RuntimeException(
                    '採番後の保存処理に失敗しました。'
                );
            });

            $this->fail(
                'RuntimeExceptionが発生しませんでした。'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                '採番後の保存処理に失敗しました。',
                $exception->getMessage()
            );
        }

        /*
         * 失敗した0002は確定していないため、
         * 次の正常な採番でも0002が発行されます。
         */
        $nextInvoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $user->id,
                $issuedAt
            )
        );

        $this->assertSame(
            'INV-202607-0002',
            $nextInvoiceNumber
        );

        $this->assertDatabaseHas(
            'invoice_number_sequences',
            [
                'user_id' => $user->id,
                'period' => '202607',
                'last_number' => 2,
            ]
        );
    }

    /**
     * 既存請求書のランダムな番号は新しい採番へ影響しません。
     */
    public function test_existing_random_invoice_number_does_not_affect_sequence(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        Invoice::factory()
            ->for($user)
            ->for($project)
            ->for($customer)
            ->create([
                'invoice_number' => 'INV-202607-9188',
                'issued_at' => '2026-07-21',
            ]);

        $invoiceNumber = DB::transaction(
            fn (): string => $this->generator->generate(
                $user->id,
                CarbonImmutable::parse('2026-07-21')
            )
        );

        $this->assertSame(
            'INV-202607-0001',
            $invoiceNumber
        );

        $this->assertDatabaseHas(
            'invoices',
            [
                'user_id' => $user->id,
                'invoice_number' => 'INV-202607-9188',
            ]
        );

        $this->assertDatabaseHas(
            'invoice_number_sequences',
            [
                'user_id' => $user->id,
                'period' => '202607',
                'last_number' => 1,
            ]
        );

        $this->assertSame(
            1,
            InvoiceNumberSequence::query()
                ->where('user_id', $user->id)
                ->where('period', '202607')
                ->count()
        );
    }
}
