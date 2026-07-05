import type { ArgumentsHost, ExceptionFilter } from '@nestjs/common'
import { Catch, HttpException, HttpStatus, Logger } from '@nestjs/common'
import type { Response } from 'express'

/**
 * فلتر أخطاء موحّد: يخرج كل خطأ بشكل ثابت { message, errors? } —
 * أخطاء التحقّق 422 تحمل خريطة (حقل → رسائل) بنمط Laravel.
 */
@Catch()
export class HttpExceptionFilter implements ExceptionFilter {
  private readonly logger = new Logger('Http')

  catch(exception: unknown, host: ArgumentsHost) {
    const res = host.switchToHttp().getResponse<Response>()

    if (exception instanceof HttpException) {
      const status = exception.getStatus()
      const body = exception.getResponse()
      if (typeof body === 'object' && body !== null)
        return res.status(status).json(body)
      return res.status(status).json({ message: body })
    }

    this.logger.error(exception)
    return res
      .status(HttpStatus.INTERNAL_SERVER_ERROR)
      .json({ message: 'حدث خطأ في الخادم' })
  }
}
