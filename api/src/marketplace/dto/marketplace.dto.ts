import { IsArray, IsOptional, IsString, MaxLength } from 'class-validator'

export class CreateOpportunityDto {
  @IsString() @MaxLength(255) title!: string
  @IsOptional() @IsString() @MaxLength(255) company?: string
  @IsOptional() @IsString() @MaxLength(255) location?: string
  @IsOptional() @IsString() @MaxLength(120) salary?: string
  @IsOptional() @IsString() @MaxLength(120) category?: string
  @IsOptional() @IsArray() skills?: string[]
}
