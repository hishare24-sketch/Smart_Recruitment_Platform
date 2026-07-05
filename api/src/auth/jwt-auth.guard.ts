import { Injectable } from '@nestjs/common'
import { AuthGuard } from '@nestjs/passport'

/** حارس المسارات المحمية — يتطلّب Bearer token صالحًا. */
@Injectable()
export class JwtAuthGuard extends AuthGuard('jwt') {}
