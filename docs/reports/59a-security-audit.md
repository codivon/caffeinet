# گزارش ممیزی امنیتی جامع — «کافی‌نت آنلاین»

**Task ID:** 59-a | **ایجنت:** security-auditor | **تاریخ:** 1405/07/18 (2026-10-10)
**مبنای ممیزی:** برنچ `agent/livewire-migration`، HEAD = `51e1270` (فاز ۵۸) + تغییرات کار در حال انجامِ همزمانِ ایجنت 59-c (فایل‌های realtime — جزئیات در بخش ۷)
**دامنه:** ۴ پنل (ادمین/سازمان/کافی‌نت/اپراتور) + اپ مشتری `/app` + API v1 + وب‌هوک + پرداخت + رسانه
**روش:** ایستا (routes/Livewire/Controllers/Services/Blade/config) + پویا (curl + tinker روی سرور توسعه `127.0.0.1:8000`) — **هیچ فایل کدی تغییر نکرده است.**

---

## ۱) خلاصهٔ مدیریتی

پایهٔ امنیتی پروژه **بالاتر از میانگین اپ‌های مشابه** است: احراز هویت چندلایه (کپچای سه‌حالته + 2FA پیامکی برای پنل‌ها، OTP هش‌شده با `hash_equals` برای مشتری)، رمزنگاری AES-256-GCM فایل‌های خصوصی، لینک‌های امضاشده برای دانلود، لاگ آدیت سراسری، CSP و هدرهای امنیتی، ownership-check یکنواخت در API v1، و کلید وب‌سرویس روی همهٔ APIها.

با این حال ممیزی **۲ یافتهٔ بحرانی، ۲ یافتهٔ بالا، ۲ متوسط و ~۸ کم/اطلاعاتی** را شناسایی کرد:

| # | شدت | یافته |
|---|------|-------|
| ۱ | ⛔ بحرانی | دور زدن کامل 2FA پیامکی و کپچا از اندپوینت‌های POST قدیمیِ ورود (`*.login.attempt`) |
| ۲ | ⛔ بحرانی | جداسازی تنانت شکسته در صفحات GET پنل کافی‌نت — ۱۱ کامپوننت Livewire پارامتر `{coffeenet}` را بدون مقایسه با سشن mount می‌کنند (IDOR بین‌شعبه‌ای: PII کارمندان/اپراتورها، سفارش‌ها، موجودی کیف) |
| ۳ | 🔴 بالا | `trustProxies(at: '*')` → جعل `X-Forwarded-For` = دور زدن همهٔ Rate limiterهای مبتنی بر IP (قفل لاگین، کپچای smart، سقف OTP، throttle مهمان API) + مسموم‌کردن IP در لاگ آدیت — **PoC واقعی** |
| ۴ | 🔴 بالا (دسترس‌پذیری) | باگ Blade `@if (! empty())` در ۳ لایهٔ پنل سازمان/کافی‌نت/اپراتور (کامیت فاز ۵۷) → **همهٔ صفحات این سه پنل 500** |
| ۵ | 🟠 متوسط | درایور پرداخت `local` (درگاه تستِ بدون اعتبارسنجی) پیش‌فرض seeder است؛ جا ماندن در پروداکشن = پرداخت/شارژ کیف جعلی |
| ۶ | 🟠 متوسط | کلیدهای API به‌صورت **متن ساده** در جدول `api_keys` ذخیره می‌شوند (نه هش) |
| ۷-۱۴ | 🟡 کم/ℹ️ | CSP با unsafe-eval/inline، توکن Sanctum بی‌انقضا + توکن در query SSE، مقایسهٔ غیر timing-safe کد 2FA، لینک فایل نامقید به کاربر، shell عمومی /app، SSRF وب‌هوک ادمین، CORS پیش‌فرض، یادآوری APP_DEBUG/SESSION_SECURE_COOKIE |

**جمع‌بندی شدت‌ها:** ⛔ بحرانی: ۲ | 🔴 بالا: ۲ | 🟠 متوسط: ۲ | 🟡 کم: ۷ | ℹ️ اطلاعاتی: ۳ (جزئیات در بخش ۳)

---

## ۲) ماتریس روت‌ها × میدل‌ویر (بررسی ایستا)

