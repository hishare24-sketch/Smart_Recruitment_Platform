<?php

namespace Tests\Feature\Api\PublicProfile;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\User\Entities\User;
use Tests\Support\Api\AssertsApiJson;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use AssertsApiJson, RefreshDatabase;

    private function owner(string $name = 'Nora Q'): User
    {
        $user = User::create([
            'name' => $name,
            'email' => 'owner'.uniqid().'@rec.test',
            'password' => 'secret123',
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function slugFor(User $user, string $name = 'Nora Q'): string
    {
        Sanctum::actingAs($user);

        return $this->getJson('/api/v1/public-profiles/me')->json('data.slug');
    }

    public function test_get_mine_creates_page_with_derived_slug(): void
    {
        $this->owner();

        $response = $this->getJson('/api/v1/public-profiles/me');

        $this->assertApiSuccess($response);
        $response->assertJsonPath('data.slug', 'nora-q')
            ->assertJsonPath('data.doc.displayName', 'Nora Q');
    }

    public function test_update_mine_persists_doc_and_flattens(): void
    {
        $this->owner();

        $response = $this->patchJson('/api/v1/public-profiles/me', [
            'doc' => ['displayName' => 'Nora Q', 'publicHeadline' => 'UX Lead'],
        ]);

        $response->assertOk()->assertJsonPath('data.publicHeadline', 'UX Lead')
            ->assertJsonPath('data.slug', 'nora-q');
    }

    public function test_public_show_needs_no_auth(): void
    {
        $user = $this->owner();
        $slug = $this->slugFor($user);
        app('auth')->forgetGuards();

        $this->getJson("/api/v1/public-profiles/{$slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $slug);
    }

    public function test_follow_and_rate_update_stats(): void
    {
        $user = $this->owner();
        $slug = $this->slugFor($user);

        $this->postJson("/api/v1/public-profiles/{$slug}/follow", ['following' => true])
            ->assertOk()->assertJsonPath('data.followersCount', 1);

        $this->postJson("/api/v1/public-profiles/{$slug}/rate", ['stars' => 4])
            ->assertOk()->assertJsonPath('data.avgRating', 4)->assertJsonPath('data.ratingCount', 1);
    }

    public function test_rate_validates_stars_range(): void
    {
        $user = $this->owner();
        $slug = $this->slugFor($user);

        $this->assertApiValidation(
            $this->postJson("/api/v1/public-profiles/{$slug}/rate", ['stars' => 9]),
            'stars',
        );
    }

    public function test_testimonial_is_hidden_until_approved(): void
    {
        $user = $this->owner();
        $slug = $this->slugFor($user);

        $this->postJson("/api/v1/public-profiles/{$slug}/testimonials", [
            'author' => 'Sara', 'excerpt' => 'Excellent',
        ])->assertStatus(201)->assertJsonPath('data.visible', false);
    }

    public function test_visitor_proof_request_reaches_owner_private_profile(): void
    {
        $user = $this->owner();
        $slug = $this->slugFor($user);

        $this->postJson("/api/v1/public-profiles/{$slug}/proof-requests", [
            'from' => 'Omar', 'relation' => 'colleague', 'skill' => 'Vue',
        ])->assertStatus(204);

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/profile/proof-requests')
            ->assertOk()
            ->assertJsonPath('data.0.from', 'Omar')
            ->assertJsonPath('data.0.skill', 'Vue');
    }
}
