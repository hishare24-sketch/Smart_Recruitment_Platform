import { Injectable } from '@nestjs/common'
import { InjectRepository } from '@nestjs/typeorm'
import { Repository } from 'typeorm'
import { Interview } from './interview.entity'
import type { CreateInterviewDto } from './dto/interview.dto'

@Injectable()
export class InterviewsService {
  constructor(
    @InjectRepository(Interview) private readonly interviews: Repository<Interview>,
  ) {}

  list(userId: number): Promise<Interview[]> {
    return this.interviews.find({ where: { userId }, order: { id: 'DESC' } })
  }

  create(userId: number, dto: CreateInterviewDto): Promise<Interview> {
    const interview = this.interviews.create({
      userId,
      track: dto.track,
      status: dto.status ?? 'scheduled',
      score: dto.score ?? 0,
      integrity: dto.integrity ?? {},
    })
    return this.interviews.save(interview)
  }
}
