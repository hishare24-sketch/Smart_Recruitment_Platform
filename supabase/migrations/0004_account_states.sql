-- ===== 0004: جدول الحالات الخاصة — مستند لكل (مستخدم × مخزن) =====
-- يخدم محرك المزامنة (src/services/cloudSync.ts — الوضع الخاص syncPrivateDoc):
-- المحفظة، الباقة، الرسائل، ... كل مخزن Pinia خاص يُحفظ كمستند jsonb واحد.
-- التصميم موثّق في DOC/CLOUD_SYNC.md (قرار «مزامنة مستند» ومسار الخروج العلائقي).

create table if not exists public.account_states (
  owner_id uuid not null references auth.users (id) on delete cascade,
  store text not null,
  data jsonb not null,
  updated_at timestamptz not null default now(),
  primary key (owner_id, store)
);

comment on table public.account_states is
  'حالة مخازن Pinia الخاصة لكل مستخدم — data تطابق شكل حالة المخزن في الواجهة';

alter table public.account_states enable row level security;

-- بيانات خاصة بحتة: لا يقرؤها ولا يكتبها إلا صاحبها — لا استثناءات تجريبية هنا
drop policy if exists "account_states_select" on public.account_states;
create policy "account_states_select"
  on public.account_states for select
  using (owner_id = auth.uid());

drop policy if exists "account_states_insert" on public.account_states;
create policy "account_states_insert"
  on public.account_states for insert
  with check (owner_id = auth.uid());

drop policy if exists "account_states_update" on public.account_states;
create policy "account_states_update"
  on public.account_states for update
  using (owner_id = auth.uid())
  with check (owner_id = auth.uid());

drop policy if exists "account_states_delete" on public.account_states;
create policy "account_states_delete"
  on public.account_states for delete
  using (owner_id = auth.uid());
