<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_profile_page(): void
    {
        $response = $this->get(route('profile.show'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_own_profile_page(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'profile@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('profile.show'));

        $response
            ->assertOk()
            ->assertViewIs('profile.show')
            ->assertViewHas(
                'user',
                fn(User $viewUser): bool =>
                $viewUser->is($user)
            )
            ->assertSee('テストユーザー')
            ->assertSee('profile@example.com');
    }

    public function test_profile_statistics_only_include_authenticated_users_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $completedProject = Project::factory()->for($user)->create([
            'title' => '本人の完了案件',
            'status' => 'completed',
        ]);

        $inProgressProject = Project::factory()->for($user)->create([
            'title' => '本人の進行中案件',
            'status' => 'in_progress',
        ]);

        Project::factory()->for($user)->create([
            'title' => '本人の未着手案件',
            'status' => 'not_started',
        ]);

        $otherProject = Project::factory()->for($otherUser)->create([
            'title' => '他ユーザーの案件',
            'status' => 'completed',
        ]);

        Task::factory()
            ->forProject($inProgressProject)
            ->inProgress()
            ->create([
                'title' => '本人の未完了タスク',
            ]);

        Task::factory()
            ->forProject($completedProject)
            ->completed()
            ->create([
                'title' => '本人の完了タスク',
            ]);

        Task::factory()
            ->forProject($otherProject)
            ->notStarted()
            ->create([
                'title' => '他ユーザーの未完了タスク',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('profile.show'));

        $response
            ->assertOk()
            ->assertViewHas('totalProjectsCount', 3)
            ->assertViewHas('completedProjectsCount', 1)
            ->assertViewHas('inProgressProjectsCount', 1)
            ->assertViewHas('incompleteTasksCount', 1)
            ->assertViewHas(
                'recentProjects',
                fn($projects): bool =>
                $projects->count() === 3
                && $projects->contains(
                    'title',
                    '本人の完了案件'
                )
                && $projects->contains(
                    'title',
                    '本人の進行中案件'
                )
                && $projects->contains(
                    'title',
                    '本人の未着手案件'
                )
                && !$projects->contains(
                    'title',
                    '他ユーザーの案件'
                )
            )
            ->assertSee('本人の完了案件')
            ->assertSee('本人の進行中案件')
            ->assertDontSee('他ユーザーの案件')
            ->assertDontSee('他ユーザーの未完了タスク');
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create([
            'name' => '変更前ユーザー',
            'email' => 'before@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('user-profile-information.update'), [
                'name' => '変更後ユーザー',
                'email' => 'after@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'status',
                'profile-information-updated'
            );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => '変更後ユーザー',
            'email' => 'after@example.com',
        ]);
    }

    public function test_user_cannot_update_email_to_an_existing_email_address(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('profile.show'))
            ->put(route('user-profile-information.update'), [
                'name' => $user->name,
                'email' => $otherUser->email,
            ]);

        $response
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasErrors(
                ['email'],
                null,
                'updateProfileInformation'
            );

        $this->assertSame(
            'owner@example.com',
            $user->fresh()->email
        );
    }

    public function test_password_cannot_be_updated_with_incorrect_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.show'))
            ->put(route('user-password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasErrors(
                ['current_password'],
                null,
                'updatePassword'
            );

        $this->assertTrue(
            Hash::check('password', $user->fresh()->password)
        );
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'password-updated');

        $this->assertTrue(
            Hash::check(
                'NewPassword123!',
                $user->fresh()->password
            )
        );
    }
}