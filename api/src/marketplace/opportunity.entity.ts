import { Column, Entity, PrimaryGeneratedColumn } from 'typeorm'

/** فرصة منشورة من جهة توظيف. */
@Entity('opportunities')
export class Opportunity {
  @PrimaryGeneratedColumn()
  id!: number

  @Column({ type: 'int', nullable: true })
  userId!: number | null

  @Column({ default: '' })
  title!: string

  @Column({ default: '' })
  company!: string

  @Column({ default: '' })
  location!: string

  @Column({ default: '' })
  salary!: string

  @Column({ default: '' })
  category!: string

  @Column({ type: 'simple-json', default: '[]' })
  skills!: string[]
}
