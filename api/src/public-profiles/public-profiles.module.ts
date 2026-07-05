import { Module } from '@nestjs/common'
import { TypeOrmModule } from '@nestjs/typeorm'
import { ProfileModule } from '../profile/profile.module'
import { PublicProfile } from './public-profile.entity'
import { PublicProfilesController } from './public-profiles.controller'
import { PublicProfilesService } from './public-profiles.service'

@Module({
  imports: [TypeOrmModule.forFeature([PublicProfile]), ProfileModule],
  controllers: [PublicProfilesController],
  providers: [PublicProfilesService],
})
export class PublicProfilesModule {}
