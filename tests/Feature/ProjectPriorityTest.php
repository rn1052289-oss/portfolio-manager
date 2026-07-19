<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPriorityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 案件登録時に選択した優先度を保存できることを確認します。
     */
    public function test_project_priority_can_be_saved_when_creating_a_project(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                route('projects.store'),
                $this->validProjectData([
                    'priority' => Project::PRIORITY_HIGH,
                ])
            );

        $response->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'title' => '優先度保存テスト案件',
            'status' => 'in_progress',
            'priority' => Project::PRIORITY_HIGH,
        ]);
    }

    /**
     * 案件編集時に優先度を変更できることを確認します。
     */
    public function test_project_priority_can_be_updated(): void
    {
        $user = User::factory()->create();

        $project = Project::factory()
            ->for($user)
            ->create([
                'priority' => Project::PRIORITY_MEDIUM,
            ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('projects.update', $project),
                $this->validProjectData([
                    'title' => $project->title,
                    'priority' => Project::PRIORITY_LOW,
                ])
            );

        $response->assertRedirect(
            route('projects.show', $project)
        );

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'user_id' => $user->id,
            'priority' => Project::PRIORITY_LOW,
        ]);
    }

    /**
     * 許可されていない優先度を拒否できることを確認します。
     */
    public function test_invalid_project_priority_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('projects.create'))
            ->post(
                route('projects.store'),
                $this->validProjectData([
                    'priority' => 'urgent',
                ])
            );

        $response
            ->assertRedirect(route('projects.create'))
            ->assertSessionHasErrors('priority');

        $this->assertDatabaseMissing('projects', [
            'user_id' => $user->id,
            'title' => '優先度保存テスト案件',
        ]);
    }

    /**
     * 優先度が未送信の場合に登録を拒否できることを確認します。
     */
    public function test_project_priority_is_required(): void
    {
        $user = User::factory()->create();

        $projectData = $this->validProjectData();

        unset($projectData['priority']);

        $response = $this
            ->actingAs($user)
            ->from(route('projects.create'))
            ->post(
                route('projects.store'),
                $projectData
            );

        $response
            ->assertRedirect(route('projects.create'))
            ->assertSessionHasErrors('priority');

        $this->assertDatabaseCount('projects', 0);
    }

    /**
     * Factoryで作成する案件の初期優先度が中であることを確認します。
     */
    public function test_project_factory_uses_medium_priority_by_default(): void
    {
        $project = Project::factory()->make();

        $this->assertSame(
            Project::PRIORITY_MEDIUM,
            $project->priority
        );

        $this->assertSame(
            'not_started',
            $project->status
        );
    }

    /**
     * 案件一覧がステータスではなく保存済み優先度を表示することを確認します。
     */
    public function test_project_index_displays_saved_priority_instead_of_status_based_priority(): void
    {
        $user = User::factory()->create();

        Project::factory()
            ->for($user)
            ->create([
                /*
                 * 旧実装ではcompletedから「低」が生成されていた。
                 * 保存値をhighにして、ステータスと独立していることを確認する。
                 */
                'status' => 'completed',
                'priority' => Project::PRIORITY_HIGH,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('projects.index'));

        $response
            ->assertOk()
            ->assertSee('priority-badge--high', false)
            ->assertDontSee('priority-badge--low', false);
    }

    /**
     * 他ユーザーが所有する案件の優先度を変更できないことを確認します。
     */
    public function test_user_cannot_update_another_users_project_priority(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $project = Project::factory()
            ->for($owner)
            ->create([
                'priority' => Project::PRIORITY_MEDIUM,
            ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(
                route('projects.update', $project),
                $this->validProjectData([
                    'title' => $project->title,
                    'priority' => Project::PRIORITY_HIGH,
                ])
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'user_id' => $owner->id,
            'priority' => Project::PRIORITY_MEDIUM,
        ]);
    }

    /**
     * 案件登録・更新テストで使用する正常な入力データです。
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validProjectData(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'title' => '優先度保存テスト案件',
                'client_name' => 'テスト株式会社',
                'description' => '案件優先度機能を確認するテスト案件です。',
                'status' => 'in_progress',
                'priority' => Project::PRIORITY_MEDIUM,
                'budget' => 300000,
                'start_date' => '2030-01-10',
                'deadline' => '2030-02-10',
                'category_id' => null,
                'tag_ids' => [],
            ],
            $overrides
        );
    }
}
