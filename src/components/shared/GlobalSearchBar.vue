<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ai } from '@/services/ai'
import { TAXONOMY } from '@/services/taxonomy'
import type { SearchScope } from '@/services/ai/types'

const router = useRouter()

const query = ref('')
const focused = ref(false)
const listening = ref(false)

const suggestions = computed(() => (focused.value ? ai.searchSuggestions(query.value) : []))
const alternatives = computed(() => (query.value ? ai.keywordAlternatives(query.value) : []))

const scopes: { value: SearchScope, title: string }[] = [
  { value: 'all', title: 'الكل' },
  { value: 'requests', title: 'الطلبات' },
  { value: 'opportunities', title: 'الفرص' },
  { value: 'interviewers', title: 'المقيّمون' },
  { value: 'users', title: 'المستخدمون' },
  { value: 'companies', title: 'الشركات' },
]

function go(extra: Record<string, string> = {}) {
  focused.value = false
  router.push({ name: 'search', query: { q: query.value.trim(), ...extra } })
}
function pick(s: string) {
  query.value = s
  go()
}
function onBlur() {
  // delay so a suggestion mousedown registers before the list closes
  setTimeout(() => (focused.value = false), 200)
}

// Advanced search
const advDialog = ref(false)
const advScope = ref<SearchScope>('all')
const advKeywords = ref('')
const advCategory = ref<string | null>(null)
const advRating = ref(0)
const advDate = ref<string | null>(null)
const categoryOptions = TAXONOMY.map(c => ({ value: c.id, title: c.label }))
const dateOptions = [
  { value: 'day', title: 'آخر يوم' },
  { value: 'week', title: 'آخر أسبوع' },
  { value: 'month', title: 'آخر شهر' },
  { value: 'year', title: 'آخر سنة' },
]
function applyAdvanced() {
  const q: Record<string, string> = { q: (advKeywords.value || query.value).trim(), scope: advScope.value }
  if (advCategory.value)
    q.category = advCategory.value
  if (advRating.value)
    q.rating = String(advRating.value)
  if (advDate.value)
    q.date = advDate.value
  advDialog.value = false
  focused.value = false
  router.push({ name: 'search', query: q })
}

// Voice search (Web Speech API, graceful fallback)
const SpeechRec = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition
const voiceSupported = !!SpeechRec
function startVoice() {
  if (!SpeechRec)
    return
  const rec = new SpeechRec()
  rec.lang = 'ar-SA'
  rec.interimResults = false
  listening.value = true
  rec.onresult = (e: any) => {
    query.value = e.results[0][0].transcript
    listening.value = false
    go()
  }
  rec.onerror = () => (listening.value = false)
  rec.onend = () => (listening.value = false)
  rec.start()
}
</script>

<template>
  <div class="global-search position-relative flex-grow-1" style="max-width: 560px">
    <VTextField
      v-model="query"
      placeholder="ابحث في المنصة كاملة: طلبات، فرص، مقيّمون، مهارات..."
      prepend-inner-icon="mdi-magnify"
      variant="solo"
      density="compact"
      hide-details
      flat
      rounded="lg"
      bg-color="background"
      @focus="focused = true"
      @blur="onBlur"
      @keydown.enter="go()"
    >
      <template #append-inner>
        <VTooltip :text="voiceSupported ? 'بحث صوتي' : 'البحث الصوتي غير مدعوم في متصفحك'" location="bottom">
          <template #activator="{ props }">
            <VBtn v-bind="props" :icon="listening ? 'mdi-microphone' : 'mdi-microphone-outline'" :color="listening ? 'error' : undefined" variant="text" size="small" :disabled="!voiceSupported" @click.stop="startVoice" />
          </template>
        </VTooltip>
        <VTooltip text="بحث متقدم" location="bottom">
          <template #activator="{ props }">
            <VBtn v-bind="props" icon="mdi-tune-variant" variant="text" size="small" @click.stop="advDialog = true" />
          </template>
        </VTooltip>
      </template>
    </VTextField>

    <!-- Live suggestions -->
    <VExpandTransition>
      <VCard v-if="focused && (suggestions.length || alternatives.length)" class="position-absolute w-100 mt-1" style="z-index: 20" elevation="8">
        <VList density="compact">
          <VListSubheader v-if="alternatives.length" class="text-caption">هل تقصد</VListSubheader>
          <VListItem v-for="alt in alternatives" :key="`alt-${alt}`" prepend-icon="mdi-lightbulb-on-outline" :title="alt" @mousedown="pick(alt)" />
          <VListSubheader class="text-caption"><VIcon icon="mdi-robot-happy-outline" size="14" class="me-1" /> اقتراحات ذكية</VListSubheader>
          <VListItem v-for="(s, i) in suggestions" :key="i" prepend-icon="mdi-magnify" :title="s" @mousedown="pick(s)" />
        </VList>
      </VCard>
    </VExpandTransition>

    <!-- Advanced search dialog -->
    <VDialog v-model="advDialog" max-width="520">
      <VCard class="pa-2">
        <VCardTitle class="d-flex align-center ga-2"><VIcon icon="mdi-tune-variant" /> بحث متقدم</VCardTitle>
        <VCardText>
          <VSelect v-model="advScope" :items="scopes" label="نطاق البحث" class="mb-3" hide-details />
          <VTextField v-model="advKeywords" label="كلمات مفتاحية" placeholder="مثال: Laravel، الرياض" class="mb-3" hide-details />
          <VSelect v-model="advCategory" :items="categoryOptions" label="المجال" clearable class="mb-3" hide-details />
          <div class="text-caption font-weight-bold mb-1">أدنى تقييم ({{ advRating }}★)</div>
          <VSlider v-model="advRating" :min="0" :max="5" :step="0.5" color="warning" hide-details class="mb-3" />
          <VSelect v-model="advDate" :items="dateOptions" label="تاريخ النشر" clearable hide-details />
        </VCardText>
        <VCardActions class="justify-end">
          <VBtn variant="text" @click="advDialog = false">إلغاء</VBtn>
          <VBtn color="accent" prepend-icon="mdi-magnify" @click="applyAdvanced">بحث</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
