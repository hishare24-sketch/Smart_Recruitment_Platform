import { Column, Entity, PrimaryGeneratedColumn } from 'typeorm'

/** مقيّم معتمد في السوق. */
@Entity('interviewers')
export class Interviewer {
  @PrimaryGeneratedColumn()
  id!: number

  @Column({ type: 'int', nullable: true })
  userId!: number | null

  @Column({ default: '' })
  name!: string

  @Column({ default: '' })
  specialty!: string

  @Column({ type: 'float', default: 0 })
  rating!: number

  @Column({ type: 'float', default: 0 })
  priceFrom!: number

  @Column({ type: 'simple-json', default: '[]' })
  availability!: string[]
}
