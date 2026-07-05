import { Body, Controller, Get, HttpCode, HttpStatus, Param, ParseIntPipe, Post, Query, UseGuards } from '@nestjs/common'
import { JwtAuthGuard } from '../auth/jwt-auth.guard'
import { CurrentUser } from '../common/current-user.decorator'
import type { User } from '../users/user.entity'
import { MarketplaceService } from './marketplace.service'
import { CreateOpportunityDto } from './dto/marketplace.dto'
import type { RequestType } from './market-request.entity'

/** الفرص وسوق الطلبات — يطابق /opportunities + /requests في ../api/openapi.yaml. */
@Controller()
@UseGuards(JwtAuthGuard)
export class MarketplaceController {
  constructor(private readonly market: MarketplaceService) {}

  @Get('opportunities')
  opportunities(@Query('q') q?: string, @Query('category') category?: string) {
    return this.market.listOpportunities(q, category)
  }

  @Post('opportunities')
  @HttpCode(HttpStatus.CREATED)
  create(@CurrentUser() user: User, @Body() dto: CreateOpportunityDto) {
    return this.market.createOpportunity(user.id, dto)
  }

  @Post('opportunities/:id/apply')
  @HttpCode(HttpStatus.CREATED)
  apply(@CurrentUser() user: User, @Param('id', ParseIntPipe) id: number) {
    return this.market.apply(user.id, id)
  }

  // /requests/mine قبل أي مسار عام بمعامل — الترتيب مهم
  @Get('requests/mine')
  myRequests(@CurrentUser() user: User) {
    return this.market.listMyRequests(user.id)
  }

  @Get('requests')
  requests(@Query('type') type?: RequestType) {
    return this.market.listRequests(type)
  }
}
