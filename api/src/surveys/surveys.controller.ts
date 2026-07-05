import { Body, Controller, Get, HttpCode, HttpStatus, Param, ParseIntPipe, Post, UseGuards } from '@nestjs/common'
import { JwtAuthGuard } from '../auth/jwt-auth.guard'
import { CurrentUser } from '../common/current-user.decorator'
import type { User } from '../users/user.entity'
import { SurveysService } from './surveys.service'
import { CreateSurveyDto } from './dto/survey.dto'

/** الاستبيانات — يطابق /surveys/* في ../api/openapi.yaml. */
@Controller('surveys')
export class SurveysController {
  constructor(private readonly surveys: SurveysService) {}

  @Get()
  @UseGuards(JwtAuthGuard)
  list(@CurrentUser() user: User) {
    return this.surveys.list(user.id)
  }

  @Post()
  @UseGuards(JwtAuthGuard)
  @HttpCode(HttpStatus.CREATED)
  create(@CurrentUser() user: User, @Body() dto: CreateSurveyDto) {
    return this.surveys.create(user.id, dto)
  }

  // إجابة المستبين — مفتوحة (يصل الرابط للمستهدَفين)
  @Post(':id/responses')
  @HttpCode(HttpStatus.CREATED)
  respond(@Param('id', ParseIntPipe) id: number, @Body() answer: Record<string, unknown>) {
    return this.surveys.addResponse(id, answer)
  }
}
