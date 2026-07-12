<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Modules\Ai\Database\Seeders\AiSeeder;
use Modules\Ai\Entities\AiSetting;
use Modules\Profile\Entities\Profile;
use Modules\User\Entities\User;
use Tests\Support\Api\AssertsApiJson;
use Tests\TestCase;

class WhyMatchTest extends TestCase
{
    use AssertsApiJson, RefreshDatabase;

    private function seeker(array $skills = ['Vue', 'TypeScript']): User
    {
        $u = User::create(['name' => 'باحث', 'email' => 'w'.uniqid().'@rec.test', 'password' => 'secret123', 'role' => 'seeker']);
        Profile::create(['user_id' => $u->id, 'skills' => $skills, 'experiences' => [['title' => 'مطوّر']], 'prefs' => ['interestedSectors' => ['tech']]]);
        Sanctum::actingAs($u);

        return $u;
    }

    public function test_why_match_returns_heuristic_explanation_without_key(): void
    {
        $this->seed(AiSeeder::class);
        $this->seeker();

        $res = $this->postJson('/api/v1/opportunities/why-match', [
            'title' => 'مطوّر واجهات Vue',
            'category' => 'tech',
            'skills' => ['Vue', 'TypeScript', 'CSS'],
        ])->assertOk()
            ->assertJsonStructure(['data' => ['live', 'score', 'verdict', 'reasons', 'redFlags', 'summary', 'matchedSkills']])
            ->assertJsonPath('data.live', false);

        $this->assertNotEmpty($res->json('data.reasons'));
        $this->assertContains('vue', $res->json('data.matchedSkills'));
    }

    public function test_why_match_uses_live_provider_when_configured(): void
    {
        $this->seed(AiSeeder::class);
        AiSetting::current()->update(['provider' => 'claude', 'api_key' => 'sk-test', 'model' => 'claude-opus-4-8']);
        $this->seeker();

        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => '{"score":84,"verdict":"ملاءمة قويّة","reasons":["مهاراتك تغطّي المطلوب"],"redFlags":[],"summary":"فرصة مناسبة لك."}']],
            'stop_reason' => 'end_turn',
            'usage' => ['input_tokens' => 60, 'output_tokens' => 25],
        ], 200)]);

        $this->postJson('/api/v1/opportunities/why-match', ['title' => 'مطوّر Vue', 'category' => 'tech', 'skills' => ['Vue']])
            ->assertOk()
            ->assertJsonPath('data.live', true)
            ->assertJsonPath('data.score', 84)
            ->assertJsonPath('data.verdict', 'ملاءمة قويّة');
    }

    public function test_why_match_requires_authentication(): void
    {
        $this->postJson('/api/v1/opportunities/why-match', ['title' => 'x'])->assertStatus(401);
    }

    public function test_why_match_validates_title(): void
    {
        $this->seed(AiSeeder::class);
        $this->seeker();
        $this->postJson('/api/v1/opportunities/why-match', ['skills' => ['Vue']])->assertStatus(422);
    }
}
