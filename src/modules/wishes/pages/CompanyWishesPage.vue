<script setup lang="ts">
import { ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'

const tab = ref('sent')

const sentWishes = [
  { candidate: 'أحمد المنصور', role: 'مطوّر واجهات أمامية', amount: '16,000 ريال', status: 'معلّق', color: 'warning', date: 'قبل يومين' },
  { candidate: 'سارة العتيبي', role: 'مهندسة برمجيات', amount: '18,000 ريال', status: 'مقبول', color: 'success', date: 'قبل 5 أيام' },
  { candidate: 'خالد الحربي', role: 'مطوّر ويب', amount: '12,000 ريال', status: 'مرفوض', color: 'error', date: 'قبل أسبوع' },
]

const receivedWishes = [
  { candidate: 'نورة القحطاني', service: 'استشارة تصميم واجهات', amount: '3,500 ريال', date: 'قبل يوم' },
]

const stats = [
  { title: 'رغبات مرسلة', value: 9, icon: 'mdi-send-outline', color: 'primary' },
  { title: 'مقبولة', value: 4, icon: 'mdi-check-circle-outline', color: 'success' },
  { title: 'معلّقة', value: 3, icon: 'mdi-clock-outline', color: 'warning' },
  { title: 'نسبة القبول', value: '57%', icon: 'mdi-percent-outline', color: 'secondary' },
]
</script>

<template>
  <div>
    <PageHeader
      title="إدارة الرغبات"
      subtitle="تابع الرغبات المرسلة للمرشحين والواردة منهم"
      icon="mdi-hand-heart-outline"
    />

    <VRow class="mb-2">
      <VCol v-for="s in stats" :key="s.title" cols="6" md="3">
        <StatCard v-bind="s" />
      </VCol>
    </VRow>

    <VTabs v-model="tab" color="primary" class="mb-4">
      <VTab value="sent" prepend-icon="mdi-send-outline">الرغبات المرسلة</VTab>
      <VTab value="received" prepend-icon="mdi-inbox-arrow-down-outline">الرغبات المستلمة</VTab>
    </VTabs>

    <VWindow v-model="tab">
      <VWindowItem value="sent">
        <VCard>
          <VTable>
            <thead>
              <tr>
                <th class="text-start">المرشح</th>
                <th class="text-start">الدور</th>
                <th class="text-start">المقابل</th>
                <th class="text-start">الحالة</th>
                <th class="text-start">التاريخ</th>
                <th class="text-start">إجراءات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="w in sentWishes" :key="w.candidate">
                <td class="font-weight-bold">{{ w.candidate }}</td>
                <td>{{ w.role }}</td>
                <td>{{ w.amount }}</td>
                <td><VChip :color="w.color" size="small" label>{{ w.status }}</VChip></td>
                <td class="text-medium-emphasis">{{ w.date }}</td>
                <td>
                  <VBtn icon="mdi-pencil" variant="text" size="small" />
                  <VBtn icon="mdi-close" variant="text" size="small" color="error" />
                </td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VWindowItem>

      <VWindowItem value="received">
        <VRow>
          <VCol v-for="w in receivedWishes" :key="w.candidate" cols="12" md="6">
            <VCard class="pa-4">
              <div class="d-flex align-center ga-3 mb-2">
                <VAvatar color="secondary" variant="tonal"><VIcon icon="mdi-account" /></VAvatar>
                <div>
                  <div class="text-subtitle-1 font-weight-bold">{{ w.candidate }}</div>
                  <div class="text-caption text-medium-emphasis">{{ w.service }}</div>
                </div>
              </div>
              <div class="text-body-2 mb-3">المقابل المطلوب: <span class="font-weight-bold">{{ w.amount }}</span></div>
              <div class="d-flex ga-2">
                <VBtn color="success" size="small" class="flex-grow-1" prepend-icon="mdi-check">قبول</VBtn>
                <VBtn color="warning" variant="outlined" size="small" prepend-icon="mdi-swap-horizontal">تفاوض</VBtn>
                <VBtn color="error" variant="outlined" size="small" icon="mdi-close" />
              </div>
            </VCard>
          </VCol>
        </VRow>
      </VWindowItem>
    </VWindow>
  </div>
</template>
