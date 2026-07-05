import { mkdirSync } from 'node:fs'
import { dirname } from 'node:path'
import { Module } from '@nestjs/common'
import { ConfigModule, ConfigService } from '@nestjs/config'
import type { TypeOrmModuleOptions } from '@nestjs/typeorm'
import { TypeOrmModule } from '@nestjs/typeorm'

/**
 * قاعدة قابلة للتبديل بالبيئة (12-factor):
 *   - التطوير: sql.js (SQLite داخل العملية، بلا خادم ولا بناء أصلي) + synchronize.
 *   - الإنتاج/الفريق: Postgres عبر migrations.
 * لا يتغيّر أي كود بين البيئتين — فقط DB_CONNECTION.
 */
@Module({
  imports: [
    TypeOrmModule.forRootAsync({
      imports: [ConfigModule],
      inject: [ConfigService],
      useFactory: (config: ConfigService): TypeOrmModuleOptions => {
        const driver = config.get<string>('DB_CONNECTION', 'sqljs')
        if (driver === 'postgres') {
          return {
            type: 'postgres',
            host: config.get<string>('DB_HOST', '127.0.0.1'),
            port: Number(config.get('DB_PORT', 5432)),
            database: config.get<string>('DB_DATABASE', 'recruitment'),
            username: config.get<string>('DB_USERNAME', 'recruitment'),
            password: config.get<string>('DB_PASSWORD', 'secret'),
            autoLoadEntities: true,
            synchronize: false, // الإنتاج يستخدم migrations
          } as TypeOrmModuleOptions
        }
        // التطوير الافتراضي: sql.js يحفظ إلى ملف (ننشئ مجلّده أولًا)
        const file = config.get<string>('DB_SQLITE_FILE', 'data/dev.sqlite')
        mkdirSync(dirname(file), { recursive: true })
        return {
          type: 'sqljs',
          location: file,
          autoSave: true,
          autoLoadEntities: true,
          synchronize: true, // إنشاء الجداول تلقائيًا في التطوير
        } as TypeOrmModuleOptions
      },
    }),
  ],
})
export class DatabaseModule {}
