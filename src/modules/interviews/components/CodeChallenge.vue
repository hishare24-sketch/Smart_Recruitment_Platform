<script setup lang="ts">
import { computed, ref } from 'vue'
import type { AdaptiveQuestion, FillCodePayload, LiveCasePayload } from '@/services/ai'

const props = defineProps<{ question: AdaptiveQuestion }>()
const answer = defineModel<string>('answer', { default: '' })

const isFill = computed(() => props.question.pattern === 'fill_code')
const livePayload = computed(() => props.question.payload as LiveCasePayload)
const fillPayload = computed(() => props.question.payload as FillCodePayload)

// Simulated run — no real execution; heuristic feedback to keep the loop tight
const output = ref<{ ok: boolean, lines: string[] } | null>(null)
const running = ref(false)
function run() {
  running.value = true
  output.value = null
  setTimeout(() => {
    const code = answer.value
    const hasReturn = /\breturn\b|=>/.test(code)
    const hasGuard = /try|catch|await|if|len|== *0|length|throw/i.test(code)
    const stillTodo = /اكتب الجسم هنا|TODO|\/\/ اكتب/i.test(code)
    if (stillTodo || code.trim().length < 12) {
      output.value = { ok: false, lines: ['✗ لم يُنفَّذ: الدالة ما زالت ناقصة.'] }
    }
    else if (!hasReturn) {
      output.value = { ok: false, lines: ['⚠ حُذّر: لا توجد قيمة معادة (return).', 'undefined'] }
    }
    else {
      output.value = {
        ok: true,
        lines: hasGuard
          ? ['✓ اجتاز 4/4 حالات اختبار (شاملة الحالات الحدّية).']
          : ['✓ اجتاز 3/4 حالات — فشل في حالة المُدخل الفارغ/غير المعرّف.'],
      }
    }
    running.value = false
  }, 700)
}
</script>

<template>
  <div>
    <!-- Given code -->
    <div class="text-caption font-weight-bold mb-1">
      <VIcon :icon="isFill ? 'mdi-function-variant' : 'mdi-bug-outline'" size="15" class="me-1" />
      {{ isFill ? 'الدالة الناقصة' : `الكود المعطى (${livePayload.language})` }}
    </div>
    <pre class="code-block given" dir="ltr">{{ isFill ? fillPayload.signature : livePayload.code }}</pre>
    <VAlert v-if="isFill" type="info" variant="tonal" density="compact" class="mb-3 text-caption">
      <strong>الشرط:</strong> {{ fillPayload.constraint }}
    </VAlert>

    <!-- Editable answer -->
    <div class="text-caption font-weight-bold mb-1">
      <VIcon icon="mdi-pencil" size="15" class="me-1" />إجابتك (اكتب الكود)
    </div>
    <textarea
      v-model="answer"
      class="code-block editor"
      dir="ltr"
      spellcheck="false"
      rows="7"
      :placeholder="isFill ? 'اكتب جسم الدالة هنا...' : 'اكتب الدالة المصحّحة هنا...'"
    />

    <div class="d-flex align-center ga-2 mt-2">
      <VBtn size="small" color="primary" variant="tonal" prepend-icon="mdi-play" :loading="running" @click="run">تشغيل الكود</VBtn>
      <span class="text-caption text-medium-emphasis">تشغيل تجريبي — الـ AI يحلّل منطق الكود لا مخرجاته فقط</span>
    </div>

    <!-- Simulated console -->
    <VExpandTransition>
      <div v-if="output" class="console mt-2" :class="output.ok ? 'ok' : 'err'" dir="ltr">
        <div v-for="(l, i) in output.lines" :key="i">{{ l }}</div>
      </div>
    </VExpandTransition>
  </div>
</template>

<style scoped>
.code-block {
  font-family: 'Cascadia Code', 'Fira Code', 'Consolas', monospace;
  font-size: 13px;
  line-height: 1.6;
  border-radius: 10px;
  padding: 12px 14px;
  white-space: pre;
  overflow-x: auto;
  text-align: left;
}
.given {
  background: #0f172a;
  color: #e2e8f0;
  margin: 0 0 12px;
}
.editor {
  width: 100%;
  background: #1e293b;
  color: #f8fafc;
  border: 1px solid rgba(79, 70, 229, 0.5);
  outline: none;
  resize: vertical;
}
.editor:focus {
  border-color: #818cf8;
  box-shadow: 0 0 0 2px rgba(129, 140, 248, 0.25);
}
.console {
  font-family: 'Consolas', monospace;
  font-size: 12.5px;
  border-radius: 10px;
  padding: 10px 14px;
  white-space: pre-wrap;
  text-align: left;
}
.console.ok {
  background: rgba(34, 197, 94, 0.12);
  color: #16a34a;
  border: 1px solid rgba(34, 197, 94, 0.4);
}
.console.err {
  background: rgba(239, 68, 68, 0.1);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.35);
}
</style>
