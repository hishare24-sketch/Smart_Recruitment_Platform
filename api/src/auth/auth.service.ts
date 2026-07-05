import { ConflictException, Injectable, UnauthorizedException } from '@nestjs/common'
import { JwtService } from '@nestjs/jwt'
import { InjectRepository } from '@nestjs/typeorm'
import { Repository } from 'typeorm'
import { compare, hash } from 'bcryptjs'
import { User } from '../users/user.entity'
import type { RegisterDto } from './dto/register.dto'
import type { LoginDto } from './dto/login.dto'

/** المستخدم بلا كلمة المرور — ما يُعاد للواجهة */
export type SafeUser = Omit<User, 'password' | 'assignUuid'>

@Injectable()
export class AuthService {
  constructor(
    @InjectRepository(User) private readonly users: Repository<User>,
    private readonly jwt: JwtService,
  ) {}

  async register(dto: RegisterDto): Promise<{ user: SafeUser, token: string }> {
    const exists = await this.users.findOne({ where: { email: dto.email } })
    if (exists)
      throw new ConflictException('هذا البريد مسجّل مسبقًا')

    const user = this.users.create({
      name: dto.name,
      email: dto.email,
      password: await hash(dto.password, 10),
      phone: dto.phone ?? null,
      role: dto.role ?? 'seeker',
    })
    await this.users.save(user)
    return this.withToken(user)
  }

  async login(dto: LoginDto): Promise<{ user: SafeUser, token: string }> {
    // password غير مُختار افتراضيًا (select:false) — نطلبه صراحةً للمقارنة
    const user = await this.users
      .createQueryBuilder('user')
      .addSelect('user.password')
      .where('user.email = :email', { email: dto.email })
      .getOne()
    if (!user || !(await compare(dto.password, user.password)))
      throw new UnauthorizedException('بيانات الدخول غير صحيحة')
    return this.withToken(user)
  }

  /** التوكن عديم الحالة — الخروج بإسقاط التوكن في الواجهة. */
  logout(): { message: string } {
    return { message: 'تم تسجيل الخروج' }
  }

  private withToken(user: User): { user: SafeUser, token: string } {
    const token = this.jwt.sign({ sub: user.id, uuid: user.uuid })
    return { user: this.sanitize(user), token }
  }

  private sanitize(user: User): SafeUser {
    const { password, assignUuid, ...safe } = user as User & { assignUuid: unknown }
    void password
    void assignUuid
    return safe as SafeUser
  }
}
