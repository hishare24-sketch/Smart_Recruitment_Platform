import { ForbiddenException, Injectable, NotFoundException, type OnModuleInit } from '@nestjs/common'
import { InjectRepository } from '@nestjs/typeorm'
import { Repository } from 'typeorm'
import { Interviewer } from './interviewer.entity'
import { Booking } from './booking.entity'
import type { CreateBookingDto, UpdateBookingDto } from './dto/booking.dto'

@Injectable()
export class InterviewersService implements OnModuleInit {
  constructor(
    @InjectRepository(Interviewer) private readonly interviewers: Repository<Interviewer>,
    @InjectRepository(Booking) private readonly bookings: Repository<Booking>,
  ) {}

  async onModuleInit(): Promise<void> {
    if (await this.interviewers.count() === 0) {
      await this.interviewers.save([
        this.interviewers.create({ name: 'خالد العتيبي', specialty: 'tech', rating: 4.8, priceFrom: 200, availability: ['الأحد', 'الثلاثاء'] }),
        this.interviewers.create({ name: 'ريم الدوسري', specialty: 'management', rating: 4.6, priceFrom: 300, availability: ['الاثنين', 'الأربعاء'] }),
        this.interviewers.create({ name: 'سلمان الحربي', specialty: 'data', rating: 4.9, priceFrom: 250, availability: ['الخميس'] }),
      ])
    }
  }

  list(): Promise<Interviewer[]> {
    return this.interviewers.find({ order: { rating: 'DESC' } })
  }

  async book(userId: number, interviewerId: number, dto: CreateBookingDto): Promise<Booking> {
    const interviewer = await this.interviewers.findOne({ where: { id: interviewerId } })
    if (!interviewer)
      throw new NotFoundException('المقيّم غير موجود')
    const booking = this.bookings.create({
      userId,
      interviewerId,
      day: dto.day,
      slot: dto.slot,
      type: dto.type ?? '',
      status: 'pending',
      elements: dto.elements ?? [],
      report: null,
    })
    return this.bookings.save(booking)
  }

  /** تحديث الحجز — للحاجز أو للمقيّم صاحب الحساب (قبول/رفض/إكمال بتقرير). */
  async update(userId: number, bookingId: number, dto: UpdateBookingDto): Promise<Booking> {
    const booking = await this.bookings.findOne({ where: { id: bookingId } })
    if (!booking)
      throw new NotFoundException('الحجز غير موجود')
    const interviewer = await this.interviewers.findOne({ where: { id: booking.interviewerId } })
    const isOwner = booking.userId === userId
    const isInterviewer = interviewer?.userId != null && interviewer.userId === userId
    if (!isOwner && !isInterviewer)
      throw new ForbiddenException('لا صلاحية لتعديل هذا الحجز')
    if (dto.status !== undefined)
      booking.status = dto.status
    if (dto.report !== undefined)
      booking.report = dto.report
    return this.bookings.save(booking)
  }
}
