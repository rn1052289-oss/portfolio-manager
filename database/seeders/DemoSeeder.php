<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * ポートフォリオ撮影用のデモデータを作成します。
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Demo User
        |--------------------------------------------------------------------------
        */

        $user = User::query()->updateOrCreate(
            [
                'email' => 'demo@clientwork.test',
            ],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 再実行時のクリーンアップ
        |--------------------------------------------------------------------------
        |
        | Demo Userのデータだけを対象にします。
        |
        */

        Invoice::query()
            ->where('user_id', $user->id)
            ->delete();

        Task::query()
            ->where('user_id', $user->id)
            ->delete();

        Project::query()
            ->where('user_id', $user->id)
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $webCategory = Category::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Web制作',
            ],
            [
                'color' => '#2563eb',
                'description' => 'Webサイト・コーポレートサイト制作',
            ]
        );

        $lpCategory = Category::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'LP制作',
            ],
            [
                'color' => '#7c3aed',
                'description' => 'ランディングページ制作',
            ]
        );

        $maintenanceCategory = Category::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => '保守・改善',
            ],
            [
                'color' => '#475569',
                'description' => '既存サイトの保守・改善',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Tags
        |--------------------------------------------------------------------------
        */

        $wordpressTag = Tag::query()->updateOrCreate([
            'user_id' => $user->id,
            'name' => 'WordPress',
        ]);

        $figmaTag = Tag::query()->updateOrCreate([
            'user_id' => $user->id,
            'name' => 'Figma',
        ]);

        $javascriptTag = Tag::query()->updateOrCreate([
            'user_id' => $user->id,
            'name' => 'JavaScript',
        ]);

        $responsiveTag = Tag::query()->updateOrCreate([
            'user_id' => $user->id,
            'name' => 'Responsive',
        ]);

        $seoTag = Tag::query()->updateOrCreate([
            'user_id' => $user->id,
            'name' => 'SEO',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */

        $corporateCustomer = Customer::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => '株式会社サンプル',
            ],
            [
                'contact_name' => '山田 太郎',
                'email' => 'contact@example-corp.test',
                'phone' => '03-1234-5678',
                'postal_code' => '100-0001',
                'address' => '東京都千代田区サンプル1-1-1',
                'memo' => 'コーポレートサイト制作のデモ顧客',
            ]
        );

        $designCustomer = Customer::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'サンプルデザイン合同会社',
            ],
            [
                'contact_name' => '佐藤 花子',
                'email' => 'contact@example-design.test',
                'phone' => '03-2345-6789',
                'postal_code' => '150-0001',
                'address' => '東京都渋谷区サンプル2-2-2',
                'memo' => 'LP制作のデモ顧客',
            ]
        );

        $estateCustomer = Customer::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'サンプル不動産',
            ],
            [
                'contact_name' => '鈴木 一郎',
                'email' => 'contact@example-estate.test',
                'phone' => '03-3456-7890',
                'postal_code' => '160-0001',
                'address' => '東京都新宿区サンプル3-3-3',
                'memo' => 'WordPress制作のデモ顧客',
            ]
        );

        $companyCustomer = Customer::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'サンプル商事',
            ],
            [
                'contact_name' => '高橋 健',
                'email' => 'contact@example-trading.test',
                'phone' => '03-4567-8901',
                'postal_code' => '105-0001',
                'address' => '東京都港区サンプル4-4-4',
                'memo' => 'サイト改善のデモ顧客',
            ]
        );

        $salonCustomer = Customer::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'サンプル美容室',
            ],
            [
                'contact_name' => '田中 美咲',
                'email' => 'contact@example-salon.test',
                'phone' => '03-5678-9012',
                'postal_code' => '152-0001',
                'address' => '東京都目黒区サンプル5-5-5',
                'memo' => '予約サイト制作のデモ顧客',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Projects
        |--------------------------------------------------------------------------
        */

        $reservationProject = Project::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $salonCustomer->id,
            'category_id' => $webCategory->id,
            'title' => '予約サイト制作',
            'client_name' => $salonCustomer->name,
            'description' => '予約導線を改善するWebサイト制作案件です。',
            'status' => 'not_started',
            'priority' => Project::PRIORITY_MEDIUM,
            'budget' => 220_000,
            'start_date' => today()->addDays(10),
            'deadline' => today()
                ->addMonth()
                ->endOfMonth()
                ->subDays(5),
        ]);

        $reservationProject->tags()->sync([
            $responsiveTag->id,
        ]);

        $improvementProject = Project::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $companyCustomer->id,
            'category_id' => $maintenanceCategory->id,
            'title' => '既存サイト改善',
            'client_name' => $companyCustomer->name,
            'description' => '既存サイトのUI改善と表示速度改善を行う案件です。',
            'status' => 'paused',
            'priority' => Project::PRIORITY_LOW,
            'budget' => 90_000,
            'start_date' => today()->subDays(20),
            'deadline' => today()
                ->endOfMonth()
                ->subDays(2),
        ]);

        $improvementProject->tags()->sync([
            $responsiveTag->id,
            $seoTag->id,
        ]);

        $corporateProject = Project::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $corporateCustomer->id,
            'category_id' => $webCategory->id,
            'title' => 'コーポレートサイトリニューアル',
            'client_name' => $corporateCustomer->name,
            'description' => '企業サイトの全面リニューアル案件です。',
            'status' => 'in_progress',
            'priority' => Project::PRIORITY_HIGH,
            'budget' => 180_000,
            'start_date' => today()->subDays(20),
            'deadline' => today()
                ->endOfMonth()
                ->subDays(8),
        ]);

        $corporateProject->tags()->sync([
            $figmaTag->id,
            $responsiveTag->id,
        ]);

        $lpProject = Project::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $designCustomer->id,
            'category_id' => $lpCategory->id,
            'title' => '新サービスLP制作',
            'client_name' => $designCustomer->name,
            'description' => '新サービス公開に合わせたLP制作案件です。',
            'status' => 'in_progress',
            'priority' => Project::PRIORITY_HIGH,
            'budget' => 140_000,
            'start_date' => today()->subDays(14),
            'deadline' => today()
                ->endOfMonth()
                ->subDays(5),
        ]);

        $lpProject->tags()->sync([
            $figmaTag->id,
            $javascriptTag->id,
        ]);

        $wordpressProject = Project::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $estateCustomer->id,
            'category_id' => $webCategory->id,
            'title' => 'WordPressサイト制作',
            'client_name' => $estateCustomer->name,
            'description' => 'WordPressを使用した企業サイト制作案件です。',
            'status' => 'in_progress',
            'priority' => Project::PRIORITY_MEDIUM,
            'budget' => 100_000,
            'start_date' => today()->subDays(10),
            'deadline' => today()
                ->endOfMonth()
                ->subDays(2),
        ]);

        $wordpressProject->tags()->sync([
            $wordpressTag->id,
            $responsiveTag->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Tasks - Corporate
        |--------------------------------------------------------------------------
        */

        Task::factory()
            ->forProject($corporateProject)
            ->completed()
            ->create([
                'title' => '要件整理',
                'priority' => Task::PRIORITY_HIGH,
                'due_date' => today()->subDays(10),
                'sort_order' => 1,
            ]);

        Task::factory()
            ->forProject($corporateProject)
            ->completed()
            ->create([
                'title' => 'トップページデザイン',
                'priority' => Task::PRIORITY_HIGH,
                'due_date' => today()->subDays(3),
                'sort_order' => 2,
            ]);

        Task::factory()
            ->forProject($corporateProject)
            ->inProgress()
            ->highPriority()
            ->dueOn(today())
            ->create([
                'title' => '下層ページ実装',
                'sort_order' => 3,
            ]);

        Task::factory()
            ->forProject($corporateProject)
            ->notStarted()
            ->mediumPriority()
            ->dueOn(today()->addDays(5))
            ->create([
                'title' => 'スマートフォン表示確認',
                'sort_order' => 4,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Tasks - LP
        |--------------------------------------------------------------------------
        */

        Task::factory()
            ->forProject($lpProject)
            ->completed()
            ->create([
                'title' => 'ワイヤーフレーム作成',
                'priority' => Task::PRIORITY_HIGH,
                'due_date' => today()->subDays(4),
                'sort_order' => 1,
            ]);

        Task::factory()
            ->forProject($lpProject)
            ->inProgress()
            ->highPriority()
            ->dueOn(today())
            ->create([
                'title' => 'ファーストビュー実装',
                'sort_order' => 2,
            ]);

        Task::factory()
            ->forProject($lpProject)
            ->notStarted()
            ->mediumPriority()
            ->dueOn(today()->addDays(3))
            ->create([
                'title' => 'お問い合わせ導線調整',
                'sort_order' => 3,
            ]);

        Task::factory()
            ->forProject($lpProject)
            ->notStarted()
            ->lowPriority()
            ->dueOn(today()->addDays(6))
            ->create([
                'title' => '最終表示確認',
                'sort_order' => 4,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Tasks - WordPress
        |--------------------------------------------------------------------------
        */

        Task::factory()
            ->forProject($wordpressProject)
            ->completed()
            ->count(3)
            ->create([
                'priority' => Task::PRIORITY_MEDIUM,
            ]);

        Task::factory()
            ->forProject($wordpressProject)
            ->inProgress()
            ->highPriority()
            ->dueOn(today())
            ->create([
                'title' => '投稿ページ調整',
                'sort_order' => 4,
            ]);

        Task::factory()
            ->forProject($wordpressProject)
            ->notStarted()
            ->mediumPriority()
            ->dueOn(today()->addDays(4))
            ->create([
                'title' => '公開前チェック',
                'sort_order' => 5,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Dashboard Project Order
        |--------------------------------------------------------------------------
        */

        Project::query()
            ->whereKey($reservationProject->id)
            ->update([
                'updated_at' => now()->subDays(2),
            ]);

        Project::query()
            ->whereKey($improvementProject->id)
            ->update([
                'updated_at' => now()->subDay(),
            ]);

        Project::query()
            ->whereKey($wordpressProject->id)
            ->update([
                'updated_at' => now()->subMinutes(2),
            ]);

        Project::query()
            ->whereKey($lpProject->id)
            ->update([
                'updated_at' => now()->subMinute(),
            ]);

        Project::query()
            ->whereKey($corporateProject->id)
            ->update([
                'updated_at' => now(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Invoice Number Sequence
        |--------------------------------------------------------------------------
        |
        | Demo請求書を0001〜0004として作成するため、
        | Demo Userの今月分だけ連番管理を作り直します。
        |
        */

        $period = today()->format('Ym');

        DB::table('invoice_number_sequences')
            ->where('user_id', $user->id)
            ->where('period', $period)
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Invoices
        |--------------------------------------------------------------------------
        |
        | 作成順：
        | draft → overdue → sent → paid
        |
        | InvoiceControllerはlatest('id')なので、
        | 一覧ではpaid → sent → overdue → draftの順に表示されます。
        |
        */

        Invoice::factory()
            ->for($user)
            ->for($reservationProject)
            ->for($salonCustomer)
            ->create([
                'invoice_number' => "INV-{$period}-0001",
                'title' => '予約サイト制作費',
                'description' => '予約サイト制作一式',
                'subtotal' => 220_000,
                'tax_amount' => 22_000,
                'total_amount' => 242_000,
                'billing_name' => $salonCustomer->name,
                'billing_contact_name' => $salonCustomer->contact_name,
                'billing_email' => $salonCustomer->email,
                'billing_phone' => $salonCustomer->phone,
                'billing_postal_code' => $salonCustomer->postal_code,
                'billing_address' => $salonCustomer->address,
                'status' => Invoice::STATUS_DRAFT,
                'issued_at' => today(),
                'transaction_date' => today(),
                'due_date' => today()->addMonth(),
                'sent_at' => null,
                'paid_at' => null,
                'last_sent_to' => null,
                'send_count' => 0,
            ]);

        Invoice::factory()
            ->for($user)
            ->for($wordpressProject)
            ->for($estateCustomer)
            ->overdue()
            ->create([
                'invoice_number' => "INV-{$period}-0002",
                'title' => 'WordPressサイト制作費',
                'description' => 'WordPressサイト制作一式',
                'subtotal' => 100_000,
                'tax_amount' => 10_000,
                'total_amount' => 110_000,
                'billing_name' => $estateCustomer->name,
                'billing_contact_name' => $estateCustomer->contact_name,
                'billing_email' => $estateCustomer->email,
                'billing_phone' => $estateCustomer->phone,
                'billing_postal_code' => $estateCustomer->postal_code,
                'billing_address' => $estateCustomer->address,
                'issued_at' => today()->startOfMonth(),
                'transaction_date' => today()->startOfMonth(),
                'due_date' => today()->subDays(2),
                'sent_at' => now()->subDays(10),
                'last_sent_to' => $estateCustomer->email,
                'send_count' => 1,
            ]);

        Invoice::factory()
            ->for($user)
            ->for($lpProject)
            ->for($designCustomer)
            ->sent()
            ->create([
                'invoice_number' => "INV-{$period}-0003",
                'title' => '新サービスLP制作費',
                'description' => 'LPデザイン・コーディング一式',
                'subtotal' => 140_000,
                'tax_amount' => 14_000,
                'total_amount' => 154_000,
                'billing_name' => $designCustomer->name,
                'billing_contact_name' => $designCustomer->contact_name,
                'billing_email' => $designCustomer->email,
                'billing_phone' => $designCustomer->phone,
                'billing_postal_code' => $designCustomer->postal_code,
                'billing_address' => $designCustomer->address,
                'issued_at' => today()->subDays(4),
                'transaction_date' => today()->subDays(4),
                'due_date' => today()->endOfMonth(),
                'sent_at' => now()->subDays(4),
                'last_sent_to' => $designCustomer->email,
                'send_count' => 1,
            ]);

        Invoice::factory()
            ->for($user)
            ->for($corporateProject)
            ->for($corporateCustomer)
            ->paid()
            ->create([
                'invoice_number' => "INV-{$period}-0004",
                'title' => 'コーポレートサイト制作費',
                'description' => 'コーポレートサイトリニューアル一式',
                'subtotal' => 180_000,
                'tax_amount' => 18_000,
                'total_amount' => 198_000,
                'billing_name' => $corporateCustomer->name,
                'billing_contact_name' => $corporateCustomer->contact_name,
                'billing_email' => $corporateCustomer->email,
                'billing_phone' => $corporateCustomer->phone,
                'billing_postal_code' => $corporateCustomer->postal_code,
                'billing_address' => $corporateCustomer->address,
                'issued_at' => today()->subDays(7),
                'transaction_date' => today()->subDays(7),
                'due_date' => today()->endOfMonth(),
                'sent_at' => now()->subDays(7),
                'paid_at' => now()->subDays(2),
                'last_sent_to' => $corporateCustomer->email,
                'send_count' => 1,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Invoice Number Sequence Sync
        |--------------------------------------------------------------------------
        |
        | 次にUIから請求書を作成した場合、
        | 0005から採番されるようにします。
        |
        */

        DB::table('invoice_number_sequences')->updateOrInsert(
            [
                'user_id' => $user->id,
                'period' => $period,
            ],
            [
                'last_number' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
