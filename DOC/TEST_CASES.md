# سجلّ حالات الاختبار (Test Cases Registry)

> ملفّ حيّ. كلّ **حالة** = سيناريو (Given/When/Then) مربوط باختباره الآليّ (أو مُعلَّم كفجوة تُنفَّذ).
> يمنع CI (م0) دمج ما يكسر أيّ اختبار مربوط. من هذا السجلّ تُشتقّ اليونت/الفيتشر تست.
> راجع [DOC/QUALITY_SYSTEM_PLAN.md](QUALITY_SYSTEM_PLAN.md).

## المفاتيح
- **الحالة:** ✅ مؤتمَت (مربوط باختبار) · 🟡 جزئيّ · ⬜ فجوة (يُنفَّذ)
- **النوع:** `U` وحدويّ (دالة/متجر نقيّ، vitest/Pest) · `F` فيتشر (endpoint عبر HTTP، Pest) · `E` E2E (Playwright، لاحقًا)
- **الأولويّة:** 🔴 حرِج · 🟠 مهمّ · ⚪ عاديّ

## اصطلاح كتابة اليونت تست (كيف تُنفَّذ)
- **واجهة — منطق نقيّ/خدمات** (`src/services`, `src/utils`, `src/composables`): `vitest` — استدعاء مباشر + `expect`. مثال قائم: `src/services/matchProfile.test.ts`.
- **واجهة — متاجر Pinia:** `createPinia()/setActivePinia()` ثمّ تأكيد الحالة/الأفعال. مثال: `src/stores/ProfileStore.test.ts`.
- **باك-إند — endpoints/خدمات:** Pest feature مع `RefreshDatabase` + `Sanctum::actingAs` + `Http::fake` لمزوّدي الذكاء. مثال: `backend/tests/Feature/Api/AssistantTest.php`.
- **قاعدة:** كلّ سطر «⬜ فجوة» يُصبح اختبارًا في الملفّ المشار إليه، فيتحوّل لـ✅ ويحرسه CI.

---

## 1) المصادقة والوصول (Auth & Access)
| ID | الحالة (Given/When/Then) | نوع | أولويّة | حالة | الاختبار |
|----|--------------------------|-----|--------|------|----------|
| AUTH-01 | دخول بصلاحيّات صحيحة → توكن + مستخدم | F | 🔴 | ✅ | `backend/tests/Feature/Api/AuthTest.php` |
| AUTH-02 | دخول ببيانات خاطئة → **401** لا 500، وبلا توكن | F | 🔴 | ✅ | ↑ |
| AUTH-03 | التوكن يصادِق `me`، والخروج ينجح (إبطال كامل → E2E لاحقًا) | F | 🔴 | ✅ | ↑ |
| AUTH-06 | نقطة مصادَقة بلا توكن → 401 | F | 🟠 | ✅ | ↑ |
| AUTH-04 | `/logout` يمسح الجلسة العالقة ويوجّه للدخول (يكسر فخّ الحارس) | U/E | 🟠 | ⬜ | `src/router/__tests__/guards.test.ts` |
| AUTH-05 | `/dev/login` معطّل في الإنتاج (`import.meta.env.DEV=false`) | U | 🟠 | ⬜ | ↑ |

## 2) المطابقة والفرز (المسار B)
| ID | الحالة | نوع | أولويّة | حالة | الاختبار |
|----|--------|-----|--------|------|----------|
| MATCH-01 | `matchScore` يرجّح المرشّح الأقوى ويطبّع على المفاتيح المتوفّرة | U | 🟠 | ✅ | `src/services/matching.test.ts` |
| MATCH-02 | `dominantSector` يتحمّل مهارات undefined/فارغة بلا رمي | U | 🔴 | ✅ | `src/services/matchProfile.test.ts` |
| MATCH-03 | `explain` بلا مفتاح → heuristic (`live:false`) بأسباب ومهارات متطابقة | F | 🟠 | ✅ | `backend/tests/Feature/Api/Admin/AdminMatchTest.php` |
| MATCH-04 | `explain` بمزوّد حيّ (Http::fake) → `live:true` + حكم/درجة من الـLLM | F | 🟠 | ✅ | ↑ |
| MATCH-05 | `explain` عند فشل المزوّد → fallback موسوم (`meta.fallback`) | F | 🟠 | ✅ | ↑ |
| MATCH-06 | `explain` يتطلّب صلاحيّة (غير الأدمن → 403) | F | 🟠 | ✅ | ↑ |

