import { IsEmail, IsIn, IsOptional, IsString, MaxLength, MinLength } from 'class-validator'

export class RegisterDto {
  @IsString()
  @MaxLength(255)
  name!: string

  @IsEmail({}, { message: 'صيغة البريد غير صحيحة' })
  email!: string

  @IsString()
  @MinLength(8, { message: 'كلمة المرور 8 أحرف على الأقل' })
  password!: string

  @IsOptional()
  @IsString()
  @MaxLength(32)
  phone?: string

  // كل الأدوار متاحة فورًا — فلسفة الحساب الموحّد
  @IsOptional()
  @IsIn(['seeker', 'company', 'endorser', 'interviewer', 'coach', 'trainer', 'consultant', 'admin'])
  role?: string
}
