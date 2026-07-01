import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { STATE_META, useRequestsStore } from './RequestsStore'

describe('RequestsStore', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('seeds requests with org rating, state and sort order', () => {
    const store = useRequestsStore()
    expect(store.requests.length).toBeGreaterThan(0)
    for (const r of store.requests) {
      expect(r.orgRating).toBeGreaterThan(0)
      expect(r.orgReviews).toBeGreaterThanOrEqual(0)
      expect(STATE_META[r.state]).toBeTruthy()
      expect(typeof r.postedOrder).toBe('number')
    }
  })

  it('applies to a request once and tracks it in mine', () => {
    const store = useRequestsStore()
    const req = store.requests[0]
    const before = store.mine.length
    store.apply(req)
    expect(store.hasApplied(req.id)).toBe(true)
    store.apply(req) // no duplicate
    expect(store.mine.length).toBe(before + 1)
  })
})
