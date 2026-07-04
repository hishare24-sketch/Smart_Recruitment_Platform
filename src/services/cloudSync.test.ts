import type { SupabaseClient } from '@supabase/supabase-js'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick, ref } from 'vue'
import { syncPrivateDoc, syncPublicProfileDoc } from './cloudSync'

// ===== عميل Supabase زائف — بلا شبكة، يسجّل النداءات للفحص =====

interface FakeOpts {
  /** معرّف الجلسة (null = زائر مجهول) */
  uid?: string | null
  /** محتوى صف المستند السحابي إن وُجد */
  row?: unknown
  /** إجبار فشل الجلب */
  fetchError?: boolean
}

function fakeClient(opts: FakeOpts = {}) {
  const calls = {
    fetches: 0,
    upserts: [] as { table: string, payload: Record<string, unknown> }[],
  }
  let authCallback: ((event: string) => void) | null = null

  const builder = (table: string) => {
    const b = {
      select: () => b,
      eq: () => b,
      maybeSingle: async () => {
        calls.fetches++
        if (opts.fetchError)
          return { data: null, error: { message: 'boom' } }
        return { data: opts.row !== undefined ? { data: opts.row } : null, error: null }
      },
      upsert: async (payload: Record<string, unknown>) => {
        calls.upserts.push({ table, payload })
        return { error: null }
      },
    }
    return b
  }

  const client = {
    auth: {
      getSession: async () => ({ data: { session: opts.uid ? { user: { id: opts.uid } } : null } }),
      onAuthStateChange: (cb: (event: string) => void) => {
        authCallback = cb
        return { data: { subscription: { unsubscribe: () => {} } } }
      },
    },
    from: builder,
  }
  return {
    client: client as unknown as SupabaseClient,
    calls,
    fireAuth: (event: string) => authCallback?.(event),
  }
}

/** تفريغ المهام الدقيقة (الجلب/الحمولات غير الخاضعة للمؤقتات) */
async function flush() {
  for (let i = 0; i < 6; i++)
    await Promise.resolve()
}

/** تمرير مهلة التجميع ثم تفريغ ما بعدها */
async function settle(ms = 1300) {
  await vi.advanceTimersByTimeAsync(ms)
  await flush()
}

beforeEach(() => {
  vi.useFakeTimers()
})
afterEach(() => {
  vi.useRealTimers()
})

describe('cloudSync — الوضع الخاص (syncPrivateDoc)', () => {
  it('يبقى off تمامًا بلا عميل (وضع المحاكاة الكاملة)', async () => {
    const state = ref({ n: 0 })
    const { status } = syncPrivateDoc({
      store: 'wallet',
      client: null,
      snapshot: () => state.value,
      apply: () => {},
      source: state,
    })
    state.value.n = 1
    await nextTick()
    await settle()
    expect(status.value).toBe('off')
  })

  it('بلا جلسة: لا جلب ولا رفع — الخصوصية أولًا', async () => {
    const { client, calls } = fakeClient({ uid: null })
    const state = ref({ n: 0 })
    const { status } = syncPrivateDoc({
      store: 'wallet',
      client,
      snapshot: () => state.value,
      apply: () => {},
      source: state,
    })
    await flush()
    expect(status.value).toBe('off')
    state.value.n = 1
    await nextTick()
    await settle()
    expect(calls.fetches).toBe(0)
    expect(calls.upserts).toHaveLength(0)
    expect(status.value).toBe('off')
  })

  it('بجلسة: يجلب ويطبّق النسخة السحابية ثم يرفع التعديلات بحمولة مملوكة', async () => {
    const { client, calls } = fakeClient({ uid: 'user-1', row: { n: 9 } })
    const state = ref({ n: 0 })
    const { status } = syncPrivateDoc({
      store: 'wallet',
      client,
      snapshot: () => state.value,
      apply: incoming => (state.value = incoming as { n: number }),
      source: state,
    })
    await flush()
    expect(state.value.n).toBe(9) // النسخة السحابية طُبّقت
    expect(status.value).toBe('synced')

    state.value.n = 10
    await nextTick()
    expect(status.value).toBe('saving')
    await settle()
    expect(calls.upserts).toHaveLength(1)
    expect(calls.upserts[0].table).toBe('account_states')
    expect(calls.upserts[0].payload).toMatchObject({ owner_id: 'user-1', store: 'wallet', data: { n: 10 } })
    expect(status.value).toBe('synced')
  })

  it('النسخة السحابية المطبَّقة لا ترتد رفعًا (حارس الارتداد)', async () => {
    const { client, calls } = fakeClient({ uid: 'user-1', row: { n: 5 } })
    const state = ref({ n: 0 })
    syncPrivateDoc({
      store: 'plan',
      client,
      snapshot: () => state.value,
      apply: incoming => (state.value = incoming as { n: number }),
      source: state,
    })
    await flush()
    await nextTick() // مراقب التعديلات يلتقط تطبيق النسخة السحابية...
    await settle()
    expect(calls.upserts).toHaveLength(0) // ...والحارس يمنع ارتدادها
  })

  it('الخروج من الجلسة يوقف المزامنة (off)', async () => {
    const { client, fireAuth } = fakeClient({ uid: 'user-1' })
    const state = ref({ n: 0 })
    const { status } = syncPrivateDoc({
      store: 'wallet',
      client,
      snapshot: () => state.value,
      apply: () => {},
      source: state,
    })
    await flush()
    expect(status.value).toBe('synced')
    fireAuth('SIGNED_OUT')
    expect(status.value).toBe('off')
  })
})

describe('cloudSync — الصفحة التعريفية العامة (syncPublicProfileDoc)', () => {
  it('بلا جلسة: يرفع بالمعرّف دون ادّعاء ملكية', async () => {
    const { client, calls } = fakeClient({ uid: null })
    const state = ref({ tagline: 'أ' })
    syncPublicProfileDoc({
      slug: () => 'demo-slug',
      client,
      snapshot: () => state.value,
      apply: () => {},
      source: state,
    })
    await flush()
    state.value.tagline = 'ب'
    await nextTick()
    await settle()
    expect(calls.upserts).toHaveLength(1)
    expect(calls.upserts[0].table).toBe('public_profiles')
    expect(calls.upserts[0].payload.slug).toBe('demo-slug')
    expect(calls.upserts[0].payload.owner_id).toBeUndefined()
  })

  it('بجلسة: الحفظ يدّعي الصف لصاحبه (owner_id)', async () => {
    const { client, calls } = fakeClient({ uid: 'user-7' })
    const state = ref({ tagline: 'أ' })
    syncPublicProfileDoc({
      slug: () => 'demo-slug',
      client,
      snapshot: () => state.value,
      apply: () => {},
      source: state,
    })
    await flush()
    await nextTick()
    await settle() // ارتداد تطبيق النسخة السحابية (إن وُجد) محسوم — نعدّل الآن
    calls.upserts.length = 0
    state.value.tagline = 'ج'
    await nextTick()
    await settle()
    expect(calls.upserts).toHaveLength(1)
    expect(calls.upserts[0].payload.owner_id).toBe('user-7')
  })

  it('فشل الجلب يظهر error لا انهيارًا', async () => {
    const { client } = fakeClient({ uid: null, fetchError: true })
    const state = ref({ tagline: 'أ' })
    const { status } = syncPublicProfileDoc({
      slug: () => 'demo-slug',
      client,
      snapshot: () => state.value,
      apply: () => {},
      source: state,
    })
    await flush()
    expect(status.value).toBe('error')
  })
})
