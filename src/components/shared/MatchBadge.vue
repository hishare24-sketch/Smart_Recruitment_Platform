<script setup lang="ts">
// شارة «نسبة التطابق» الموحّدة عبر بطاقات الاكتشاف — تُوحّد اللون (utils/match)
// وشكل العرض وإتاحة القارئ الشاشيّ، مع ثلاث هيئات تناسب تخطيط كل بطاقة:
//  • bar  — شريط أفقيّ بتسمية ونسبة (بطاقة الفرص الشبكيّة)
//  • ring — حلقة دائريّة مضغوطة (صفّ الطلبات الأفقيّ)
//  • chip — رقاقة صغيرة (رأس بطاقة المقيّم)
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { matchChipColor, matchColor } from '@/utils/match'
import BaseProgressBar from '@/components/ui/BaseProgressBar.vue'
import BaseProgressRing from '@/components/ui/BaseProgressRing.vue'
import BaseChip from '@/components/ui/BaseChip.vue'

const props = withDefaults(defineProps<{
  value: number
  variant?: 'bar' | 'ring' | 'chip'
  /** نصّ مجاور مرئيّ (شريط/حلقة)؛ الرقاقة بلا تسمية مرئيّة */
  label?: string
  ringSize?: number
  ringWidth?: number
  barHeight?: number
}>(), { variant: 'bar', ringSize: 58, ringWidth: 5, barHeight: 6 })

const { t } = useI18n()
const tokenColor = computed(() => matchColor(props.value)) // رمز ثيم (شريط/حلقة)
const chipColor = computed(() => matchChipColor(props.value)) // لوحة BaseChip
const resolvedLabel = computed(() => props.label ?? t('discovery.matchRate'))
const ariaLabel = computed(() => `${t('discovery.matchRate')} ${props.value}%`)
</script>

<template>
  <!-- شريط أفقيّ -->
  <div
    v-if="variant === 'bar'" class="mt-1" role="meter"
    :aria-label="ariaLabel" :aria-valuenow="value" aria-valuemin="0" aria-valuemax="100"
  >
    <div class="mb-1 flex justify-between text-xs">
      <span class="text-muted">{{ resolvedLabel }}</span>
      <span class="font-bold" :style="{ color: `rgb(var(--v-theme-${tokenColor}))` }">{{ value }}%</span>
    </div>
    <BaseProgressBar :value="value" :color="tokenColor" :height="barHeight" />
  </div>

  <!-- حلقة دائريّة -->
  <div v-else-if="variant === 'ring'" class="flex flex-col items-center text-center" role="meter" :aria-label="ariaLabel" :aria-valuenow="value" aria-valuemin="0" aria-valuemax="100">
    <BaseProgressRing :value="value" :color="tokenColor" :size="ringSize" :width="ringWidth">
      <span class="text-xs font-bold">{{ value }}%</span>
    </BaseProgressRing>
    <div v-if="label" class="mt-1 text-xs text-muted">{{ label }}</div>
  </div>

  <!-- رقاقة -->
  <BaseChip v-else :color="chipColor" :aria-label="ariaLabel">{{ value }}%</BaseChip>
</template>
