import type { CallHandler, ExecutionContext, NestInterceptor } from '@nestjs/common'
import { Injectable } from '@nestjs/common'
import type { Observable } from 'rxjs'
import { map } from 'rxjs/operators'

/**
 * غلاف استجابة موحّد: كل استجابة ناجحة تُلفّ في { data } —
 * يطابق شكل العقد (../api/openapi.yaml) وما يتوقّعه عميل الواجهة.
 */
@Injectable()
export class ResponseInterceptor<T> implements NestInterceptor<T, { data: T }> {
  intercept(_ctx: ExecutionContext, next: CallHandler<T>): Observable<{ data: T }> {
    return next.handle().pipe(map(data => ({ data })))
  }
}
