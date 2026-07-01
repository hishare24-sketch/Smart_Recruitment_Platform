<script setup lang="ts">
import { computed, ref } from 'vue'
import { TAXONOMY, categorizeSkill } from '@/services/taxonomy'

// Each item carries its skills (for category counting) and a text blob (for
// sub-category keyword counting).
const props = defineProps<{ items: { skills: string[], text: string }[] }>()
const selection = defineModel<{ category?: string, sub?: string }>({ default: () => ({}) })

const catSearch = ref('')
const expanded = ref<Set<string>>(new Set())

const tree = computed(() => {
  return TAXONOMY.map((cat) => {
    const inCat = props.items.filter(it => it.skills.some(s => categorizeSkill(s) === cat.id))
    const subs = cat.subcategories
      .map(name => ({ name, count: inCat.filter(it => it.text.includes(name)).length }))
      .filter(s => s.count > 0)
    return { id: cat.id, label: cat.label, icon: cat.icon, color: cat.color, count: inCat.length, subs }
  }).filter(c => c.count > 0)
})

const visibleTree = computed(() => {
  const q = catSearch.value.trim()
  if (!q)
    return tree.value
  return tree.value.filter(c => c.label.includes(q) || c.subs.some(s => s.name.includes(q)))
})

const total = computed(() => props.items.length)

function toggleExpand(id: string) {
  const next = new Set(expanded.value)
  next.has(id) ? next.delete(id) : next.add(id)
  expanded.value = next
}
function selectCategory(id: string) {
  if (selection.value.category === id && !selection.value.sub)
    selection.value = {}
  else
    selection.value = { category: id }
  toggleExpandOpen(id)
}
function toggleExpandOpen(id: string) {
  if (!expanded.value.has(id))
    expanded.value = new Set(expanded.value).add(id)
}
function selectSub(id: string, sub: string) {
  if (selection.value.category === id && selection.value.sub === sub)
    selection.value = { category: id }
  else
    selection.value = { category: id, sub }
}
function clearAll() {
  selection.value = {}
  catSearch.value = ''
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-2">
      <span class="text-subtitle-2 font-weight-bold"><VIcon icon="mdi-file-tree-outline" size="18" class="me-1" /> التصنيفات</span>
      <span class="text-caption text-medium-emphasis">{{ total }}</span>
    </div>

    <VTextField
      v-model="catSearch"
      placeholder="بحث في التصنيفات"
      prepend-inner-icon="mdi-magnify"
      density="compact"
      variant="outlined"
      hide-details
      clearable
      class="mb-2"
    />

    <VList density="compact" class="py-0" nav>
      <template v-for="cat in visibleTree" :key="cat.id">
        <VListItem
          :active="selection.category === cat.id && !selection.sub"
          rounded="lg"
          @click="selectCategory(cat.id)"
        >
          <template #prepend>
            <VIcon
              :icon="expanded.has(cat.id) ? 'mdi-menu-down' : 'mdi-menu-left'"
              size="18"
              class="me-1"
              @click.stop="toggleExpand(cat.id)"
            />
            <VIcon :icon="cat.icon" :color="cat.color" size="18" />
          </template>
          <VListItemTitle class="text-body-2 font-weight-medium">{{ cat.label }}</VListItemTitle>
          <template #append>
            <VChip size="x-small" label>{{ cat.count }}</VChip>
          </template>
        </VListItem>

        <template v-if="expanded.has(cat.id)">
          <VListItem
            v-for="sub in cat.subs"
            :key="`${cat.id}-${sub.name}`"
            :active="selection.category === cat.id && selection.sub === sub.name"
            rounded="lg"
            class="ps-8"
            @click="selectSub(cat.id, sub.name)"
          >
            <VListItemTitle class="text-caption">{{ sub.name }}</VListItemTitle>
            <template #append>
              <span class="text-caption text-medium-emphasis">{{ sub.count }}</span>
            </template>
          </VListItem>
        </template>
      </template>
    </VList>

    <div v-if="!visibleTree.length" class="text-caption text-medium-emphasis text-center py-3">لا تصنيفات مطابقة</div>

    <VBtn v-if="selection.category || catSearch" variant="text" size="small" color="error" prepend-icon="mdi-close" block class="mt-2" @click="clearAll">
      إزالة الكل
    </VBtn>
  </div>
</template>
