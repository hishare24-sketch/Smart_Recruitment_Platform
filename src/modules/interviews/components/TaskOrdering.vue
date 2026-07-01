<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { AdaptiveQuestion, ReversePlanPayload } from '@/services/ai'

const props = defineProps<{ question: AdaptiveQuestion }>()
const answer = defineModel<string>('answer', { default: '' })
const payload = computed(() => props.question.payload as ReversePlanPayload)

const ordered = ref<string[]>([...payload.value.tasks])
const twistApplied = ref(false)

function move(i: number, dir: -1 | 1) {
  const j = i + dir
  if (j < 0 || j >= ordered.value.length)
    return
  const arr = [...ordered.value]
  ;[arr[i], arr[j]] = [arr[j], arr[i]]
  ordered.value = arr
}

// Adaptive twist: a task's deadline moves up — surface it once the user starts
function applyTwist() {
  twistApplied.value = true
}

watch(ordered, () => {
  answer.value = `الترتيب العكسي (من التسليم للبداية): ${ordered.value.join(' ← ')}`
}, { immediate: true })
</script>

<template>
  <div>
    <VAlert type="info" variant="tonal" density="compact" class="mb-3 text-body-2">
      الهدف: <strong>{{ payload.goal }}</strong> · الموعد النهائي: <strong>{{ payload.deadline }}</strong>
      <div class="text-caption mt-1">رتّب المهام من التسليم النهائي (أعلى) إلى نقطة البداية (أسفل).</div>
    </VAlert>

    <VCard variant="outlined" class="pa-2 mb-2">
      <div v-for="(t, i) in ordered" :key="t" class="d-flex align-center ga-2 pa-2 rounded task-row">
        <VChip size="x-small" color="primary" label>{{ i + 1 }}</VChip>
        <span class="text-body-2 flex-grow-1" :class="{ 'text-error font-weight-bold': twistApplied && t === payload.tasks[1] }">
          {{ t }}
          <VChip v-if="twistApplied && t === payload.tasks[1]" size="x-small" color="error" label class="ms-1">قُدّم موعدها!</VChip>
        </span>
        <VBtn icon="mdi-chevron-up" size="x-small" variant="text" :disabled="i === 0" @click="move(i, -1)" />
        <VBtn icon="mdi-chevron-down" size="x-small" variant="text" :disabled="i === ordered.length - 1" @click="move(i, 1)" />
      </div>
    </VCard>

    <VBtn v-if="!twistApplied" size="small" color="warning" variant="tonal" prepend-icon="mdi-shuffle-variant" @click="applyTwist">
      محاكاة تغيّر مفاجئ في المواعيد
    </VBtn>
    <VAlert v-else color="warning" variant="tonal" density="compact" class="text-caption" border="start">
      <VIcon icon="mdi-alert-decagram-outline" size="15" class="me-1" />تغيّر مفاجئ: قُدّم موعد «{{ payload.tasks[1] }}» — أعد ترتيبها بما يناسب.
    </VAlert>
  </div>
</template>

<style scoped>
.task-row:hover {
  background: rgba(79, 70, 229, 0.06);
}
</style>
