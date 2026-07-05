import { HttpException, HttpStatus, Injectable } from '@nestjs/common'
import { InjectRepository } from '@nestjs/typeorm'
import { Repository } from 'typeorm'
import { User } from '../users/user.entity'
import { Wallet } from './wallet.entity'
import type { Tier } from './dto/plan.dto'

/** سعر الترقية لكل باقة — الترقية تدفع الفرق من المحفظة، التخفيض مجّاني. */
const PLAN_PRICE: Record<Tier, number> = { free: 0, pro: 50, elite: 150 }

function nextId(items: { id: number }[]): number {
  return items.reduce((m, i) => Math.max(m, i.id), 0) + 1
}

@Injectable()
export class AccountService {
  constructor(
    @InjectRepository(Wallet) private readonly wallets: Repository<Wallet>,
    @InjectRepository(User) private readonly users: Repository<User>,
  ) {}

  /** محفظة المستخدم — تُنشأ برصيد ترحيبي عند أول وصول. */
  async getWallet(userId: number): Promise<Wallet> {
    let wallet = await this.wallets.findOne({ where: { userId } })
    if (!wallet) {
      wallet = this.wallets.create({
        userId,
        balance: 100,
        transactions: [{ id: 1, amount: 100, label: 'رصيد ترحيبي', at: new Date().toISOString() }],
      })
      await this.wallets.save(wallet)
    }
    return wallet
  }

  async getPlan(userId: number): Promise<{ tier: string }> {
    const user = await this.users.findOne({ where: { id: userId } })
    return { tier: user?.tier ?? 'free' }
  }

  /** ترقية/تخفيض الباقة — الترقية تخصم الفرق من المحفظة (402 عند نقص الرصيد). */
  async setPlan(userId: number, tier: Tier): Promise<{ tier: Tier, balance: number }> {
    const user = await this.users.findOneOrFail({ where: { id: userId } })
    const current = (user.tier ?? 'free') as Tier
    const cost = Math.max(0, PLAN_PRICE[tier] - PLAN_PRICE[current])

    if (cost > 0) {
      const wallet = await this.getWallet(userId)
      if (wallet.balance < cost)
        throw new HttpException({ message: 'الرصيد غير كافٍ لترقية الباقة', code: 'insufficient_funds' }, HttpStatus.PAYMENT_REQUIRED)
      wallet.balance -= cost
      wallet.transactions = [...wallet.transactions, {
        id: nextId(wallet.transactions),
        amount: -cost,
        label: `ترقية الباقة إلى ${tier}`,
        at: new Date().toISOString(),
      }]
      await this.wallets.save(wallet)
    }

    user.tier = tier
    await this.users.save(user)
    const balance = (await this.getWallet(userId)).balance
    return { tier, balance }
  }
}
