<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue'

// شريط إشعار سفلي أساس يحاكي VSnackbar — يظهر عند v-model=true ويختفي تلقائيًّا
// بعد timeout. لون دلالي + أزرار عبر slot. يُنقل لـ <body>.
const props = withDefaults(defineProps<{
  modelValue: boolean
  color?: string
  timeout?: number
}>(), { color: 'success', timeout: 4000 })
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

let timer: ReturnType<typeof setTimeout> | undefined
watch(() => props.modelValue, (open) => {
  clearTimeout(timer)
  if (open && props.timeout > 0)
    timer = setTimeout(() => emit('update:modelValue', false), props.timeout)
})
onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="rounded-ui snack-in fixed inset-x-0 bottom-6 z-[110] mx-auto flex w-fit max-w-[92vw] items-center gap-3 px-4 py-3 text-sm font-medium shadow-lg shadow-black/30"
      :style="{ background: `rgb(var(--v-theme-${color}))`, color: `rgb(var(--v-theme-on-${color}))` }"
    >
      <slot />
      <div v-if="$slots.actions" class="flex items-center gap-1">
        <slot name="actions" />
      </div>
    </div>
  </Teleport>
</template>
