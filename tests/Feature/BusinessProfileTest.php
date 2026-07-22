<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ユーザーのリレーションから事業者プロフィールを作成できます。
     */
    public function test_user_can_create_business_profile_through_relationship(): void
    {
        $user = User::factory()->create();

        $profile = $user->businessProfile()->create([
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
        ]);

        $this->assertSame($user->id, $profile->user_id);
        $this->assertSame(
            'ClientWork Studio',
            $user->fresh()->businessProfile?->business_name
        );
    }

    /**
     * 事業者プロフィールから所有ユーザーを取得できます。
     */
    public function test_business_profile_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $profile = $user->businessProfile()->create([
            'business_name' => 'ClientWork Studio',
        ]);

        $this->assertTrue(
            $profile->user->is($user)
        );
    }

    /**
     * 銀行口座番号はDBへ平文保存されません。
     */
    public function test_bank_account_number_is_encrypted_in_database(): void
    {
        $user = User::factory()->create();

        $profile = $user->businessProfile()->create([
            'business_name' => 'ClientWork Studio',
            'bank_account_number' => '1234567',
        ]);

        $storedValue = DB::table('business_profiles')
            ->where('id', $profile->id)
            ->value('bank_account_number');

        $this->assertNotNull($storedValue);
        $this->assertNotSame('1234567', $storedValue);

        /*
         * Model経由で取得すると、
         * encrypted castによって復号された値を取得できます。
         */
        $this->assertSame(
            '1234567',
            $profile->fresh()->bank_account_number
        );
    }

    /**
     * 1ユーザーに複数の事業者プロフィールは登録できません。
     */
    public function test_user_cannot_have_multiple_business_profiles(): void
    {
        $user = User::factory()->create();

        $user->businessProfile()->create([
            'business_name' => '最初の事業者情報',
        ]);

        $this->expectException(QueryException::class);

        /*
         * business_profiles.user_idのunique制約により、
         * 同じユーザーの2件目はDBで拒否されます。
         */
        BusinessProfile::query()->forceCreate([
            'user_id' => $user->id,
            'business_name' => '2件目の事業者情報',
        ]);
    }

    /**
     * ユーザー削除時に事業者プロフィールも削除されます。
     */
    public function test_business_profile_is_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()->create();

        $profile = $user->businessProfile()->create([
            'business_name' => 'ClientWork Studio',
        ]);

        $profileId = $profile->id;

        $user->delete();

        $this->assertDatabaseMissing('business_profiles', [
            'id' => $profileId,
        ]);
    }
}
