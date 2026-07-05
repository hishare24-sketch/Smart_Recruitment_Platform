import { ForbiddenException, Injectable, NotFoundException } from '@nestjs/common'
import { InjectRepository } from '@nestjs/typeorm'
import { Repository } from 'typeorm'
import { User } from '../users/user.entity'
import { Survey } from './survey.entity'
import type { CreateSurveyDto } from './dto/survey.dto'

/** حدّ عدد الاستبيانات لكل باقة — يطابق منطق التمكين في الواجهة. */
const SURVEY_LIMIT: Record<string, number> = { free: 1, pro: 10, elite: Infinity }

@Injectable()
export class SurveysService {
  constructor(
    @InjectRepository(Survey) private readonly surveys: Repository<Survey>,
    @InjectRepository(User) private readonly users: Repository<User>,
  ) {}

  list(userId: number): Promise<Survey[]> {
    return this.surveys.find({ where: { userId }, order: { id: 'DESC' } })
  }

  async create(userId: number, dto: CreateSurveyDto): Promise<Survey> {
    const user = await this.users.findOne({ where: { id: userId } })
    const limit = SURVEY_LIMIT[user?.tier ?? 'free'] ?? 1
    const count = await this.surveys.count({ where: { userId } })
    if (count >= limit)
      throw new ForbiddenException(`بلغت حدّ باقتك (${limit} استبيان). رقِّ الباقة لإنشاء المزيد.`)

    const survey = this.surveys.create({
      userId,
      title: dto.title,
      state: dto.state ?? 'draft',
      pointsPool: dto.pointsPool ?? 0,
      targeting: dto.targeting ?? {},
      questions: dto.questions ?? [],
      responses: [],
    })
    return this.surveys.save(survey)
  }

  /** إجابة مستبين — تصرف نقطة من مجمّع الاستبيان (إن بقيت نقاط). */
  async addResponse(id: number, answer: Record<string, unknown>): Promise<void> {
    const survey = await this.surveys.findOne({ where: { id } })
    if (!survey)
      throw new NotFoundException('الاستبيان غير موجود')
    survey.responses = [...survey.responses, { ...answer, at: new Date().toISOString() }]
    if (survey.pointsPool > 0)
      survey.pointsPool -= 1
    await this.surveys.save(survey)
  }
}
