import { Module } from '@nestjs/common'
import { TypeOrmModule } from '@nestjs/typeorm'
import { User } from '../users/user.entity'
import { Survey } from './survey.entity'
import { SurveysController } from './surveys.controller'
import { SurveysService } from './surveys.service'

@Module({
  imports: [TypeOrmModule.forFeature([Survey, User])],
  controllers: [SurveysController],
  providers: [SurveysService],
})
export class SurveysModule {}
