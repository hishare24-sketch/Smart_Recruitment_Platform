import { Column, CreateDateColumn, Entity, PrimaryGeneratedColumn } from 'typeorm'

export type NotificationCategory = 'opportunity' | 'wish' | 'endorsement' | 'message' | 'system' | 'interview'

/** إشعار موجّه لمستخدم. */
@Entity('notifications')
export class Notification {
  @PrimaryGeneratedColumn()
  id!: number

  @Column()
  userId!: number

  @Column({ default: 'mdi-bell' })
  icon!: string

  @Column({ default: '' })
  title!: string

  @Column({ type: 'text', default: '' })
  body!: string

  @Column({ default: 'system' })
  category!: NotificationCategory

  @Column({ default: false })
  read!: boolean

  @Column({ type: 'varchar', nullable: true })
  actionTo!: string | null

  @CreateDateColumn()
  created_at!: Date
}
