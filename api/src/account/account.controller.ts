import { Body, Controller, Get, HttpCode, HttpStatus, Put, UseGuards } from '@nestjs/common'
import { JwtAuthGuard } from '../auth/jwt-auth.guard'
import { CurrentUser } from '../common/current-user.decorator'
import type { User } from '../users/user.entity'
import { AccountService } from './account.service'
import { SetPlanDto } from './dto/plan.dto'

/** المحفظة وباقة الحساب — يطابق /wallet + /account/plan في ../api/openapi.yaml. */
@Controller()
@UseGuards(JwtAuthGuard)
export class AccountController {
  constructor(private readonly account: AccountService) {}

  @Get('wallet')
  wallet(@CurrentUser() user: User) {
    return this.account.getWallet(user.id)
  }

  @Get('account/plan')
  plan(@CurrentUser() user: User) {
    return this.account.getPlan(user.id)
  }

  @Put('account/plan')
  @HttpCode(HttpStatus.OK)
  setPlan(@CurrentUser() user: User, @Body() dto: SetPlanDto) {
    return this.account.setPlan(user.id, dto.tier)
  }
}
