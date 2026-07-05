import { Column, Entity, PrimaryGeneratedColumn } from 'typeorm'

export interface WalletTransaction {
  id: number
  amount: number
  label: string
  at: string
}

/** محفظة المستخدم — رصيد + حركات. صفّ واحد لكل مستخدم. */
@Entity('wallets')
export class Wallet {
  @PrimaryGeneratedColumn()
  id!: number

  @Column({ unique: true })
  userId!: number

  @Column({ type: 'float', default: 0 })
  balance!: number

  @Column({ type: 'simple-json', default: '[]' })
  transactions!: WalletTransaction[]
}
