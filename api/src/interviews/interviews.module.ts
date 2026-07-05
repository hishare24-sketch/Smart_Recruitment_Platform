import { Module } from '@nestjs/common'
import { TypeOrmModule } from '@nestjs/typeorm'
import { Interview } from './interview.entity'
import { InterviewsController } from './interviews.controller'
import { InterviewsService } from './interviews.service'

@Module({
  imports: [TypeOrmModule.forFeature([Interview])],
  controllers: [InterviewsController],
  providers: [InterviewsService],
})
export class InterviewsModule {}
