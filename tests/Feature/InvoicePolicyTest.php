<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function owner_can_view_invoice(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->create();

        $this->assertTrue(
            $user->can('view', $invoice)
        );
    }

    /** @test */
    public function another_user_cannot_view_invoice(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($owner)
            ->create();

        $this->assertFalse(
            $other->can('view', $invoice)
        );
    }

    /** @test */
    public function owner_can_update_and_delete_draft_invoice(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->create([
                'status' => Invoice::STATUS_DRAFT,
            ]);

        $this->assertTrue(
            $user->can('update', $invoice)
        );

        $this->assertTrue(
            $user->can('delete', $invoice)
        );
    }

    /** @test */
    public function owner_cannot_update_or_delete_sent_invoice(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->sent()
            ->create();

        $this->assertFalse(
            $user->can('update', $invoice)
        );

        $this->assertFalse(
            $user->can('delete', $invoice)
        );
    }

    /** @test */
    public function owner_can_pay_sent_invoice(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->sent()
            ->create();

        $this->assertTrue(
            $user->can('pay', $invoice)
        );
    }

    /** @test */
    public function owner_cannot_cancel_paid_invoice(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->paid()
            ->create();

        $this->assertFalse(
            $user->can('cancel', $invoice)
        );
    }

    /** @test */
    public function another_user_cannot_operate_invoice(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($owner)
            ->sent()
            ->create();

        $this->assertFalse($other->can('update', $invoice));
        $this->assertFalse($other->can('delete', $invoice));
        $this->assertFalse($other->can('send', $invoice));
        $this->assertFalse($other->can('pay', $invoice));
        $this->assertFalse($other->can('cancel', $invoice));
    }
}
