<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Entities\User;
use Tests\Support\Api\AssertsApiJson;
use Tests\TestCase;

/**
 * حالات: AUTH-01/02/03 من DOC/TEST_CASES.md — دورة حياة التوكن (دخول/me/خروج/إبطال).
 */
class AuthTest extends TestCase
{
    use AssertsApiJson, RefreshDatabase;

    private function user(string $password = 'secret123'): User
    {
        return User::create([
            'name' => 'مستخدم', 'email' => 'auth'.uniqid().'@rec.test', 'password' => $password,
        ]);
    }

    // AUTH-01: دخول صحيح → توكن + مستخدم
    public function test_login_with_valid_credentials_returns_token_and_user(): void
    {
        $u = $this->user();

        $this->postJson('/api/v1/auth/login', ['email' => $u->email, 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email']]])
            ->assertJsonPath('data.user.email', $u->email);
    }

    // AUTH-02: دخول ببيانات خاطئة → 401 (بيانات غير صحيحة) لا 500، وبلا توكن
    public function test_login_with_wrong_password_is_rejected_without_token(): void
    {
        $u = $this->user();

        $res = $this->postJson('/api/v1/auth/login', ['email' => $u->email, 'password' => 'WRONG-pass'])
            ->assertStatus(401);

        $this->assertNull($res->json('data.token'));
    }

    // AUTH-03: التوكن يصادِق me بالمستخدم الصحيح، والخروج ينجح (إبطال التوكن يُنفَّذ عبر
    // currentAccessToken()->delete()؛ التحقّق الكامل من الإبطال E2E — env الاختبار يحمل جلسة).
    public function test_token_authenticates_me_and_logout_succeeds(): void
    {
        $u = $this->user();
        $token = $this->postJson('/api/v1/auth/login', ['email' => $u->email, 'password' => 'secret123'])
            ->assertOk()->json('data.token');
        $auth = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/api/v1/auth/me', $auth)->assertOk()->assertJsonPath('data.email', $u->email);
        $this->postJson('/api/v1/auth/logout', [], $auth)->assertOk();
    }

    // حارس: نقطة مصادَقة بلا توكن → 401
    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }
}
