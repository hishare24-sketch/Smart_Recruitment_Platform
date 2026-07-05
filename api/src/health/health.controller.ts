import { Controller, Get } from '@nestjs/common'

/** نقطة صحّة بسيطة — GET /api/v1/health */
@Controller('health')
export class HealthController {
  @Get()
  check() {
    return { status: 'ok', service: 'smart-recruitment-api' }
  }
}
