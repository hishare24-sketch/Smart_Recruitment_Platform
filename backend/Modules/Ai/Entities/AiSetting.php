<?php

namespace Modules\Ai\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * إعدادات الذكاء — صفّ مفرد. مصدر الحقيقة لتهيئة المساعد عبر المنصّة.
 * الحصص الافتراضيّة لكلّ مستوًى والمستويات نفسها ثابتة في الواجهة (assistantLevels)،
 * وهنا تُخزَّن تجاوزات الأدمن فقط.
 */
class AiSetting extends Model
{
    protected $fillable = [
        'enabled', 'provider', 'model', 'api_key', 'endpoint', 'temperature', 'max_tokens',
        'language', 'system_prompt', 'assistant_level', 'allow_user_level_override',
        'doc_max_reads', 'level_tokens', 'plan_quotas',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'temperature' => 'float',
        'max_tokens' => 'integer',
        'assistant_level' => 'integer',
        'allow_user_level_override' => 'boolean',
        'doc_max_reads' => 'integer',
        'level_tokens' => 'array',
        'plan_quotas' => 'array',
    ];

    /** الصفّ المفرد — يُنشأ بالافتراضيّات عند أوّل وصول. */
    public static function current(): self
    {
        $s = static::query()->first();
        if ($s === null) {
            $s = static::create([]);
            $s->refresh(); // حمّل افتراضيّات قاعدة البيانات في الذاكرة (provider='simulation'…)
        }

        return $s;
    }

    /** حصّة باقة بعينها (من التخزين أو صفر افتراضيّ). */
    public function quotaFor(string $planKey): array
    {
        $all = $this->plan_quotas ?? [];

        return array_merge(
            ['maxTokensPerRequest' => 2048, 'dailyTokens' => 0, 'weeklyTokens' => 0, 'monthlyTokens' => 0],
            $all[$planKey] ?? []
        );
    }
}
