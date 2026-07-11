<?php

namespace Tests\Feature\Api\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Marketplace\Entities\Opportunity;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;
use Tests\Support\Api\AssertsApiJson;
use Tests\TestCase;

class AdminArchiveTest extends TestCase
{
    use AssertsApiJson, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('permission:insert');
    }

    private function admin(): User
    {
        $admin = User::create(['name' => 'A', 'email' => 'ar'.uniqid().'@rec.test', 'password' => 'secret123']);
        $admin->assignRole(Role::where(['name' => 'super_admin', 'guard_name' => 'admin'])->first());
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function opp(): Opportunity
    {
        $u = User::create(['name' => 'E', 'email' => 'e'.uniqid().'@rec.test', 'password' => 'secret123']);

        return Opportunity::create(['user_id' => $u->id, 'title' => 'فرصة مؤرشفة', 'company' => 'x', 'location' => '-', 'salary' => '-', 'category' => 'tech', 'skills' => []]);
    }

    public function test_deleting_a_resource_archives_it_and_appears_in_archive(): void
    {
        $this->admin();
        $opp = $this->opp();

        // الحذف من شاشة المورد صار حذفًا ناعمًا (أرشفة)
        $this->deleteJson("/api/admin/opportunities/{$opp->id}")->assertOk();
        $this->assertSoftDeleted('opportunities', ['id' => $opp->id]);

        $res = $this->getJson('/api/admin/archive')->assertOk()
            ->assertJsonStructure(['data' => [['type', 'id', 'title', 'deletedAt']], 'meta']);
        $this->assertSame('opportunities', $res->json('data.0.type'));
        $this->assertSame($opp->id, $res->json('data.0.id'));

        $this->getJson('/api/admin/archive/stats')->assertOk()->assertJsonPath('data.total', 1);
    }

    public function test_admin_can_restore_archived_item(): void
    {
        $this->admin();
        $opp = $this->opp();
        $opp->delete();

        $this->postJson('/api/admin/archive/restore', ['type' => 'opportunities', 'id' => $opp->id])
            ->assertOk()->assertJsonPath('data.restored', true);

        $this->assertDatabaseHas('opportunities', ['id' => $opp->id, 'deleted_at' => null]);
    }

    public function test_admin_can_purge_permanently(): void
    {
        $this->admin();
        $opp = $this->opp();
        $opp->delete();

        $this->postJson('/api/admin/archive/purge', ['type' => 'opportunities', 'id' => $opp->id])
            ->assertOk()->assertJsonPath('data.purged', true);

        $this->assertDatabaseMissing('opportunities', ['id' => $opp->id]);
    }

    public function test_type_filter_and_unknown_type(): void
    {
        $this->admin();
        $opp = $this->opp();
        $opp->delete();

        $this->getJson('/api/admin/archive?type=surveys')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson('/api/admin/archive/restore', ['type' => 'bogus', 'id' => 1])->assertStatus(422);
    }

    public function test_non_admin_cannot_access_archive(): void
    {
        Sanctum::actingAs(User::create(['name' => 'U', 'email' => 'ar'.uniqid().'@rec.test', 'password' => 'secret123']));
        $this->getJson('/api/admin/archive')->assertStatus(403);
    }
}
