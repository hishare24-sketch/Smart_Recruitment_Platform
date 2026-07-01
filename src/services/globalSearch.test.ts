import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useGlobalSearch } from './globalSearch'

describe('globalSearch', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('returns categorized results across sections', () => {
    const { search } = useGlobalSearch()
    const cats = search('Vue')
    const keys = cats.map(c => c.key)
    expect(keys).toContain('requests')
    expect(keys).toContain('opportunities')
    expect(keys).toContain('interviewers')
    const total = cats.reduce((s, c) => s + c.items.length, 0)
    expect(total).toBeGreaterThan(0)
  })

  it('limits results to a single scope', () => {
    const { search } = useGlobalSearch()
    const cats = search('خالد', 'interviewers')
    expect(cats.every(c => c.key === 'interviewers')).toBe(true)
  })

  it('filters by taxonomy category', () => {
    const { search } = useGlobalSearch()
    const tech = search('', 'requests', 'technology')
    const design = search('', 'requests', 'design')
    // technology should surface Vue/TS requests; design a different (smaller) set
    expect(tech[0].items.length).toBeGreaterThan(0)
    expect(tech[0].items.length).not.toBe(design[0].items.length)
  })
})
