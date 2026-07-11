<?php

namespace Tests\Feature\Api\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Marketplace\Entities\Application;
use Modules\Marketplace\Entities\Opportunity;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;
use Tests\Support\Api\AssertsApiJson;
use Tests\TestCase;

class AdminPipelineTest extends TestCase
{
    use AssertsApiJson, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('permission:insert');
    }

    private function admin(): User
    {
        $admin = User::create(['name' => 'A', 'email' => 'pl'.uniqid().'@rec.test', 'password' => 'secret123']);
        $admin->assignRole(Role::where(['name' => 'super_admin', 'guard_name' => 'admin'])->first());
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function seedApplications(): Application
    {
        $employer = User::create(['name' => 'Emp', 'email' => 'e'.uniqid().'@rec.test', 'password' => 'secret123']);
        $opp = Opportunity::create(['user_id' => $employer->id, 'title' => 'مطوّر واجهات', 'company' => 'أفق', 'location' => 'عن بُعد', 'salary' => '—', 'category' => 'tech', 'skills' => []]);
        $first = null;
        foreach (['سارة', 'خالد', 'نورة'] as $name) {
            $u = User::create(['name' => $name, 'email' => 's'.uniqid().'@rec.test', 'password' => 'secret123']);
            $app = Application::create(['user_id' => $u->id, 'opportunity_id' => $opp->id]);
            $first ??= $app;
        }

        return $first;
    }

    public function test_board_groups_applications_by_stage(): void
    {
        $this->admin();
        $this->seedApplications();

        $res = $this->getJson('/api/admin/pipeline/board')->assertOk()
            ->assertJsonStructure(['data' => ['stages' => [['key', 'count', 'items']]]]);

        $stages = collect($res->json('data.stages'));
        $this->assertSame(['applied', 'screening', 'interview', 'offer', 'hired', 'rejected'], $stages->pluck('key')->all());
        // كل المتقدّمين في applied ابتداءً
        $this->assertSame(3, $stages->firstWhere('key', 'applied')['count']);
    }

    public function test_move_records_event_and_updates_stage(): void
    {
        $this->admin();
        $app = $this->seedApplications();

        $this->postJson("/api/admin/pipeline/applications/{$app->id}/move", ['to_stage' => 'interview', 'note' => 'مرشّح واعد'])
            ->assertOk()
            ->assertJsonPath('data.stage', 'interview');

        $this->assertDatabaseHas('applications', ['id' => $app->id, 'stage' => 'interview']);
        $this->assertDatabaseHas('application_events', ['application_id' => $app->id, 'from_stage' => 'applied', 'to_stage' => 'interview']);
    }

    public function test_invalid_stage_rejected(): void
    {
        $this->admin();
        $app = $this->seedApplications();
        $this->postJson("/api/admin/pipeline/applications/{$app->id}/move", ['to_stage' => 'bogus'])->assertStatus(422);
    }

    public function test_bulk_move_and_stats(): void
    {
        $this->admin();
        $this->seedApplications();
        $ids = Application::pluck('id')->all();

        $this->postJson('/api/admin/pipeline/bulk-move', ['ids' => $ids, 'to_stage' => 'hired'])
            ->assertOk()->assertJsonPath('data.moved', 3);

        $this->getJson('/api/admin/pipeline/stats')->assertOk()
            ->assertJsonPath('data.hired', 3)
            ->assertJsonPath('data.hireRate', 100);
    }

    public function test_opportunities_selector_has_counts(): void
    {
        $this->admin();
        $this->seedApplications();

        $this->getJson('/api/admin/pipeline/opportunities')->assertOk()
            ->assertJsonStructure(['data' => [['id', 'title', 'applications']]])
            ->assertJsonPath('data.0.applications', 3);
    }

    public function test_non_admin_cannot_access_pipeline(): void
    {
        Sanctum::actingAs(User::create(['name' => 'U', 'email' => 'pl'.uniqid().'@rec.test', 'password' => 'secret123']));
        $this->getJson('/api/admin/pipeline/board')->assertStatus(403);
    }
}
