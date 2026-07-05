import { IsIn, IsNumber, IsObject, IsOptional, IsString, MaxLength } from 'class-validator'
import type { InterviewTrack } from '../interview.entity'

export class CreateInterviewDto {
  @IsIn(['tech', 'management', 'design', 'data', 'support'])
  track!: InterviewTrack

  @IsOptional() @IsString() @MaxLength(60) status?: string
  @IsOptional() @IsNumber() score?: number
  @IsOptional() @IsObject() integrity?: Record<string, unknown>
}
