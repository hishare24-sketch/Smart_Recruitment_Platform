import type { AiService } from './types'
import { mockAi } from './mockAi'

// Flip to false and add claudeAi.ts to use the real Claude API later.
const USE_MOCK_AI = true

export const ai: AiService = USE_MOCK_AI ? mockAi : mockAi

export * from './interviewEngine'
export * from './types'
