import { Column, CreateDateColumn, Entity, PrimaryGeneratedColumn } from 'typeorm'

/** تقديم مرشّح على فرصة. */
@Entity('applications')
export class Application {
  @PrimaryGeneratedColumn()
  id!: number

  @Column()
  opportunityId!: number

  @Column()
  userId!: number

  @CreateDateColumn()
  created_at!: Date
}
