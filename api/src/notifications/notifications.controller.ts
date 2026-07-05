import { Controller, Get, HttpCode, HttpStatus, Post, UseGuards } from '@nestjs/common'
import { JwtAuthGuard } from '../auth/jwt-auth.guard'
import { CurrentUser } from '../common/current-user.decorator'
import type { User } from '../users/user.entity'
import { NotificationsService } from './notifications.service'

/** الإشعارات — يطابق /notifications في ../api/openapi.yaml. */
@Controller('notifications')
@UseGuards(JwtAuthGuard)
export class NotificationsController {
  constructor(private readonly notifications: NotificationsService) {}

  @Get()
  list(@CurrentUser() user: User) {
    return this.notifications.list(user.id)
  }

  @Post('read-all')
  @HttpCode(HttpStatus.NO_CONTENT)
  readAll(@CurrentUser() user: User) {
    return this.notifications.markAllRead(user.id)
  }
}
