<script setup lang="ts">
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseIcon from '@/components/ui/BaseIcon.vue'

defineProps<{
  title: string
  subtitle?: string
  icon?: string
}>()
</script>

<template>
  <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
      <BaseAvatar v-if="icon" color="brand" :size="44" tonal square>
        <BaseIcon :name="icon" :size="24" />
      </BaseAvatar>
      <div>
        <h1 class="text-2xl font-bold">
          {{ title }}
        </h1>
        <p v-if="subtitle" class="text-sm text-muted">
          {{ subtitle }}
        </p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
