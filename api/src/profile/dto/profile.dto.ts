import { IsArray, IsIn, IsInt, IsObject, IsOptional, IsString, Max, MaxLength, Min } from 'class-validator'

export class UpdateProfileDto {
  @IsOptional()
  @IsString()
  @MaxLength(255)
  headline?: string

  @IsOptional()
  @IsString()
  @MaxLength(2000)
  summary?: string

  // الوثيقة الخاصة الكاملة — المخزن يحفظ اللقطة كلها في نداء واحد (يقابل persist المحلي)
  @IsOptional() @IsArray() skills?: Array<Record<string, unknown>>
  @IsOptional() @IsArray() experiences?: Array<Record<string, unknown>>
  @IsOptional() @IsArray() certificates?: Array<Record<string, unknown>>
  @IsOptional() @IsObject() prefs?: Record<string, unknown>
}

export class AddSkillDto {
  @IsString()
  @MaxLength(120)
  name!: string

  @IsInt()
  @Min(1)
  @Max(5)
  selfLevel!: number

  @IsOptional()
  @IsString()
  @MaxLength(120)
  category?: string
}

export class AddProofDto {
  @IsIn(['assessment', 'endorsement', 'project', 'certificate', 'self'])
  type!: 'assessment' | 'endorsement' | 'project' | 'certificate' | 'self'

  @IsString()
  @MaxLength(255)
  label!: string

  @IsOptional()
  @IsString()
  date?: string
}

export class ResolveProofRequestDto {
  @IsIn([true, false])
  accept!: boolean
}
