import { createParamDecorator } from '@nestjs/common'
import type { ExecutionContext } from '@nestjs/common'
import type { Request } from 'express'
import type { User } from '../users/user.entity'

/**
 * @CurrentUser() — يستخرج المستخدم المصادَق (req.user الذي تضعه JwtStrategy).
 * يختصر البويلربليت في كل متحكّم محمي.
 */
export const CurrentUser = createParamDecorator(
  (_data: unknown, ctx: ExecutionContext): User => {
    const req = ctx.switchToHttp().getRequest<Request>()
    return req.user as User
  },
)
