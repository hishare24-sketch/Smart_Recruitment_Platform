import { Module } from '@nestjs/common'
import { TypeOrmModule } from '@nestjs/typeorm'
import { Opportunity } from './opportunity.entity'
import { Application } from './application.entity'
import { MarketRequest } from './market-request.entity'
import { MarketplaceController } from './marketplace.controller'
import { MarketplaceService } from './marketplace.service'

@Module({
  imports: [TypeOrmModule.forFeature([Opportunity, Application, MarketRequest])],
  controllers: [MarketplaceController],
  providers: [MarketplaceService],
})
export class MarketplaceModule {}
