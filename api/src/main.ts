import { ValidationPipe, UnprocessableEntityException } from '@nestjs/common'
import type { ValidationError } from '@nestjs/common'
import { ConfigService } from '@nestjs/config'
import { NestFactory } from '@nestjs/core'
import { AppModule } from './app.module'
import { ResponseInterceptor } from './common/response.interceptor'
import { HttpExceptionFilter } from './common/http-exception.filter'

async function bootstrap() {
  const app = await NestFactory.create(AppModule)
  const config = app.get(ConfigService)

  // كل المسارات تحت /api/v1 — يطابق ../api/openapi.yaml
  app.setGlobalPrefix('api/v1')

  app.enableCors({
    origin: config.get('FRONTEND_URL', 'http://localhost:5173'),
    credentials: true,
  })

  // تحقّق المدخلات — أخطاء 422 بنمط Laravel (حقل → رسائل) كما يتوقّع عميل الواجهة
  app.useGlobalPipes(new ValidationPipe({
    whitelist: true,
    transform: true,
    exceptionFactory: (errors: ValidationError[]) => {
      const bag: Record<string, string[]> = {}
      for (const e of errors)
        bag[e.property] = Object.values(e.constraints ?? {})
      return new UnprocessableEntityException({ message: 'المدخلات غير صحيحة', errors: bag })
    },
  }))

  // غلاف استجابة موحّد { data } + فلتر أخطاء موحّد
  app.useGlobalInterceptors(new ResponseInterceptor())
  app.useGlobalFilters(new HttpExceptionFilter())

  await app.listen(config.get('PORT', 8000))
}
bootstrap()
