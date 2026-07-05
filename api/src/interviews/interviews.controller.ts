import { Body, Controller, Get, HttpCode, HttpStatus, Post, UseGuards } from '@nestjs/common'
import { JwtAuthGuard } from '../auth/jwt-auth.guard'
import { CurrentUser } from '../common/current-user.decorator'
import type { User } from '../users/user.entity'
import { InterviewsService } from './interviews.service'
import { CreateInterviewDto } from './dto/interview.dto'

/** المقابلات التكيّفية — يطابق /interviews في ../api/openapi.yaml. */
@Controller('interviews')
@UseGuards(JwtAuthGuard)
export class InterviewsController {
  constructor(private readonly interviews: InterviewsService) {}

  @Get()
  list(@CurrentUser() user: User) {
    return this.interviews.list(user.id)
  }

  @Post()
  @HttpCode(HttpStatus.CREATED)
  create(@CurrentUser() user: User, @Body() dto: CreateInterviewDto) {
    return this.interviews.create(user.id, dto)
  }
}
