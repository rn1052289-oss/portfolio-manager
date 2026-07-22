<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BusinessProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーは事業者プロフィールを更新できません。
     */
    public function test_guest_cannot_update_business_profile(): void
    {
        $response = $this->put(
            route('profile.business-profile.update'),
            $this->validPayload()
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('business_profiles', 0);
    }

    /**
     * ログインユーザーは事業者プロフィールを初回保存できます。
     */
    public function test_authenticated_user_can_create_business_profile(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(
                route('profile.business-profile.update'),
                $this->validPayload([
                    /*
                     * FormRequestによる正規化も同時に確認します。
                     */
                    'invoice_registration_number' => ' t 1234-5678-9012-3 ',
                    'bank_account_number' => ' 123-4567 ',
                ])
            );

        $response
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'status',
                'business-profile-updated'
            );

        $profile = $user
            ->fresh()
            ->businessProfile;

        $this->assertNotNull($profile);
        $this->assertSame(
            $user->id,
            $profile->user_id
        );
        $this->assertSame(
            'ClientWork Studio',
            $profile->business_name
        );
        $this->assertSame(
            'T1234567890123',
            $profile->invoice_registration_number
        );
        $this->assertSame(
            '1234567',
            $profile->bank_account_number
        );
    }

    /**
     * 登録済みの場合は同じプロフィールを更新します。
     */
    public function test_existing_business_profile_is_updated_without_creating_duplicate(): void
    {
        $user = User::factory()->create();

        $profile = $user
            ->businessProfile()
            ->create([
                'business_name' => '変更前の屋号',
                'representative_name' => '変更前 太郎',
                'email' => 'before@example.com',
                'phone' => '03-0000-0000',
                'postal_code' => '100-0001',
                'address' => '東京都千代田区変更前1-1',
                'invoice_registration_number' => 'T1111111111111',
                'bank_name' => '変更前銀行',
                'bank_branch_name' => '変更前支店',
                'bank_account_type' => '普通',
                'bank_account_number' => '1111111',
                'bank_account_holder' => 'ヘンコウマエ タロウ',
            ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('profile.business-profile.update'),
                $this->validPayload([
                    'business_name' => '変更後の屋号',
                    'email' => 'after@example.com',
                    'bank_name' => '変更後銀行',
                    'bank_account_number' => '7654321',
                ])
            );

        $response
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasNoErrors();

        $updatedProfile = $user
            ->fresh()
            ->businessProfile;

        $this->assertNotNull($updatedProfile);
        $this->assertSame(
            $profile->id,
            $updatedProfile->id
        );
        $this->assertSame(
            '変更後の屋号',
            $updatedProfile->business_name
        );
        $this->assertSame(
            'after@example.com',
            $updatedProfile->email
        );
        $this->assertSame(
            '変更後銀行',
            $updatedProfile->bank_name
        );
        $this->assertSame(
            '7654321',
            $updatedProfile->bank_account_number
        );

        $this->assertDatabaseCount(
            'business_profiles',
            1
        );
    }

    /**
     * 更新処理は他ユーザーの事業者プロフィールへ影響しません。
     */
    public function test_user_cannot_update_another_users_business_profile(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherProfile = $otherUser
            ->businessProfile()
            ->create([
                'business_name' => '他ユーザーの屋号',
                'representative_name' => '他ユーザー',
                'email' => 'other@example.com',
                'phone' => '03-9999-9999',
                'postal_code' => '150-0001',
                'address' => '東京都渋谷区他人1-1',
                'invoice_registration_number' => 'T9999999999999',
                'bank_name' => '他ユーザー銀行',
                'bank_branch_name' => '他ユーザー支店',
                'bank_account_type' => '普通',
                'bank_account_number' => '9999999',
                'bank_account_holder' => 'タユーザー',
            ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('profile.business-profile.update'),
                $this->validPayload([
                    'business_name' => '本人の屋号',
                ])
            );

        $response->assertSessionHasNoErrors();

        $this->assertSame(
            '他ユーザーの屋号',
            $otherProfile->fresh()->business_name
        );

        $this->assertSame(
            '本人の屋号',
            $user->fresh()->businessProfile?->business_name
        );

        $this->assertDatabaseCount(
            'business_profiles',
            2
        );
    }

    /**
     * 不正な入力は専用のエラーバッグへ返されます。
     */
    public function test_invalid_business_profile_data_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.show'))
            ->put(
                route('profile.business-profile.update'),
                [
                    'business_name' => '',
                    'representative_name' => '',
                    'email' => 'invalid-email',
                    'phone' => '',
                    'postal_code' => '12-345',
                    'address' => '',
                    'invoice_registration_number' => 'T123',
                    'bank_name' => '',
                    'bank_branch_name' => '',
                    'bank_account_type' => '定期',
                    'bank_account_number' => 'ABC123',
                    'bank_account_holder' => '',
                ]
            );

        $response
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasErrors(
                [
                    'business_name',
                    'email',
                    'postal_code',
                    'address',
                    'invoice_registration_number',
                    'bank_name',
                    'bank_branch_name',
                    'bank_account_type',
                    'bank_account_number',
                    'bank_account_holder',
                ],
                null,
                'updateBusinessProfile'
            );

        $this->assertDatabaseCount(
            'business_profiles',
            0
        );
    }

    /**
     * 正常な事業者プロフィール入力を返します。
     *
     * @param  array<string, string|null>  $overrides
     * @return array<string, string|null>
     */
    private function validPayload(
        array $overrides = []
    ): array {
        return array_merge([
            'business_name' => 'ClientWork Studio',
            'representative_name' => '山田 太郎',
            'email' => 'billing@example.com',
            'phone' => '03-1234-5678',
            'postal_code' => '100-0001',
            'address' => '東京都千代田区千代田1-1',
            'invoice_registration_number' => 'T1234567890123',
            'bank_name' => 'テスト銀行',
            'bank_branch_name' => '中央支店',
            'bank_account_type' => '普通',
            'bank_account_number' => '1234567',
            'bank_account_holder' => 'ヤマダ タロウ',
        ], $overrides);
    }
}
