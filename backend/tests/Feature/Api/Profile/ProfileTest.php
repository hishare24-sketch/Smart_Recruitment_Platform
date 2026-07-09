<?php

namespace Tests\Feature\Api\Profile;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\User\Entities\User;
use Tests\Support\Api\AssertsApiJson;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use AssertsApiJson, RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::create([
            'name' => 'Tester',
            'email' => 'tester'.uniqid().'@rec.test',
            'password' => 'secret123',
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_get_profile_returns_empty_structure(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('/api/v1/profile');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.headline', '')
            ->assertJsonPath('data.skills', [])
            ->assertJsonPath('data.proofRequests', []);
    }

    public function test_patch_profile_updates_headline_and_summary(): void
    {
        $this->actingAsUser();

        $response = $this->patchJson('/api/v1/profile', [
            'headline' => 'Frontend Engineer',
            'summary' => 'Ten years',
        ]);

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.headline', 'Frontend Engineer')
            ->assertJsonPath('data.summary', 'Ten years');
    }

    public function test_add_skill_creates_with_self_proof(): void
    {
        $this->actingAsUser();

        $response = $this->postJson('/api/v1/profile/skills', [
            'name' => 'Vue',
            'selfLevel' => 4,
            'category' => 'frontend',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Vue')
            ->assertJsonPath('data.selfLevel', 4)
            ->assertJsonPath('data.proofs.0.type', 'self');
    }

    public function test_add_skill_validates_required_fields(): void
    {
        $this->actingAsUser();

        $this->assertApiValidation(
            $this->postJson('/api/v1/profile/skills', ['selfLevel' => 9]),
            ['name', 'selfLevel'],
        );
    }

    public function test_delete_skill_returns_no_content(): void
    {
        $this->actingAsUser();
        $this->postJson('/api/v1/profile/skills', ['name' => 'Vue', 'selfLevel' => 3]);

        $this->deleteJson('/api/v1/profile/skills/1')->assertStatus(204);

        $this->getJson('/api/v1/profile')->assertJsonPath('data.skills', []);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->assertApiUnauthenticated($this->getJson('/api/v1/profile'));
    }
}
