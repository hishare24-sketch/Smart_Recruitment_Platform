import { IsArray, IsIn, IsInt, IsObject, IsOptional, IsString, MaxLength, Min } from 'class-validator'
import type { SurveyState } from '../survey.entity'

export class CreateSurveyDto {
  @IsString() @MaxLength(255) title!: string

  @IsOptional()
  @IsIn(['draft', 'pending', 'active', 'paused', 'closed', 'archived'])
  state?: SurveyState

  @IsOptional() @IsInt() @Min(0) pointsPool?: number
  @IsOptional() @IsObject() targeting?: Record<string, unknown>
  @IsOptional() @IsArray() questions?: Array<Record<string, unknown>>
}
