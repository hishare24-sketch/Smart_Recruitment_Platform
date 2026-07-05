import { Column, Entity, PrimaryGeneratedColumn } from 'typeorm'

export type InterviewTrack = 'tech' | 'management' | 'design' | 'data' | 'support'

/** مقابلة تكيّفية (AI) — مملوكة لصاحبها. */
@Entity('interviews')
export class Interview {
  @PrimaryGeneratedColumn()
  id!: number

  @Column()
  userId!: number

  @Column({ default: 'tech' })
  track!: InterviewTrack

  @Column({ default: 'scheduled' })
  status!: string

  @Column({ type: 'float', default: 0 })
  score!: number

  @Column({ type: 'simple-json', default: '{}' })
  integrity!: Record<string, unknown>
}
