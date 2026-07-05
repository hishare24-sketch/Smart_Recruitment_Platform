<script setup lang="ts">
// شريط تمرير أساس يحاكي VSlider — يعتمد <input type="range"> أصليًّا مع
// accent-color من الثيم (يلوّن المسار والمقبض في المتصفحات الحديثة).
withDefaults(defineProps<{
  modelValue?: number
  min?: number
  max?: number
  step?: number
  color?: string
}>(), { modelValue: 0, min: 0, max: 100, step: 1, color: 'secondary' })
const emit = defineEmits<{ 'update:modelValue': [value: number] }>()
</script>

<template>
  <input
    type="range"
    :value="modelValue"
    :min="min"
    :max="max"
    :step="step"
    class="h-2 w-full cursor-pointer"
    :style="{ accentColor: `rgb(var(--v-theme-${color}))` }"
    @input="emit('update:modelValue', Number(($event.target as HTMLInputElement).value))"
  >
</template>