| ناحیه | میدل‌ویر | وضعیت |
|---|---|---|
| `/admin/*` (۱۸۹ روت، core/orders/entities/finance) | `admin.access` (is_active + نقش super_admin/admin + پالیسی بخش‌به‌بخش `AdminAccessPolicy`) | ✅ همهٔ روت‌ها داخل گروه محافظت‌شده؛ فقط `GET/POST admin/login` باز |
| `/organization/*` | `role:org_manager` + `org.context` (سشن org_id باید `owner_id` کاربر باشد) | ✅ |
| `/coffeenet/*` | `role:coffeenet_manager` + `coffeenet.context` (اعتبارسنجی مالکیت فعالِ سشن) | ✅ سطح روت — ⛔ اما پارامتر `{coffeenet}` در صفحات Livewire مقید نمی‌شود (یافتهٔ ۲) |
| `/operator/*` | `role:operator` + `operator.context` | ✅ (کامپوننت‌ها `authorizeOrder` دارند) |
| `/app/*` صفحات | بدون auth سمت سرور (shell عمومی؛ داده فقط از API) | ℹ️ یافتهٔ ۱۱ |
| `/api/v1/*` | `api.key` (X-Api-Key الزام؛ استثنا `health`) + `api.usage` + `auth:sanctum` + `throttle:api` روی فضای احرازشده؛ عمومی‌ها: otp (throttle اختصاصی)، geo، work-hours، vpn-status | ✅ |
| روت‌های باز عمومی | `/`، `/status`، `media/{path}`، `files/*` (امضاشده)، `payment/*` (شروع امضاشده + callback)، `realtime/stream`، `presence-offline`، PWA manifest، offline | ✅ با استثناهای مستندشده |
| CSRF-exempt | فقط `payment/callback` (بازگشت بانک) و `presence-offline` (sendBeacon) — هر دو با استدلال مستند و اثر جانبی حداقلی | ✅ |

**روت بدون auth/نقش پیدا نشد** به‌جز موارد طراحی‌شدهٔ بالا.

---

## ۳) جدول یافته‌ها (فایل:خط، PoC، پچ پیشنهادی)

### ⛔ F1 — دور زدن 2FA و کپچا از مسیر POST قدیمی ورود پنل‌ها

- **فایل:** `app/Http/Controllers/Back/Admin/AuthController.php:22-98` (login) — مشابه: `Back/Coffeenet/AuthController.php`، `Back/Org/AuthController.php`، `Back/Operator/AuthController.php`؛ روت‌ها: `routes/panels/{admin,coffeenet,org,operator}.php` → `*.login.attempt`
- **توضیح:** گیت‌های امنیتی فاز ۵۰ (کپچای سه‌حالته + 2FA پیامکی) فقط در کامپوننت‌های Livewire (`PanelLoginSecurity`) پیاده شده‌اند. چهار اندپوینت POST قدیمی (به‌عنوان «fallback») هنوز فعال‌اند و **نه کپچا می‌خواهند و نه 2FA** — فقط RateLimiter (۵/دقیقه) دارند.
- **PoC (تست شد روی سرور توسعه):**
  1. فعال‌کردن 2FA از tinker (`features.two_factor=1`) → `POST /admin/login` با `admin@caffeinet.ir / Admin@1234` → **HTTP 200** `{"message":"خوش آمدید!","redirect":"/admin"}` → `GET /admin/settings` با همان کوکی = **200** (نشست ادمین کامل، بدون کد پیامکی). در مسیر Livewire همین تلاش باید به گام کد می‌رفت.
  2. `features.captcha_mode=always` → `POST /admin/login` بدون فیلد کپچا → **422 «ایمیل یا رمز عبور اشتباه است»** (به‌جای «پاسخ کد امنیتی درست نیست») = کپچا رد نشد.
