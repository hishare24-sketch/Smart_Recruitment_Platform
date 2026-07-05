import { Column, Entity, PrimaryGeneratedColumn } from 'typeorm'

export type BookingStatus = 'pending' | 'accepted' | 'rejected' | 'completed'

/** حجز جلسة تقييم لدى مقيّم. */
@Entity('bookings')
export class Booking {
  @PrimaryGeneratedColumn()
  id!: number

  @Column()
  userId!: number

  @Column()
  interviewerId!: number

  @Column({ default: '' })
  day!: string

  @Column({ default: '' })
  slot!: string

  @Column({ default: '' })
  type!: string

  @Column({ default: 'pending' })
  status!: BookingStatus

  @Column({ type: 'simple-json', default: '[]' })
  elements!: string[]

  @Column({ type: 'simple-json', nullable: true })
  report!: Record<string, unknown> | null
}
