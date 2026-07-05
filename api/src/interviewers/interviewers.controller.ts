import { Body, Controller, Get, HttpCode, HttpStatus, Param, ParseIntPipe, Patch, Post, UseGuards } from '@nestjs/common'
import { JwtAuthGuard } from '../auth/jwt-auth.guard'
import { CurrentUser } from '../common/current-user.decorator'
import type { User } from '../users/user.entity'
import { InterviewersService } from './interviewers.service'
import { CreateBookingDto, UpdateBookingDto } from './dto/booking.dto'

/** المقيّمون والحجوزات — يطابق /interviewers + /bookings في ../api/openapi.yaml. */
@Controller()
@UseGuards(JwtAuthGuard)
export class InterviewersController {
  constructor(private readonly service: InterviewersService) {}

  @Get('interviewers')
  list() {
    return this.service.list()
  }

  @Post('interviewers/:id/bookings')
  @HttpCode(HttpStatus.CREATED)
  book(@CurrentUser() user: User, @Param('id', ParseIntPipe) id: number, @Body() dto: CreateBookingDto) {
    return this.service.book(user.id, id, dto)
  }

  @Patch('bookings/:id')
  @HttpCode(HttpStatus.OK)
  update(@CurrentUser() user: User, @Param('id', ParseIntPipe) id: number, @Body() dto: UpdateBookingDto) {
    return this.service.update(user.id, id, dto)
  }
}
