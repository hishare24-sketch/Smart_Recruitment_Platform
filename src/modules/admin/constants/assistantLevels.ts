// مستويات تفاعل المساعد الذكيّ — مقبض واحد يجمع (العمق + حجم الردّ).
// الأدمن يضبط الافتراضيّ + كثافة كلّ مستوًى (level_tokens) + السماح للمشترك بالتجاوز.
export type AssistantLevel = 1 | 2 | 3

export interface AssistantLevelDef {
  id: AssistantLevel
  icon: string
  labelKey: string
  hintKey: string
  defaultTokens: number
}

export const ASSISTANT_LEVELS: AssistantLevelDef[] = [
  { id: 1, icon: '⚡', labelKey: 'admin.ai.level1', hintKey: 'admin.ai.level1Hint', defaultTokens: 600 },
  { id: 2, icon: '⚖️', labelKey: 'admin.ai.level2', hintKey: 'admin.ai.level2Hint', defaultTokens: 1200 },
  { id: 3, icon: '🧠', labelKey: 'admin.ai.level3', hintKey: 'admin.ai.level3Hint', defaultTokens: 2400 },
]

export const DEFAULT_LEVEL_TOKENS: Record<string, number> = { 1: 600, 2: 1200, 3: 2400 }
