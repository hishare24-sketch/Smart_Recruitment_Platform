import { Module } from '@nestjs/common'
import { TypeOrmModule } from '@nestjs/typeorm'
import { Interviewer } from './interviewer.entity'
import { Booking } from './booking.entity'
import { InterviewersController } from './interviewers.controller'
import { InterviewersService } from './interviewers.service'

@Module({
  imports: [TypeOrmModule.forFeature([Interviewer, Booking])],
  controllers: [InterviewersController],
  providers: [InterviewersService],
})
export class InterviewersModule {}
