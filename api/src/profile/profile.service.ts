import { Injectable, NotFoundException } from '@nestjs/common'
import { InjectRepository } from '@nestjs/typeorm'
import { Repository } from 'typeorm'
import { Profile } from './profile.entity'
import type { Skill } from './profile.entity'
import type { AddProofDto, AddSkillDto, UpdateProfileDto } from './dto/profile.dto'

/** أعلى id + 1 داخل مصفوفة عناصر مرقّمة (ترقيم محلي للملف). */
function nextId(items: { id: number }[]): number {
  return items.reduce((m, i) => Math.max(m, i.id), 0) + 1
}

@Injectable()
export class ProfileService {
  constructor(
    @InjectRepository(Profile) private readonly profiles: Repository<Profile>,
  ) {}

  /** يجلب ملف المستخدم أو ينشئه فارغًا (كل مستخدم له ملف واحد). */
  async getOrCreate(userId: number): Promise<Profile> {
    let profile = await this.profiles.findOne({ where: { userId } })
    if (!profile) {
      profile = this.profiles.create({ userId })
      await this.profiles.save(profile)
    }
    return profile
  }

  async update(userId: number, dto: UpdateProfileDto): Promise<Profile> {
    const profile = await this.getOrCreate(userId)
    if (dto.headline !== undefined)
      profile.headline = dto.headline
    if (dto.summary !== undefined)
      profile.summary = dto.summary
    return this.profiles.save(profile)
  }

  async addSkill(userId: number, dto: AddSkillDto): Promise<Skill> {
    const profile = await this.getOrCreate(userId)
    const skill: Skill = {
      id: nextId(profile.skills),
      name: dto.name,
      selfLevel: dto.selfLevel,
      category: dto.category,
      // كل مهارة تبدأ بإثبات ذاتي — يطابق منطق الواجهة
      proofs: [{ id: 1, type: 'self', label: 'إثبات ذاتي', date: new Date().toISOString().slice(0, 10) }],
    }
    profile.skills = [...profile.skills, skill]
    await this.profiles.save(profile)
    return skill
  }

  async removeSkill(userId: number, skillId: number): Promise<void> {
    const profile = await this.getOrCreate(userId)
    const next = profile.skills.filter(s => s.id !== skillId)
    if (next.length === profile.skills.length)
      throw new NotFoundException('المهارة غير موجودة')
    profile.skills = next
    await this.profiles.save(profile)
  }

  async addProof(userId: number, skillId: number, dto: AddProofDto): Promise<Skill> {
    const profile = await this.getOrCreate(userId)
    const skill = profile.skills.find(s => s.id === skillId)
    if (!skill)
      throw new NotFoundException('المهارة غير موجودة')
    skill.proofs = [...skill.proofs, {
      id: nextId(skill.proofs),
      type: dto.type,
      label: dto.label,
      date: dto.date ?? new Date().toISOString().slice(0, 10),
    }]
    // إعادة الإسناد ليلتقط TypeORM تغيّر الـ JSON
    profile.skills = [...profile.skills]
    await this.profiles.save(profile)
    return skill
  }

  /** طلب إثبات وارد من زائر الصفحة العامة — يظهر لمالك الملف في /profile/proof-requests. */
  async pushProofRequest(userId: number, data: { skill: string, from: string, relation?: string }): Promise<void> {
    const profile = await this.getOrCreate(userId)
    profile.proofRequests = [...profile.proofRequests, {
      id: nextId(profile.proofRequests),
      from: data.from,
      relation: data.relation ?? '',
      skill: data.skill,
      date: new Date().toISOString().slice(0, 10),
    }]
    await this.profiles.save(profile)
  }

  async listProofRequests(userId: number) {
    const profile = await this.getOrCreate(userId)
    return profile.proofRequests
  }

  /** قبول الطلب يحوّله لتوصية على المهارة المطابقة؛ الرفض يسقطه فقط. */
  async resolveProofRequest(userId: number, id: number, accept: boolean): Promise<void> {
    const profile = await this.getOrCreate(userId)
    const reqItem = profile.proofRequests.find(r => r.id === id)
    if (!reqItem)
      throw new NotFoundException('طلب الإثبات غير موجود')
    if (accept) {
      const skill = profile.skills.find(s => s.name === reqItem.skill)
      if (skill) {
        skill.proofs = [...skill.proofs, {
          id: nextId(skill.proofs),
          type: 'endorsement',
          label: `توصية من ${reqItem.from}`,
          date: new Date().toISOString().slice(0, 10),
        }]
        profile.skills = [...profile.skills]
      }
    }
    profile.proofRequests = profile.proofRequests.filter(r => r.id !== id)
    await this.profiles.save(profile)
  }
}
