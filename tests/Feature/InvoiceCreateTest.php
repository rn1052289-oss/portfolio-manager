<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class InvoiceCreateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーは請求書作成画面を表示できません。
     */
    public function test_guest_is_redirected_from_invoice_create_page(): void
    {
        $response = $this->get(
            route('invoices.create')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    /**
     * ログインユーザーは請求書作成画面を表示できます。
     */
    public function test_authenticated_user_can_view_invoice_create_page(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create([
                'name' => '株式会社テスト顧客',
            ]);

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create([
                'title' => 'テスト案件',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.create'));

        $response
            ->assertOk()
            ->assertViewIs('invoices.create')
            ->assertViewHasAll([
                'projects',
                'customers',
            ])
            ->assertSeeText('新しい請求書を作成')
            ->assertSeeText($project->title)
            ->assertSeeText($customer->name);
    }

    /**
     * 作成画面にはログインユーザー本人の案件と顧客だけが表示されます。
     */
    public function test_invoice_create_page_only_displays_owned_projects_and_customers(): void
    {
        $user = User::factory()->create();

        $ownCustomer = Customer::factory()
            ->for($user)
            ->create([
                'name' => '本人所有の顧客',
            ]);

        $ownProject = Project::factory()
            ->for($user)
            ->for($ownCustomer)
            ->create([
                'title' => '本人所有の案件',
            ]);

        $otherUser = User::factory()->create();

        $otherCustomer = Customer::factory()
            ->for($otherUser)
            ->create([
                'name' => '他ユーザー所有の顧客',
            ]);

        $otherProject = Project::factory()
            ->for($otherUser)
            ->for($otherCustomer)
            ->create([
                'title' => '他ユーザー所有の案件',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.create'));

        $response
            ->assertOk()
            ->assertViewHas(
                'projects',
                function (Collection $projects) use ($ownProject, $otherProject): bool {
                    return $projects->contains(
                        'id',
                        $ownProject->id
                    ) && ! $projects->contains(
                        'id',
                        $otherProject->id
                    );
                }
            )
            ->assertViewHas(
                'customers',
                function (Collection $customers) use ($ownCustomer, $otherCustomer): bool {
                    return $customers->contains(
                        'id',
                        $ownCustomer->id
                    ) && ! $customers->contains(
                        'id',
                        $otherCustomer->id
                    );
                }
            )
            ->assertSeeText($ownProject->title)
            ->assertSeeText($ownCustomer->name)
            ->assertDontSeeText($otherProject->title)
            ->assertDontSeeText($otherCustomer->name);
    }

    /**
     * 案件に紐づく顧客IDが選択肢のdata属性へ設定されます。
     */
    public function test_project_option_contains_linked_customer_id(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.create'));

        $response
            ->assertOk()
            ->assertSee(
                'data-customer-id="'.
                $customer->id.
                '"',
                false
            )
            ->assertSeeText($project->title);
    }

    /**
     * 顧客はあるが案件がない場合、案件登録への導線が表示されます。
     */
    public function test_create_page_prompts_user_to_register_project_when_projects_are_empty(): void
    {
        $user = User::factory()->create();

        Customer::factory()
            ->for($user)
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.create'));

        $response
            ->assertOk()
            ->assertSeeText(
                '請求書を作成するための準備が必要です'
            )
            ->assertSeeText('案件を登録する')
            ->assertDontSeeText('顧客を登録する')
            ->assertSee(
                route('projects.create'),
                false
            );
    }

    /**
     * 案件はあるが顧客がない場合、顧客登録への導線が表示されます。
     */
    public function test_create_page_prompts_user_to_register_customer_when_customers_are_empty(): void
    {
        $user = User::factory()->create();

        Project::factory()
            ->for($user)
            ->create([
                'customer_id' => null,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.create'));

        $response
            ->assertOk()
            ->assertSeeText(
                '請求書を作成するための準備が必要です'
            )
            ->assertSeeText('顧客を登録する')
            ->assertDontSeeText('案件を登録する')
            ->assertSee(
                route('customers.create'),
                false
            );
    }
}
