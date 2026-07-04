-- ===== 0005: تفعيل Realtime — البثّ اللحظي بين أجهزة المستخدم =====
-- يضيف جدولَي المزامنة إلى نشرة supabase_realtime فيبثّ Supabase أي تغيير صف
-- عبر WebSocket. سياسات RLS تبقى نافذة على البثّ: كل اتصال لا يستقبل إلا
-- الصفوف التي يملك قراءتها (صفوف صاحب الحساب / الصفحات العامة).
-- الطبقة العميلة: src/services/cloudSync.ts (اشتراك postgres_changes).

do $$
begin
  if not exists (
    select 1 from pg_publication_tables
    where pubname = 'supabase_realtime' and schemaname = 'public' and tablename = 'account_states'
  ) then
    alter publication supabase_realtime add table public.account_states;
  end if;
  if not exists (
    select 1 from pg_publication_tables
    where pubname = 'supabase_realtime' and schemaname = 'public' and tablename = 'public_profiles'
  ) then
    alter publication supabase_realtime add table public.public_profiles;
  end if;
end $$;

-- REPLICA IDENTITY FULL: يضمن وصول الصف كاملًا في أحداث UPDATE/DELETE
-- ويجعل الفلترة على الأعمدة (owner_id / slug) موثوقة في البثّ.
alter table public.account_states replica identity full;
alter table public.public_profiles replica identity full;
