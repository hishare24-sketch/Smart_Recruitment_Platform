-- ===== 0002: عمود الملكية — تمهيد Supabase Auth =====
-- يبقى فارغًا (null) حتى يصل الدخول الحقيقي للواجهة؛ عندها:
--   1) كل صف يُربط بمالكه auth.users(id)
--   2) تُستبدل سياسات الكتابة التجريبية بشرط auth.uid() = owner_id

alter table public.public_profiles
  add column if not exists owner_id uuid references auth.users (id);

create index if not exists public_profiles_owner_idx
  on public.public_profiles (owner_id);

comment on column public.public_profiles.owner_id is
  'مالك الصف من auth.users — null في المرحلة التجريبية قبل تفعيل الدخول';
