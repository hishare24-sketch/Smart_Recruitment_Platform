import { Column, Entity, PrimaryGeneratedColumn } from 'typeorm'

export type RequestType = 'job' | 'project' | 'consultation' | 'task'
export type RequestState = 'new' | 'reviewing' | 'accepted' | 'closed'

/** طلب في سوق الطلبات (وظيفة/مشروع/استشارة/مهمة). */
@Entity('market_requests')
export class MarketRequest {
  @PrimaryGeneratedColumn()
  id!: number

  @Column({ type: 'int', nullable: true })
  userId!: number | null

  @Column({ default: 'job' })
  type!: RequestType

  @Column({ default: '' })
  title!: string

  @Column({ default: '' })
  org!: string

  @Column({ default: 'new' })
  state!: RequestState

  @Column({ default: '' })
  compensation!: string

  @Column({ default: false })
  remote!: boolean
}
