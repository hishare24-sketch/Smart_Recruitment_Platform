import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useWishesStore } from './WishesStore'

beforeEach(() => {
  localStorage.clear()
  setActivePinia(createPinia())
  vi.useFakeTimers()
})
afterEach(() => {
  vi.useRealTimers()
})

describe('WishesStore company side — sent wishes', () => {
  it('sends a wish and receives a simulated candidate reply', () => {
    const s = useWishesStore()
    const before = s.sent.length
    const w = s.sendWish({ candidateName: 'مرشح تجريبي', role: 'مطوّر', amount: '10,000 ريال', duration: 'سنة', reason: 'خبرة مناسبة' })
    expect(s.sent.length).toBe(before + 1)
    expect(w.status).toBe('pending')
    vi.advanceTimersByTime(7000)
    expect(['accepted', 'rejected']).toContain(s.sent.find(x => x.id === w.id)!.status)
  })

  it('edits, withdraws and resends a wish', () => {
    const s = useWishesStore()
    const w = s.sendWish({ candidateName: 'آخر', role: 'مصمم', amount: '5,000', duration: 'مهمة', reason: '—' })
    s.updateWish(w.id, { amount: '6,000' })
    expect(s.sent.find(x => x.id === w.id)!.amount).toBe('6,000')
    s.withdrawWish(w.id)
    expect(s.sent.find(x => x.id === w.id)!.status).toBe('withdrawn')
    expect(s.sentActive.some(x => x.id === w.id)).toBe(false)
    s.resendWish(w.id)
    expect(s.sent.find(x => x.id === w.id)!.status).toBe('pending')
  })

  it('computes the acceptance rate from resolved wishes only', () => {
    const s = useWishesStore()
    // seed: 1 accepted + 1 rejected + 1 pending → 50%
    expect(s.acceptanceRate).toBe(50)
  })
})

describe('WishesStore company side — received offers', () => {
  it('accepts and declines offers', () => {
    const s = useWishesStore()
    s.respondOffer(1, 'accepted')
    expect(s.received.find(o => o.id === 1)!.status).toBe('accepted')
    s.respondOffer(2, 'declined')
    expect(s.received.find(o => o.id === 2)!.status).toBe('declined')
    expect(s.newOffersCount).toBe(0)
  })

  it('negotiates: counter amount then simulated agreement', () => {
    const s = useWishesStore()
    s.negotiateOffer(1, '3,000 ريال')
    const o = s.received.find(x => x.id === 1)!
    expect(o.status).toBe('negotiating')
    expect(o.amount).toBe('3,000 ريال')
    vi.advanceTimersByTime(6000)
    expect(o.status).toBe('accepted')
  })
})
