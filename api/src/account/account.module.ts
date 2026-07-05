import { Module } from '@nestjs/common'
import { TypeOrmModule } from '@nestjs/typeorm'
import { User } from '../users/user.entity'
import { Wallet } from './wallet.entity'
import { AccountController } from './account.controller'
import { AccountService } from './account.service'

@Module({
  imports: [TypeOrmModule.forFeature([Wallet, User])],
  controllers: [AccountController],
  providers: [AccountService],
  exports: [AccountService],
})
export class AccountModule {}
