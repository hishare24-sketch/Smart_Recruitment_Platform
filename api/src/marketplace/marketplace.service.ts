import { Injectable, NotFoundException, type OnModuleInit } from '@nestjs/common'
import { InjectRepository } from '@nestjs/typeorm'
import { ILike, Repository } from 'typeorm'
import { Opportunity } from './opportunity.entity'
import { Application } from './application.entity'
import { MarketRequest } from './market-request.entity'
import type { RequestType } from './market-request.entity'
import type { CreateOpportunityDto } from './dto/marketplace.dto'

@Injectable()
export class MarketplaceService implements OnModuleInit {
  constructor(
    @InjectRepository(Opportunity) private readonly opps: Repository<Opportunity>,
    @InjectRepository(Application) private readonly apps: Repository<Application>,
    @InjectRepository(MarketRequest) private readonly requests: Repository<MarketRequest>,
  ) {}

  /** بذور أوليّة كي تُعيد قوائم السوق بيانات (تُدرج مرّة واحدة). */
  async onModuleInit(): Promise<void> {
    if (await this.opps.count() === 0) {
      await this.opps.save([
        this.opps.create({ title: 'مهندس واجهات أمامية', company: 'شركة أفق', location: 'الرياض', salary: '١٢٠٠٠–١٨٠٠٠', category: 'tech', skills: ['Vue', 'TypeScript'] }),
        this.opps.create({ title: 'أخصائي تحليل بيانات', company: 'داتا بلس', location: 'عن بُعد', salary: '١٥٠٠٠–٢٢٠٠٠', category: 'data', skills: ['SQL', 'Python'] }),
        this.opps.create({ title: 'مصمم تجربة مستخدم', company: 'استوديو نون', location: 'جدة', salary: '١٠٠٠٠–١٤٠٠٠', category: 'design', skills: ['Figma'] }),
      ])
    }
    if (await this.requests.count() === 0) {
      await this.requests.save([
        this.requests.create({ type: 'job', title: 'مطوّر Backend بدوام كامل', org: 'منصّة رِفد', state: 'new', compensation: 'راتب شهري', remote: true }),
        this.requests.create({ type: 'project', title: 'بناء متجر إلكتروني', org: 'متجر السنابل', state: 'reviewing', compensation: 'بالمشروع', remote: true }),
        this.requests.create({ type: 'consultation', title: 'استشارة معمارية سحابية', org: 'شركة مدى', state: 'new', compensation: 'بالساعة', remote: false }),
      ])
    }
  }

  listOpportunities(q?: string, category?: string): Promise<Opportunity[]> {
    const where: Record<string, unknown>[] = []
    if (category)
      where.push({ category })
    if (q) {
      const like = ILike(`%${q}%`)
      // بحث في العنوان أو الشركة (مع مراعاة فلتر التصنيف إن وُجد)
      const base = category ? { category } : {}
      return this.opps.find({ where: [{ ...base, title: like }, { ...base, company: like }] })
    }
    return this.opps.find({ where: where.length ? where : undefined, order: { id: 'DESC' } })
  }

  async createOpportunity(userId: number, dto: CreateOpportunityDto): Promise<Opportunity> {
    const opp = this.opps.create({
      userId,
      title: dto.title,
      company: dto.company ?? '',
      location: dto.location ?? '',
      salary: dto.salary ?? '',
      category: dto.category ?? '',
      skills: dto.skills ?? [],
    })
    return this.opps.save(opp)
  }

  async apply(userId: number, opportunityId: number): Promise<Application> {
    const opp = await this.opps.findOne({ where: { id: opportunityId } })
    if (!opp)
      throw new NotFoundException('الفرصة غير موجودة')
    const existing = await this.apps.findOne({ where: { userId, opportunityId } })
    if (existing)
      return existing
    return this.apps.save(this.apps.create({ userId, opportunityId }))
  }

  listRequests(type?: RequestType): Promise<MarketRequest[]> {
    return this.requests.find({ where: type ? { type } : undefined, order: { id: 'DESC' } })
  }

  listMyRequests(userId: number): Promise<MarketRequest[]> {
    return this.requests.find({ where: { userId }, order: { id: 'DESC' } })
  }
}
