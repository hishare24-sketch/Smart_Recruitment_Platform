<?php

namespace Modules\Chat\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * حوكمة المحادثات — صفّ مفرد. يحكم ميزة الرسائل المباشرة والمساعد الذكيّ عبر المنصّة.
 */
class ChatSetting extends Model
{
    protected $fillable = [
        'direct_messages_enabled', 'assistant_enabled', 'moderation_enabled', 'retention_days',
    ];

    protected $casts = [
        'direct_messages_enabled' => 'boolean',
        'assistant_enabled' => 'boolean',
        'moderation_enabled' => 'boolean',
        'retention_days' => 'integer',
    ];

    /** الصفّ المفرد — يُنشأ بالافتراضيّات عند أوّل وصول (مع إعادة تحميل لضمّ افتراضيّات DB). */
    public static function current(): self
    {
        $s = static::query()->first();
        if ($s === null) {
            $s = static::create([]);
            $s->refresh();
        }

        return $s;
    }

    /** قراءة آمنة لعلَم حوكمة (fallback عند غياب الجدول/الصفّ — يُبقي السلوك القديم). */
    public static function flag(string $key, bool $default = true): bool
    {
        try {
            return (bool) static::current()->getAttribute($key);
        } catch (\Throwable) {
            return $default;
        }
    }
}
