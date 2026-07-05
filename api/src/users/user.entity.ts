import { BeforeInsert, Column, CreateDateColumn, Entity, PrimaryGeneratedColumn, UpdateDateColumn } from 'typeorm'
import { randomUUID } from 'node:crypto'

/** المستخدم — يقابل uuid/role/phone في واجهة المنصة (interfaces/Auth.ts). */
@Entity('users')
export class User {
  @PrimaryGeneratedColumn()
  id!: number

  @Column({ unique: true })
  uuid!: string

  @Column()
  name!: string

  @Column({ unique: true })
  email!: string

  // select: false — لا يظهر في الاستعلامات العادية؛ يُطلب صراحةً عند الدخول
  @Column({ select: false })
  password!: string

  @Column({ default: 'seeker' })
  role!: string

  @Column({ type: 'varchar', nullable: true })
  phone!: string | null

  @CreateDateColumn()
  created_at!: Date

  @UpdateDateColumn()
  updated_at!: Date

  @BeforeInsert()
  assignUuid() {
    if (!this.uuid)
      this.uuid = randomUUID()
  }
}