- **پیامد:** مهاجم با رمز سرقت‌شده/لو رفته، دومرحله‌ای را کلاً رد می‌کند؛ در حالت «کپچا همیشه» ربات‌گیر بی‌اثر می‌شود.
- **پچ پیشنهادی (کد):** در هر چهار کنترلر، ابتدای `login()`:
  ```php
  // بعد از RateLimiter check:
  if (! \App\Support\Captcha::verify($request->input('captcha'))) {
      return response()->json(['message' => 'پاسخ کد امنیتی درست نیست — سؤال جدید آمد.', 'captcha' => \App\Support\Captcha::current()], 422);
  }
  // و بعد از موفقیت Auth::attempt + چک نقش‌ها:
  if (app(\App\Services\Settings\SettingsService::class)->get('features.two_factor')
      && preg_match('/^09\d{9}$/', trim((string) $user->mobile))) {
      Auth::logout(); // نشست کامل نشود
      // کد بساز، SMS بفرست، uid در session بگذار و 422 با 'two_fa_required' برگردان
      // (یا ساده‌تر: fallback را کاملاً حذف کن)
  }
  ```
  **راه‌حل تمیزتر:** حذف ۴ روت `*.login.attempt` از routes/panels (کامپوننت‌های Livewire جایگزین کامل‌اند) یا ریدایرکت آن‌ها به صفحهٔ لاگین Livewire.

### ⛔ F2 — IDOR بین‌شعبه‌ای در صفحات Livewire پنل کافی‌نت (جداسازی تنانت)

- **فایل‌ها (mount بدون گارد):**
  - `app/Livewire/Coffeenet/Staff/Index.php:46` — `app/Livewire/Coffeenet/Orders/Index.php:29` — `app/Livewire/Coffeenet/Wallet/Index.php:33` — `app/Livewire/Coffeenet/Withdrawals/Index.php:36` — `app/Livewire/Coffeenet/Ratings/Index.php:60` — `app/Livewire/Coffeenet/Salaries/Index.php:53` — `app/Livewire/Coffeenet/BankCards/Index.php:26` — `app/Livewire/Coffeenet/Chats/Index.php:42` — `app/Livewire/Coffeenet/Tickets/Index.php:44` — `app/Livewire/Coffeenet/Tickets/Show.php:32` (فقط order→coffeenet چک می‌شود، نه coffeenet→session) — `app/Livewire/Coffeenet/Chats/Conversation.php:34` (همان)
- **توضیح:** میدل‌ویر `EnsureCoffeenetContext` فقط سشن را اعتبارسنجی می‌کند (کافی‌نتِ سشن باید متعلق به مدیر باشد)؛ اما پارامتر مسیر `{coffeenet}` مستقل است. الگوی صحیح فقط در ۲ کامپوننت رعایت شده (`Core/Dashboard.php:36` و `Settings/Index.php:31`):
  `abort_unless(request()->attributes->get('current_coffeenet')?->id === $coffeenet->id, 403);`
  در ۱۱ کامپوننت دیگر `mount(Coffeenet $coffeenet)` مستقیم bind می‌شود؛ `render()` داده‌های همان `$coffeenet` (پارامتر URL) را می‌سازد. همهٔ کنترلرهای POST همین پنل گارد `assertSameCoffeenet`/`$coffeenet->id !== $session->id` دارند (مثلاً `Back/Coffeenet/OrdersController.php:158`) — یعنی مسیر نوشتن بسته است و فقط **خواندن بین‌شعبه‌ای** باز.
