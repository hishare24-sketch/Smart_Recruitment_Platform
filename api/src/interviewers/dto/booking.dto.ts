import { IsArray, IsIn, IsObject, IsOptional, IsString, MaxLength } from 'class-validator'
import type { BookingStatus } from '../booking.entity'

export class CreateBookingDto {
  @IsString() @MaxLength(60) day!: string
  @IsString() @MaxLength(60) slot!: string
  @IsOptional() @IsString() @MaxLength(120) type?: string
  @IsOptional() @IsArray() elements?: string[]
}

export class UpdateBookingDto {
  @IsOptional()
  @IsIn(['pending', 'accepted', 'rejected', 'completed'])
  status?: BookingStatus

  @IsOptional() @IsObject() report?: Record<string, unknown>
}
