import { describe, expect, it } from 'vitest'
import { ALL_SKILLS, TAXONOMY, categorizeSkill, getCategory } from './taxonomy'

describe('taxonomy', () => {
  it('exposes 8 main categories with subcategories and skills', () => {
    expect(TAXONOMY.length).toBe(8)
    for (const c of TAXONOMY) {
      expect(c.subcategories.length).toBeGreaterThan(0)
      expect(c.skills.length).toBeGreaterThan(0)
    }
  })

  it('classifies known skills into their category', () => {
    expect(categorizeSkill('Vue.js')).toBe('technology')
    expect(categorizeSkill('Figma')).toBe('design')
    expect(categorizeSkill('PMP')).toBe('management')
    expect(categorizeSkill('AutoCAD')).toBe('engineering')
  })

  it('returns undefined for unknown skills', () => {
    expect(categorizeSkill('نشاط غير معروف تمامًا')).toBeUndefined()
  })

  it('resolves categories by id and dedupes ALL_SKILLS', () => {
    expect(getCategory('technology')?.label).toBe('التقنية')
    expect(getCategory('nope')).toBeUndefined()
    expect(new Set(ALL_SKILLS).size).toBe(ALL_SKILLS.length)
  })
})