- **PoC (تست شد):** با نشست مدیرِ شعبهٔ ۱ (کاربر آزمایشی aud.m1، مدیر فقط CN#1): `GET /coffeenet/3/staff` (CN#3 = شعبهٔ آزمایشی متعلق به دیگری) → پاسخ **500 شد** — اما عمداً به‌خاطر باگ مستقل F4 (خطای کامپایل لایه)؛ **گات‌کیپر access هیچ 403/404ای نداد** و mount/دیتاسازی CN#3 اجرا شد (نام شعبهٔ قربانی «TEST-AUDIT» در خروجی خطا ۳ بار ظاهر شد = کامپوننت به CN#3 مقید شده). با رفع F4، رندر کامل فهرست کارمندان/سفارش‌ها/موجودی کیف شعبهٔ دیگر در دسترس خواهد بود (تحلیل ایستای render() همهٔ ۱۱ کامپوننت همین را می‌سازند).
- **پیامد:** افشای بین‌تنانتی: نام/ایمیل/موبایل/حقوق کارمندان شعبهٔ دیگر، سفارش‌ها و مشتریان آن، موجودی کیف پول و برداشت‌ها.
- **پچ پیشنهادی (کد):** در ابتدای `mount()` هر ۱۱ کامپوننت:
  ```php
  public function mount(Coffeenet $coffeenet): void
  {
      abort_unless(request()->attributes->get('current_coffeenet')?->id === $coffeenet->id, 403);
      $this->coffeenet = $coffeenet;
  }
  ```
  (الگوی موجود در `Coffeenet\Core\Dashboard::mount` — کپی عیناً.)

### 🔴 F3 — جعل X-Forwarded-For همهٔ محدودیت‌های نرخ مبتنی بر IP را دور می‌زند

- **فایل:** `bootstrap/app.php:17` → `$middleware->trustProxies(at: '*');` + کلیدهای limiter مبتنی بر `request()->ip()` در `app/Providers/AppServiceProvider.php:106-128`، `app/Livewire/Auth/Concerns/PanelLoginSecurity.php`، کنترلرهای Auth پنل‌ها.
- **توضیح:** اعتماد به همهٔ پروکسی‌ها یعنی `Request::ip()` هر مقدار `X-Forwarded-For` ارسالی کلاینت را می‌پذیرد. همهٔ گیت‌ها بر «IP» کلید می‌خورند: قفل ۵تایی لاگین پنل‌ها، فعال‌شدن کپچای smart (بر اساس attempts همان کلید)، سقف ۱۰/h فوقِ OTP برای هر IP، throttle:api مهمان‌ها. با چرخاندن XFF، هر درخواست باکت تازه می‌گیرد → brute-force رمز و بمباران SMS ممکن می‌شود؛ ضمناً **لاگ آدیت IP جعلی** ثبت می‌کند (رdsnهیزی معیوب).
- **PoC (تست شد):** ۷ تلاش `POST /admin/login` با رمز غلط → تلاش ۶ و ۷ = **429**. سپس همان درخواست با هدر `X-Forwarded-For: 1.2.3.4` (و 5.6.7.8 و 9.10.11.12) → هر سه **422** عادی (باکت جدید؛ دور زدن 429) و در `audit_logs` ردیف‌های `auth.login_failed` با `ip=1.2.3.4/5.6.7.8/9.10.11.12` ثبت شد.
- **پیامد:** در هر استقراری که اپ مستقیماً (یا پشت پروکسیِ append-کنندهٔ XFF) در دسترس باشد، قفل لاگین/کپچا/سقف OTP عملاً قابل دور زدن است.
- **پچ پیشنهادی (کد):** اعتماد فقط به پروکسی واقعی:
  ```php
  // bootstrap/app.php — به‌جای '*'
  $middleware->trustProxies(at: env('TRUSTED_PROXIES') ? explode(',', env('TRUSTED_PROXIES')) : []);
  ```
  و در `.env` پروداکشن فقط CIDR/آی‌پی لودبالانسر واقعی (مثلاً `TRUSTED_PROXIES=10.0.0.0/8`). روی هاست اشتراکی بدون پروکسی شفاف، خالی بماند (پس request()->ip() = آی‌پی واقعی TCP).

### 🔴 F4 — 500 روی همهٔ صفحات پنل کافی‌نت/سازمان/اپراتور (باگ کامپایل Blade)

- **فایل:** `resources/views/back/coffeenet/layouts/panel.blade.php:161`، `resources/views/back/layouts/org.blade.php:138`، `resources/views/back/operator/layouts/panel.blade.php:167` — همه: `@if (! empty())` (بدون آرگومان)
- **توضیح:** در فاز ۵۷ سوئیچر زبان به ۵ لایه اضافه شد؛ در لایهٔ ادمین و اپ مشتری درست است (`! empty($i18nOn)`) اما در ۳ لایهٔ دیگر آرگومان `$i18nOn` جا افتاده → `empty()` بدون آرگومان = **ParseError در ویوی کامپایل‌شده** → هر رندر صفحهٔ این سه پنل 500 (تأیید داینامیک: `view('back.coffeenet.layouts.panel')->render()` → «syntax error, unexpected token ")"»؛ و `GET /coffeenet/1/staff` با نشست معتبر = 500). حملهٔ امنیتی نیست اما **کل دسترس‌پذیری سه پنل ستافی را از کار می‌اندازد** و PoC یافتهٔ F2 را هم پوشانده (به همین دلیل PoC کاملِ خواندن بین‌شعبه‌ای رندر نشد).
- **پچ پیشنهادی (کد):** در هر ۳ فایل:
  ```blade
  @if (! empty($i18nOn))
  ```

### 🟠 F5 — درگاه تست `local` پیش‌فرض پرداخت است؛ verify آن هیچ اعتبارسنجی ندارد

- **فایل:** `database/seeders/SettingsSeeder.php:126` (`payment.driver=local`) + `app/Services/Customer/PaymentGatewayService.php:55` + `vendor/shetabit/multipay/src/Drivers/Local/Local.php:64` (verify فقط «transactionId موجود و cancel=خیر» را چک می‌کند) + `app/Http/Controllers/Front/App/PaymentController.php:68` (callback بدون auth، CSRF-exempt).
- **توضیح:** جریان خودِ کد سالم است: مبلغ verify همیشه از DB (`$payment->amount`) می‌آید نه از ورودی، و start فقط با لینک امضاشده باز می‌شود. اما اگر در پروداکشن درایور روی `local` بماند، هر کاربر احرازشده می‌تواند callback را جعل کند:
  **PoC (تحلیلی — روی dev که درگاه test واقعی است):** `POST /payment/callback` با `transactionId=<ref_id یک Payment معلق>` بدون `cancel` → status=Success → `markOrderPaid`/`markWalletCharged` (اعتبار کیف از هیچ!). DEPLOYMENT.md اشاره‌ای به تعویض درایور ندارد (grep = 0 نتیجه).
- **پچ پیشنهادی (کد):** در `PaymentGatewayService::handleCallback` سطر اول:
  ```php
  if (app()->environment('production') && $this->driverName() === 'local') {
      abort(403, 'درگاه تست در محیط عملیاتی غیرفعال است.');
  }
  ```
  + افزودن بند «تعویض payment.driver» به چک‌لیست DEPLOYMENT.md و نمایش هشدار در داشبورد ادمین وقتی driver=local در production است.

### 🟠 F6 — کلیدهای API به‌صورت متن ساده در دیتابیس

- **فایل:** `app/Models/ApiKey.php:28` (`where('key', $provided)` در `VerifyApiKey.php:44`)، مایگریشن `2026_10_10_000001_create_api_keys_table.php`، تزریق کلید داخلی: `resources/views/partials/vpn-modal.blade.php` (meta api-key — طراحی‌شده).
- **توضیح:** نشت DB/بکاپ = استفادهٔ مستقیم همهٔ کلیدها. کلید تولیدی crypto-secure است (`cnk_`+40 hex) ولی در resting state unprotected است (برخلاف OTP که هش می‌شود).
- **پچ پیشنهادی (کد):** ستون `key_hash` (SHA-256) + جستجو با هش؛ مقدار خام فقط یک‌بار در لحظهٔ ساخت نمایش داده شود:
  ```php
  // VerifyApiKey
  $apiKey = ApiKey::query()->where('key_hash', hash('sha256', $provided))->first();
  // هنگام ساخت: $hash = hash('sha256', $plain);  // $plain فقط یک‌بار نمایش
  ```

### 🟡 یافته‌های کم

| # | یافته | فایل:خط | پچ |
|---|---|---|---|
| F7 | CSP اجازهٔ `'unsafe-eval'` + `'unsafe-inline'` به script-src می‌دهد (الزام Livewire 4/Alpine — مستندشده)؛ XSS موفق را می‌تواند اجرا کند | `app/Http/Middleware/SecurityHeaders.php:53-55` | مهاجرت تدریجی به nonce (Blade directive `@csp-nonce`) و حذف eval با ارتقای Livewire |
| F8 | توکن‌های Sanctum بدون انقضا (`expiration=null`) و توکن اپ مشتری در query استریم SSE (`?token=`) → ماندگاری توکن در access-log | `config/sanctum.php:53`، `app/Http/Controllers/RealtimeStreamController.php:34` | مقدار `expiration` (مثلاً 60×24×30) + برای SSE: بلیت کوتاه‌عمر یک‌بارمصرف (ذخیره در cache، TTL 30s) به‌جای توکن خام |
| F9 | مقایسهٔ کد 2FA با `!==` (غیر timing-safe) | `app/Livewire/Auth/Concerns/PanelLoginSecurity.php:169` | `hash_equals($expect, trim($this->twoFaCode))` (الگوی OtpService:142) |
| F10 | لینک‌های امضاشدهٔ دانلود فایل به کاربر مقید نیستند (۶ ساعت، قابل اشتراک/لو رفتن از Referer) | `routes/web.php:57-64` + `Front/App/{Files,ChatFiles,TicketFiles}Controller.php` | کاهش TTL (۶۰ دقیقه) یا افزودن `auth()->check()` به دانلودها (اپ وب نشست دارد) |
| F11 | صفحات `/app/*` برای مهمان 200 shell می‌دهند؛ احراز فقط سمت کلاینت (داده‌ای منتشر نمی‌شود) | `routes/panels/app.php:29-58` | میدل‌ویر `auth` سفارشی که مهمان را به `/app/auth` ریدایرکت کند (دفاع عمقی + UX) |
| F12 | SSRF وب‌هوک: مدیر می‌تواند URL دلخواه (شامل داخلی) ثبت کند؛ `Http::post` با آن | `app/Services/Webhooks/WebhookDispatcher.php:95` | بلاک IPهای خصوصی در `Webhook::url` (validate با `filter_var(FILTER_FLAG_NO_PRIV_RANGE|NO_RES_RANGE)`) — ریسک کم چون admin-only |
| F13 | CORS پیش‌فرض فریم‌ورک (`*` روی api/*) + stateful domains شامل localhost؛ API بر Bearer است پس ریسک CSRF/نشت کوکی حاد نیست | `config/cors.php` (publish نشده) | publish و مقیدکردن `allowed_origins` به دامنهٔ رسمی در پروداکشن |
| F14 | `.env` توسعه: `APP_DEBUG=true` → پاسخ‌های خطای API شامل exception/file/trace (تست شد در 404 سفارش) — چک‌لیست دپلوی APP_DEBUG=false را دارد؛ `SESSION_SECURE_COOKIE` خالی است (پروداکشن HTTPS باید true شود)؛ `LOG_LEVEL=debug` | `.env:4,49`، `DEPLOYMENT.md:111-112` | در پروداکشن: `APP_DEBUG=false`، `SESSION_SECURE_COOKIE=true`، `LOG_LEVEL=warning` |

### ℹ️ اطلاعاتی

- **ℹ1:** کلید داخلی API در `<meta name="api-key">` صفحات تزریق می‌شود (طراحی‌شده — گیت کلاینت‌ساید، حفاظت واقعی همان auth:sanctum/throttle است).
- **ℹ2:** آواتار عمومی بر اساس user id (`/app/avatar/{user}`) — طراحی‌شده برای چت.
- **ℹ3:** ترتیب میدل‌ویر: برای مهمان‌های API، پیام 401 از `auth:sanctum` می‌آید نه `api.key` (SubstituteBindings در گروه api باعث جابه‌جایی Authenticate به قبل از VerifyApiKey می‌شود) — از نظر امنیتی بی‌اثر (هر دو 401)؛ فقط پیام خطا با مستندات متفاوت است.

---

## ۴) موارد «تست شد و سالم بود» (پوشش ممیزی)

| مورد | نتیجه |
|---|---|
| مهمان → `/admin/*`، `/coffeenet/{id}/dashboard`، `/operator`، `/organization` | **302 به لاگین همان پنل** (نه 200/500) — `/admin/settings`→302✓، `/coffeenet/1/dashboard`→302✓، `/operator/dashboard`→302✓، `/organization`→302✓؛ روت‌های ناموجود →404 |
| `/api/v1/health` بدون کلید | **200** `{"ok":true,...}` ✓ |
| `/api/v1/{me,orders,wallet,notifications}` بدون کلید / کلید bogus | **401 JSON** (پیام auth برای مهمان؛ برای کاربر احرازشده `invalid_api_key` — ماتریس ۵حالته تست شد؛ ترکیب «توکن معتبر + بدون کلید» → 401 «کلید ارسال نشده» ✓) |
| لاگ مصرف API (`api.usage`) | ۶۰ ردیف ثبت‌شده؛ آخرین ردیف: `PATCH /api/v1/family/1 ip=127.0.0.1 status=404 uid=9` ✓ |
| IDOR با دو کاربر واقعی (کاربر دوم با OTP dev ساخته شد) | `GET /api/v1/orders/13` (مالک: کاربر ۷) با توکن کاربر ۹ → **404**؛ `/orders/13/messages` → **404**؛ `/tickets/1` (مالک: کاربر ۶) → **404**؛ `PATCH /api/v1/family/1` → **404** ✓ (الگوی authorizeOwner/404 ضد-enum در همهٔ کنترلرهای API ایستا هم تأیید شد) |
| قفل لاگین پنل (POST) | ۵ تلاش → 422، تلاش ۶-۷ → **429 «تلاش‌های بیش از حد؛ ۵۶ ثانیه»** ✓ (اما جعل XFF آن را دور می‌زند — F3) |
| کپچا (سمت سرور) | پاسخ در session، یک‌بارمصرف (`session()->forget` پس از verify)، TTL ۶۰۰s، حالت‌های off/always/smart با RateLimiter؛ verify سمت سرور در هر ۴ کامپوننت لاگین (`passCaptchaGate` در همه) ✓ — تست داینامیک مسیر Livewire انجام نشد (پروتکل wire)؛ مسیر POST = یافتهٔ F1 |
| 2FA پیامکی (مسیر Livewire — ایستا) | کد یک‌بارمصرف (Cache::forget پس از موفقیت)، TTL ۵دقیقه، کول‌داون resend ۶۰s، قفل ۵ تلاش، گام دوم سشن‌محور ✓ — مسیر POST = یافتهٔ F1 |
| OTP مشتری (API) | کد هش‌شده (sha256 با app.key)، مقایسهٔ timing-safe `hash_equals`، TTL ۳دقیقه، ۵ تلاش، کول‌داون ۹۰ثانیه‌ای (تست: درخواست دوم → 422 «کد قبلی معتبر است») ، throttle 2/min هر موبایل + 10/h هر IP (تست: 200→422→**429**)، حذف کدهای قبلی هنگام درخواست جدید ✓ |
| کپی پنل‌های دیگر | لاگین Livewire هر ۴ پنل: کپچا+2FA+`session()->regenerate()` ✓ (ایستا) |
| کوکی نشست | `httponly; samesite=lax` + encrypt (تست روی Set-Cookie)؛ regenerate در هر دو مسیر ورود؛ `invalidate` در خروج و حساب غیرفعال ✓ |
| هدرهای امنیتی صفحهٔ لاگین | `X-Frame-Options: SAMEORIGIN`، `X-Content-Type-Options: nosniff`، `Referrer-Policy: strict-origin-when-cross-origin`، `Permissions-Policy`، `X-Permitted-Cross-Domain-Policies: none`، **CSP کامل** (default-src self, object-src none, frame-ancestors self, base-uri self, form-action self) ✓ |
| فایل‌های حساس وب | `/.env`، `/.env.backup`، `/.git/config`، `/composer.json`، `/vendor/composer/installed.json` → **404**؛ `/storage/logs/laravel.log(.0)` و `/storage/logs/laravel.db` → **403** (php -S مسیرهای خارج docroot از symlink را می‌بندد؛ روی پروداکشن هم `storage/logs` خارج `storage/app/public` است و از `/storage/*` سرو نمی‌شود) ✓ |
| مسیر پیمایش رسانه | `/media/services/../../.env`، `..%2F..%2F.env`، `%2e%2e%2f...`، `.../../../../composer.json` → همگی **404** ✓ (+ دفاع ایستا: whitelist پیشوند، ممنوعیت `..`/NUL/`:?#`، whitelist پسوند (svg ممنوع)، بررسی realpath) |
| پرداخت — مبلغ/امضا | لینک شروع `hasValidSignature()` ✓؛ مبلغ verify از DB نه ورودی ✓؛ idempotency (Success → بدون پردوبار) ✓؛ پرداخت کیف اتمیک در تراکنش + چک مالکیت سفارش (`customer_id !== user->id → 403`) ✓ |
| فایل‌های خصوصی | دیسک `local` + AES-256-GCM (CNENC1) + لینک امضاشده ۶ساعته + لاگ آدیت دانلود ✓ |
| آپلودها | آواتار: `image|mimes:jpeg,jpg,png,webp|max:2048` + بازانکودینگ اجباری GD→WebP (کشتن payload) ✓؛ پیوست تیکت: whitelist پسوند (بدون html/svg) + ۱۵MB + mime از کلاینت فقط برای Content-Type (nosniff + attachment برای غیر image) ✓؛ چت: whitelist mimes تصویر/صدا/ویدیو ✓ |
| SQL خام | همهٔ `selectRaw/DB::raw/whereRaw` با مقادیر ثابت/enum (`WorkTrendService.groupExpr` از map داخلی) — هیچ ورودی کاربر interpolate نمی‌شود ✓ |
| خروجی unescaped Blade | ۱۶ نقطهٔ `{!! !!}`: همه SVG ثابت/JSON-LD/کپچا (htmlspecialchars داخل Captcha::svg) /CSS پویا با sanitize هگز `^#([0-9a-fA-F]{3}|{6})$`؛ فقط بدنهٔ راهنماها (`guide/show`) محتوای admin-managed است ( trusted content) ✓ |
| وب‌هوک | امضای HMAC-SHA256 با `hash_hmac('sha256', $body, $secret)` + هدر `X-Webhook-Signature` ✓ (سویهٔ ارسال‌کننده؛ `hash_equals` سمت گیرندهٔ بیرونی است و در این کدبَرَس وجود ندارد — صحیح). لاگ delivery + timeout 4s + try/catch کامل ✓ |
| دسترسی نقش‌ها | `AdminSectionAccess` (بخش‌به‌بخش با AdminAccessPolicy + Gate::before super_admin)، `EnsureRole` (403 برای نقش غلط، 302 برای مهمان)، contextهای سه پنل با چک مالکیت سشن ✓ |
| نوشتن بین‌شعبه‌ای (منفی‌تست) | `POST /coffeenet/3/orders/1/accept` با نشست مدیر شعبهٔ ۱ → **403 «کافی‌نت مسیر با جلسه فعلی شما مطابقت ندارد»** ✓ (کنترلرها گارد دارند — فقط صفحات Livewire ندارند: F2) |
| mass assignment | هیچ الگوی `->update($this->…)`/`fill()` با پراپرتی‌های عمومی Livewire؛ پراپرتی‌های public فقط فیلتر/آی‌دی‌های مقیدشده ✓ |
| exec/کامند | `CompressionService` و `UploadConfig`: `escapeshellarg` روی همهٔ آرگومان‌ها ✓ |

---

## ۵) نکتهٔ پوشش داینامیک

اکشن‌های تغییر وضعیت Livewire (مودال‌ها/اکشن‌های کامپوننت) با curl قابل درخواست نیستند (پروتکل snapshot+checksum) — این نقاط با **review ایستا** پوشش داده شد (mount/render/authorize همهٔ ۷۰+ کامپوننت اسکن شد؛ یافتهٔ F2 حاصل همین اسکن است). سایر نقاط non-Livewire (API v1، POST fallbackها، media/files/payment، صفحات GET پنل‌ها) به‌صورت پویا تست شدند.

## ۶) ترتیب پیشنهادی اکشن (برای فاز بعدی)

1. **F4** (یک‌خطی ×۳ فایل) — پنل‌های ستافی همین حالا 500 می‌دهند.
2. **F1** — حذف/محافظت ۴ اندپوینت POST ورود.
3. **F2** — افزودن گارد یک‌خطی به ۱۱ کامپوننت کافی‌نت.
4. **F3** — محدودسازی trustProxies با env.
5. F5/F6 (پرداخت/کلید API) قبل از دپلوی پروداکشن.
6. موارد 🟡 در بک‌لاگ امنیتی.

## ۷) یادداشت محیطی

- سرور توسعه و دیتابیس دست‌نخورده بازگردانده شدند: ردیف‌های آزمایشی settings به مقدار قبلی برگشت (`captcha_mode=smart`، `two_factor=0`)، کاربران/شعبهٔ آزمایشی (aud.*, TEST-AUDIT، کاربران OTP تست) حذف شدند، limiterها clear شدند. ردیف‌های `audit_logs` مربوط به تلاش‌های تست لاگین باقی‌اند (ردپای ممیزی).
- در جریان ممیزی، تغییرات کدِ **کار در حال انجام ایجنت‌های موازی** (59-c: `RealtimeStreamController`، `SseService`، `realtime.js v8`، `config/realtime.php`، bump نسخه‌های ۴ لایه) در working tree ظاهر شد — این گزارش به HEAD `51e1270` مبناست؛ یافتهٔ F4 در HEAD و worktree هر دو برقرار است.
