<?php

namespace Tests\Feature\Api\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;
use Tests\Support\Api\AssertsApiJson;
use Tests\TestCase;

class AdminSystemHealthTest extends TestCase
{
    use AssertsApiJson, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('permission:insert');
    }

    private function admin(): User
    {
        $admin = User::create(['name' => 'A', 'email' => 'sh'.uniqid().'@rec.test', 'password' => 'secret123']);
        $admin->assignRole(Role::where(['name' => 'super_admin', 'guard_name' => 'admin'])->first());
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_health_returns_services_metrics_and_overall(): void
    {
        $this->admin();

        $res = $this->getJson('/api/admin/system/health')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'services' => [['key', 'label', 'status', 'detail']],
                'metrics' => ['users', 'pendingJobs', 'failedJobs', 'requestsToday', 'errorsToday', 'php', 'laravel', 'env'],
                'recentErrors',
                'series' => [['date', 'value', 'errors']],
                'overall',
            ]]);

        $keys = array_column($res->json('data.services'), 'key');
        foreach (['database', 'cache', 'queue', 'broadcast', 'ai_provider'] as $svc) {
            $this->assertContains($svc, $keys);
        }
        // قاعدة البيانات يجب أن تكون سليمة في بيئة الاختبار
        $db = collect($res->json('data.services'))->firstWhere('key', 'database');
        $this->assertSame('ok', $db['status']);
    }

    public function test_non_admin_cannot_view_health(): void
    {
        Sanctum::actingAs(User::create(['name' => 'U', 'email' => 'sh'.uniqid().'@rec.test', 'password' => 'secret123']));
        $this->getJson('/api/admin/system/health')->assertStatus(403);
    }
}
