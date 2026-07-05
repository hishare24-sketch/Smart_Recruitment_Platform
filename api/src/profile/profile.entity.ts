import { Column, Entity, PrimaryGeneratedColumn } from 'typeorm'

export interface SkillProof {
  id: number
  type: 'assessment' | 'endorsement' | 'project' | 'certificate' | 'self'
  label: string
  date: string
}

export interface Skill {
  id: number
  name: string
  selfLevel: number
  category?: string
  proofs: SkillProof[]
}

export interface Experience {
  id: number
  title: string
  company: string
  period: string
  desc: string
}

export interface Certificate {
  id: number
  name: string
  issuer: string
  date: string
}

export interface ProofRequest {
  id: number
  from: string
  relation: string
  skill: string
  date: string
}

/**
 * الملف الخاص — صفّ واحد لكل مستخدم. الحقول المتداخلة (مهارات/خبرات/…)
 * تُخزَّن JSON لتطابق شكل المخزن الواحد في الواجهة (ProfileStore).
 */
@Entity('profiles')
export class Profile {
  @PrimaryGeneratedColumn()
  id!: number

  @Column({ unique: true })
  userId!: number

  @Column({ default: '' })
  headline!: string

  @Column({ default: '' })
  summary!: string

  @Column({ type: 'simple-json', default: '[]' })
  skills!: Skill[]

  @Column({ type: 'simple-json', default: '[]' })
  experiences!: Experience[]

  @Column({ type: 'simple-json', default: '[]' })
  certificates!: Certificate[]

  // تفضيلات الباحث (seeker_profiles) — تُخزَّن كما هي
  @Column({ type: 'simple-json', default: '{}' })
  prefs!: Record<string, unknown>

  @Column({ type: 'simple-json', default: '[]' })
  proofRequests!: ProofRequest[]
}