## 3) «لماذا أنا مطابق؟» (واجهة الباحث)
| ID | الحالة | نوع | أولويّة | حالة | الاختبار |
|----|--------|-----|--------|------|----------|
| WHY-01 | why-match بلا مفتاح → شرح heuristic (`live:false`) | F | 🟠 | ✅ | `backend/tests/Feature/Api/WhyMatchTest.php` |
| WHY-02 | why-match بمزوّد حيّ → `live:true` + درجة/حكم | F | 🟠 | ✅ | ↑ |
| WHY-03 | يتطلّب مصادقة (401 بلا توكن) | F | 🟠 | ✅ | ↑ |
| WHY-04 | تحقّق العنوان مطلوب (422) | F | ⚪ | ✅ | ↑ |

## 4) المساعد الذكيّ (المسار C)
| ID | الحالة | نوع | أولويّة | حالة | الاختبار |
|----|--------|-----|--------|------|----------|
| ASST-01 | رسالة تُنشئ محادثة وتُثبِّت الردّ + الميتا | F | 🟠 | ✅ | `backend/tests/Feature/Api/AssistantTest.php` |
| ASST-02 | ذاكرة المحادثة: الرسالة الثانية تحمل الأدوار السابقة للمزوّد | F | 🟠 | ✅ | ↑ |
| ASST-03 | أدوات function-calling (Claude) تجيب من بيانات مبذورة + تجمع التوكن | F | 🟠 | ✅ | ↑ |
| ASST-04 | أدوات function-calling (OpenAI) — نفس المسار | F | 🟠 | ✅ | ↑ |
| ASST-05 | الأدوات الشخصيّة تُحجب حين `data_access=false` (خصوصيّة) | F | 🔴 | ✅ | ↑ |
| ASST-06 | فشل/رفض المزوّد → محاكاة موسومة (لا انقطاع) | F | 🟠 | ✅ | ↑ |
| ASST-07 | حجب الحصّة اليوميّة مهذّب مع إتاحة التصعيد | F | 🟠 | ✅ | ↑ |

## 5) ويدجت الشات (المرحلة 1)
| ID | الحالة | نوع | أولويّة | حالة | الاختبار |
|----|--------|-----|--------|------|----------|
| CHAT-01 | `nudgeRoute` يربط الإجراء بالوجهة الصحيحة (profile/opportunities/…) | U | 🟠 | ⬜ | `src/utils/chatLinks.test.ts` |
| CHAT-02 | `linkifyParts` يقسّم الروابط بأمان (بلا HTML) | U | 🟠 | ⬜ | ↑ |
| CHAT-03 | `ChatWidgetStore`: open/unread/markRead/bumpUnread (لا يزيد وهو مفتوح) | U | ⚪ | ⬜ | `src/stores/ChatWidgetStore.test.ts` |
| CHAT-04 | فتح الويدجت من الـFAB + إرسال + ردّ + زرّ nudge ينقل لقسم | E | 🟠 | ⬜ | e2e |

## 6) متانة وضع الـAPI الحقيقيّ (صنف «الشاشة الفارغة»)
| ID | الحالة | نوع | أولويّة | حالة | الاختبار |
|----|--------|-----|--------|------|----------|
| RAPI-01 | `skillConfidence` بلا مصفوفة proofs → 0 بلا رمي | U | 🔴 | ✅ | `src/stores/ProfileStore.test.ts` |
| RAPI-02 | `sectorForSkill(undefined)` → undefined بلا رمي | U | 🔴 | ✅ | `src/services/sectors.test.ts` |
| RAPI-03 | تحميل `/analytics` و`/dashboard` بأشكال بيانات ناقصة → يُصيّر بلا شاشة فارغة | E | 🔴 | ⬜ | e2e (م5) |

## 7) الأدمن والحوكمة
| ID | الحالة | نوع | أولويّة | حالة | الاختبار |
|----|--------|-----|--------|------|----------|
| ADM-01 | أوزان المطابقة تُقرأ/تُحدَّث + حالة تعزيز الذكاء | F | 🟠 | ✅ | `AdminMatchTest.php` |
| ADM-02 | بلاغ جديد يبثّ `moderation.created` لقناة `admin.governance`؛ التكرار لا يعيد البثّ | F | 🟠 | ✅ | `AdminGovernanceTest.php` |
| ADM-03 | الشاشات الستّ (customization/settings/ai/chat/pipeline/matching) تُصيّر بجلسة صحيحة | E | 🟠 | ⬜ | e2e (م5) |

---

## ملخّص التغطية (يُحدَّث)
- **مؤتمَت الآن:** المطابقة B · why-match · المساعد C · متانة real-API (الدوال) · أساسيّات الأدمن — عبر **313 اختبارًا** (274 واجهة + 39 باك) يحرسها CI.
- **الفجوات ذات الأولويّة (التالية):** AUTH-01..05 · CHAT-01..03 (وحدويّ سريع) · الفئة E2E (م5) — خاصّة RAPI-03/ADM-03 (صنف الشاشة الفارغة).
