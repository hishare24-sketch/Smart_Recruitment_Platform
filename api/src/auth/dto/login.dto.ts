import { IsEmail, IsString } from 'class-validator'

export class LoginDto {
  @IsEmail({}, { message: 'صيغة البريد غير صحيحة' })
  email!: string

  @IsString()
  password!: string
}
