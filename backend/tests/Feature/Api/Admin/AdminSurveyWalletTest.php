<?php

namespace Tests\Feature\Api\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Account\Entities\Wallet;
use Modules\Survey\Entities\Survey;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;
use Tests\Support\Api\AssertsApiJson;
use Tests\TestCase;

class AdminSurveyWalletTest extends TestCase
{
    use AssertsApiJson, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('permission:insert');
    }

    private function admin(): User
    {
        $admin = User::create(['name' => 'A', 'email' => 'sw'.uniqid().'@rec.test', 'password' => 'secret123']);
        $admin->assignRole(Role::where(['name' => 'super_admin', 'guard_name' => 'admin'])->first());
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_admin_can_close_and_delete_survey(): void
    {
        $owner = $this->admin();
        $survey = Survey::create(['user_id' => $owner->id, 'title' => 'S', 'state' => 'draft', 'points_pool' => 10]);

        $this->getJson('/api/admin/surveys')->assertOk()->assertJsonStructure(['data', 'meta']);

        $this->postJson("/api/admin/surveys/{$survey->id}/close")
            ->assertOk()
            ->assertJsonPath('data.state', 'closed');

        $this->deleteJson("/api/admin/surveys/{$survey->id}")->assertOk();
        $this->assertSoftDeleted('surveys', ['id' => $survey->id]);
    }

    public function test_admin_can_adjust_wallet(): void
    {
        $owner = $this->admin();
        $wallet = Wallet::create(['user_id' => $owner->id, 'balance' => 100, 'transactions' => []]);

        $this->getJson('/api/admin/wallets')->assertOk()->assertJsonStructure(['data', 'meta']);

        $this->postJson("/api/admin/wallets/{$wallet->id}/adjust", ['amount' => 250, 'note' => 'bonus'])
            ->assertOk()
            ->assertJsonPath('data.balance', 350);

        // منع الرصيد السالب (405 نمط الفريق)
        $this->postJson("/api/admin/wallets/{$wallet->id}/adjust", ['amount' => -9999])->assertStatus(405);
    }

    public function test_non_admin_cannot_list_wallets(): void
    {
        Sanctum::actingAs(User::create(['name' => 'U', 'email' => 'w'.uniqid().'@rec.test', 'password' => 'secret123']));
        $this->getJson('/api/admin/wallets')->assertStatus(403);
    }
}
