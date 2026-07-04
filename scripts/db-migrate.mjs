// ===== مُرحِّل قاعدة Supabase — يطبّق ملفات supabase/migrations الجديدة مباشرة =====
// التشغيل: npm run db:migrate
// الرمز: SUPABASE_ACCESS_TOKEN من البيئة أو من .env.local (غير متتبع)
// التتبع: جدول public._migrations يسجّل ما طُبّق فلا يتكرر.

import { readdirSync, readFileSync } from 'node:fs'

const PROJECT_REF = 'maqogxdksjpibsnkskiq'
const API = `https://api.supabase.com/v1/projects/${PROJECT_REF}/database/query`

function loadToken() {
  if (process.env.SUPABASE_ACCESS_TOKEN)
    return process.env.SUPABASE_ACCESS_TOKEN.trim()
  try {
    const env = readFileSync(new URL('../.env.local', import.meta.url), 'utf8')
    const m = env.match(/^SUPABASE_ACCESS_TOKEN=(.+)$/m)
    if (m)
      return m[1].trim()
  }
  catch { /* لا .env.local — نكمل للفشل الواضح أدناه */ }
  console.error('❌ SUPABASE_ACCESS_TOKEN غير موجود (في البيئة أو .env.local)')
  process.exit(1)
}

const token = loadToken()

async function query(sql) {
  const res = await fetch(API, {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
    body: JSON.stringify({ query: sql }),
  })
  if (!res.ok)
    throw new Error(`HTTP ${res.status}: ${await res.text()}`)
  return res.json()
}

// جدول تتبّع الترحيلات
await query(`
  create table if not exists public._migrations (
    name text primary key,
    applied_at timestamptz not null default now()
  )`)

const appliedRows = await query('select name from public._migrations')
const applied = new Set((Array.isArray(appliedRows) ? appliedRows : []).map(r => r.name))

const dir = new URL('../supabase/migrations/', import.meta.url)
const files = readdirSync(dir).filter(f => f.endsWith('.sql')).sort()

let ran = 0
for (const file of files) {
  if (applied.has(file)) {
    console.log(`= ${file} (مطبَّق سابقًا)`)
    continue
  }
  const sql = readFileSync(new URL(file, dir), 'utf8')
  await query(sql)
  await query(`insert into public._migrations (name) values ('${file.replace(/'/g, '\'\'')}')`)
  console.log(`+ ${file} ✅`)
  ran++
}

console.log(ran ? `اكتمل الترحيل — ${ran} ملف جديد.` : 'القاعدة محدَّثة — لا جديد.')
