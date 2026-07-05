import { Column, Entity, PrimaryGeneratedColumn } from 'typeorm'

export type SurveyState = 'draft' | 'pending' | 'active' | 'paused' | 'closed' | 'archived'

/** استبيان — الدورة الإدارية بحالاتها الست. مملوك لمنشئه. */
@Entity('surveys')
export class Survey {
  @PrimaryGeneratedColumn()
  id!: number

  @Column()
  userId!: number

  @Column({ default: '' })
  title!: string

  @Column({ default: 'draft' })
  state!: SurveyState

  @Column({ type: 'int', default: 0 })
  pointsPool!: number

  @Column({ type: 'simple-json', default: '{}' })
  targeting!: Record<string, unknown>

  @Column({ type: 'simple-json', default: '[]' })
  questions!: Array<Record<string, unknown>>

  @Column({ type: 'simple-json', default: '[]' })
  responses!: Array<Record<string, unknown>>
}
