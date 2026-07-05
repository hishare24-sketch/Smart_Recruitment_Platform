<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'

// نافذة حوار أساس تحاكي VDialog — طبقة معتمة + لوحة موسّطة، تُنقل لـ <body>
// (Teleport) لتعلو القشرة. تُغلق بالنقر على الخلفية أو Escape. عنوان + أزرار (slots).
const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  maxWidth?: number
}>(), { maxWidth: 520 })
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

function close() {
  emit('update:modelValue', false)
}
function onKey(e: KeyboardEvent) {
  if (props.modelValue && e.key === 'Escape')
    close()
}
onMounted(() => document.addEventListener('keydown', onKey))
onBeforeUnmount(() => document.removeEventListener('keydown', onKey))
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <!-- backdrop -->
      <div class="absolute inset-0 bg-black/50" @click="close" />
      <!-- panel -->
      <div
        class="border-ui rounded-ui-lg dd-panel relative z-10 max-h-[90vh] w-full overflow-y-auto bg-surface text-content shadow-2xl shadow-black/40"
        :style="{ maxWidth: `${maxWidth}px` }"
      >
        <div v-if="title || $slots.title" class="flex items-center justify-between border-b border-ui p-4">
          <div class="text-lg font-bold">
            <slot name="title">{{ title }}</slot>
          </div>
          <button class="icon-btn h-8 w-8" aria-label="إغلاق" @click="close">
            <BaseIcon name="mdi-close" :size="20" />
          </button>
        </div>
        <div class="p-4">
          <slot />
        </div>
        <div v-if="$slots.actions" class="flex justify-end gap-2 border-t border-ui p-4">
          <slot name="actions" />
        </div>
      </div>
    </div>
  </Teleport>
</template>
