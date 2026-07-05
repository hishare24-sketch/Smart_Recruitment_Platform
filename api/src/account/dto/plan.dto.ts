import { IsIn } from 'class-validator'

export type Tier = 'free' | 'pro' | 'elite'

export class SetPlanDto {
  @IsIn(['free', 'pro', 'elite'])
  tier!: Tier
}
