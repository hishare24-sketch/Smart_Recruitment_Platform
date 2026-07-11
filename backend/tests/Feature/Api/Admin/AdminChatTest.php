<?php

namespace Tests\Feature\Api\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Ai\Database\Seeders\AiSeeder;
use Modules\Ai\Entities\AiCapability;
use Modules\Ai\Entities\AiSetting;
use Modules\Chat\Database\Seeders\ChatSeeder;
use Modules\Chat\Entities\ChatSetting;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;
use Tests\Support\Api\AssertsApiJson;
use Tests\TestCase;

class AdminChatTest extends TestCase
{
    use AssertsApiJson, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('permission:insert');
    }

    private function admin(): User
    {
        $admin = User::create(['name' => 'A', 'email' => 'ch'.uniqid().'@rec.test', 'password' => 'secret123']);
        $admin->assignRole(Role::where(['name' => 'super_admin', 'guard_name' => 'admin'])->first());
        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_config_returns_settings_and_ai_linkage(): void
    {
        $this->admin();
        $this->seed(AiSeeder::class);
        $this->seed(ChatSeeder::class);

        $this->getJson('/api/admin/chat')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'settings' => ['directMessagesEnabled', 'assistantEnabled', 'moderationEnabled', 'retentionDays'],
                'aiLinkage' => ['aiEnabled', 'chatCapabilityEnabled', 'assistantEnabled', 'effectiveEnabled', 'provider', 'model', 'assistantLevel'],
            ]])
            ->assertJsonPath('data.aiLinkage.effectiveEnabled', true);
    }

    public function test_ai_linkage_reflects_disabling_chat_capability(): void
    {
        $this->admin();
        $this->seed(AiSeeder::class);
        $this->seed(ChatSeeder::class);

        // إيقاف قسم مساعد المحادثة في موديول الذكاء ينعكس على الربط.
        AiCapability::where('key', 'chat_assistant')->update(['enabled' => false]);

        $this->getJson('/api/admin/chat')
            ->assertOk()
            ->assertJsonPath('data.aiLinkage.chatCapabilityEnabled', false)
            ->assertJsonPath('data.aiLinkage.effectiveEnabled', false);
    }

    public function test_admin_can_update_chat_settings(): void
    {
        $this->admin();
        $this->seed(ChatSeeder::class);

        $this->putJson('/api/admin/chat/settings', ['direct_messages_enabled' => false, 'moderation_enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.directMessagesEnabled', false)
            ->assertJsonPath('data.moderationEnabled', true);

        $this->assertDatabaseHas('chat_settings', ['id' => 1, 'direct_messages_enabled' => false, 'moderation_enabled' => true]);
    }

    public function test_threads_list_groups_messages_by_pair(): void
    {
        $this->admin();
        $this->seed(ChatSeeder::class);

        $res = $this->getJson('/api/admin/chat/threads')->assertOk()
            ->assertJsonStructure(['data' => [['key', 'participants', 'messagesCount', 'lastBody', 'lastMessageAt']], 'meta']);

        // البذر ينشئ 3 محادثات.
        $this->assertCount(3, $res->json('data'));
    }

    public function test_thread_returns_full_conversation(): void
    {
        $this->admin();
        $this->seed(ChatSeeder::class);

        $key = $this->getJson('/api/admin/chat/threads')->json('data.0.key');

        $this->getJson('/api/admin/chat/threads/'.rawurlencode($key))
            ->assertOk()
            ->assertJsonStructure(['data' => ['key', 'participants', 'messages' => [['id', 'senderName', 'body', 'at']]]]);
    }

    public function test_stats_shape(): void
    {
        $this->admin();
        $this->seed(ChatSeeder::class);

        $this->getJson('/api/admin/chat/stats')
            ->assertOk()
            ->assertJsonStructure(['data' => ['threads', 'messages', 'activeToday', 'participants', 'series', 'topSenders']])
            ->assertJsonPath('data.threads', 3);
    }

    public function test_assistant_preview_replies_when_enabled_and_respects_level(): void
    {
        $this->admin();
        $this->seed(AiSeeder::class);
        $this->seed(ChatSeeder::class);

        // المستوى 3 (مستشار) — الردّ أطول ويحقن المعرفة.
        AiSetting::query()->where('id', 1)->update(['assistant_level' => 3]);

        $this->postJson('/api/admin/chat/assistant-preview', ['prompt' => 'كيف أرفع فرص توظيفي؟'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['reply', 'level', 'tokensCap', 'provider', 'model', 'simulated', 'usedKnowledge']])
            ->assertJsonPath('data.level', 3)
            ->assertJsonPath('data.simulated', true);
    }

    public function test_assistant_preview_blocked_when_ai_master_off(): void
    {
        $this->admin();
        $this->seed(AiSeeder::class);
        $this->seed(ChatSeeder::class);

        AiSetting::query()->where('id', 1)->update(['enabled' => false]);

        $this->postJson('/api/admin/chat/assistant-preview', ['prompt' => 'مرحبا'])
            ->assertStatus(405);
    }

    public function test_disabling_direct_messages_blocks_sending(): void
    {
        // مسار السلوك الحيّ: إيقاف الرسائل من الحوكمة يمنع الإرسال في /v1.
        $this->admin();
        $this->seed(ChatSeeder::class);
        ChatSetting::query()->where('id', 1)->update(['direct_messages_enabled' => false]);

        $sender = User::create(['name' => 'S', 'email' => 'snd'.uniqid().'@rec.test', 'password' => 'secret123']);
        Sanctum::actingAs($sender);

        $this->postJson('/api/v1/direct-messages', [
            'recipientId' => 'peer-1', 'recipientName' => 'Peer', 'body' => 'hi',
        ])->assertStatus(403);
    }

    public function test_direct_messages_work_when_unseeded(): void
    {
        // بلا بذر chat_settings — الافتراضيّ مُفعّل فلا ينكسر السلوك القديم.
        $sender = User::create(['name' => 'S', 'email' => 'snd'.uniqid().'@rec.test', 'password' => 'secret123']);
        Sanctum::actingAs($sender);

        $this->postJson('/api/v1/direct-messages', [
            'recipientId' => 'peer-1', 'recipientName' => 'Peer', 'body' => 'hi',
        ])->assertStatus(201);
    }

    public function test_non_admin_cannot_access_chat_oversight(): void
    {
        Sanctum::actingAs(User::create(['name' => 'U', 'email' => 'ch'.uniqid().'@rec.test', 'password' => 'secret123']));
        $this->getJson('/api/admin/chat')->assertStatus(403);
        $this->getJson('/api/admin/chat/threads')->assertStatus(403);
    }
}
