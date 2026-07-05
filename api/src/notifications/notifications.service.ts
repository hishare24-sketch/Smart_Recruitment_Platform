import { Injectable } from '@nestjs/common'
import { InjectRepository } from '@nestjs/typeorm'
import { Repository } from 'typeorm'
import { Notification } from './notification.entity'
import type { NotificationCategory } from './notification.entity'

@Injectable()
export class NotificationsService {
  constructor(
    @InjectRepository(Notification) private readonly notifications: Repository<Notification>,
  ) {}

  async list(userId: number): Promise<Notification[]> {
    const count = await this.notifications.count({ where: { userId } })
    if (count === 0) {
      // إشعار ترحيبي عند أول وصول
      await this.push(userId, {
        icon: 'mdi-hand-wave',
        title: 'مرحبًا بك في منظومة التوظيف الذكية',
        body: 'أكمِل ملفك لترفع درجة ثقتك وتظهر للجهات.',
        category: 'system',
        actionTo: '/profile',
      })
    }
    return this.notifications.find({ where: { userId }, order: { id: 'DESC' } })
  }

  /** إنشاء إشعار — يُستدعى داخليًا من التدفقات (حجز/قبول/رسالة…). */
  push(userId: number, data: {
    icon?: string
    title: string
    body?: string
    category?: NotificationCategory
    actionTo?: string
  }): Promise<Notification> {
    const n = this.notifications.create({
      userId,
      icon: data.icon ?? 'mdi-bell',
      title: data.title,
      body: data.body ?? '',
      category: data.category ?? 'system',
      actionTo: data.actionTo ?? null,
      read: false,
    })
    return this.notifications.save(n)
  }

  async markAllRead(userId: number): Promise<void> {
    await this.notifications.update({ userId, read: false }, { read: true })
  }
}
