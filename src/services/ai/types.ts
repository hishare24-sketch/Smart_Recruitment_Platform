// Shared AI service contracts — mock now, Claude API later (same interface)

export interface SkillLevelResult {
  level: 'entry' | 'mid' | 'advanced' | 'expert'
  confidence: number // 0-100
  rationale: string
}

export interface TrustTip {
  text: string
  gain: number // estimated % gain
  action?: string // route name to deep-link
  actionLabel?: string
}

export interface InterviewQuestion {
  id: number
  text: string
  competency: string
}

export interface AnswerEvaluation {
  score: number // 0-100
  feedback: string
}

export interface VideoAnalysis {
  bodyLanguage: number // 0-100
  tone: number // 0-100
  confidence: number // 0-100
  note: string
}

export type InterviewType = 'ai_text' | 'ai_video' | 'external' | 'expert'
export type InterviewLevel = 'basic' | 'intermediate' | 'advanced' | 'expert'

export interface AiService {
  skillLevel: (proofScore: number) => SkillLevelResult
  trustAnalysis: (factors: { key: string, label: string, value: number }[]) => TrustTip[]
  interviewQuestions: (type: InterviewType, level: InterviewLevel) => InterviewQuestion[]
  evaluateAnswer: (question: string, answer: string) => AnswerEvaluation
  videoAnalysis: () => VideoAnalysis
  suggestProofRequest: (skillName: string) => string
}
