<script setup lang="ts" generic="T extends string | number">
import BaseIcon from '@/components/ui/BaseIcon.vue'

// قائمة اختيار أساس تحاكي VSelect — تعتمد <select> أصليًّا منسّقًا بالثيم (موثوق
// وسهل الوصول). عام (generic) فيحفظ نوع القيمة. `clearable` يضيف خيار العنصر النائب.
const props = withDefaults(defineProps<{
  modelValue?: T | null
  items: { value: T, title: string }[]
  placeholder?: string
  prefixIcon?: string
  clearable?: boolean
}>(), { clearable: false })
const emit = defineEmits<{ 'update:modelValue': [value: T | null] }>()

// خلفية/لون صريح للخيارات كي تُقرأ في الوضع الداكن (قائمة النظام لا ترث الثيم)
const optStyle = 'background: rgb(var(--v-theme-surface)); color: rgb(var(--v-theme-on-surface))'

function onChange(e: Event) {
  const raw = (e.target as HTMLSelectElement).value
  if (raw === '') {
    emit('update:modelValue', null)
    return
  }
  const found = props.items.find(it => String(it.value) === raw)
  emit('update:modelValue', found ? found.value : null)
}
</script>

<template>
  <div class="input-wrap rounded-ui relative flex items-center bg-surface px-3">
    <BaseIcon v-if="prefixIcon" :name="prefixIcon" :size="20" class="me-2 text-muted" />
    <select
      :value="modelValue == null ? '' : String(modelValue)"
      class="h-11 min-w-0 flex-1 appearance-none bg-transparent text-content outline-none"
      @change="onChange"
    >
      <option v-if="placeholder || clearable" value="" :style="optStyle">{{ placeholder ?? '—' }}</option>
      <option v-for="it in items" :key="String(it.value)" :value="String(it.value)" :style="optStyle">{{ it.title }}</option>
    </select>
    <BaseIcon name="mdi-chevron-down" :size="18" class="pointer-events-none ms-1 text-muted" />
  </div>
</template>
