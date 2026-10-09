<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="robots" content="noindex">
    <title>آفلاین هستید — {{ config('app.name') }}</title>
    {{-- همه استایل‌ها inline تا این صفحه با یک URL (precache) کامل کار کند --}}
    {{-- v3 — رنگ‌ها از متغیرهای CSS با fallback آبی پیش‌فرض خوانده می‌شوند؛
         offline.js پوستهٔ پالت ذخیره‌شده (localStorage) و تم روشن/تاریک را تزریق می‌کند
         تا صفحهٔ آفلاین هم دقیقاً هم‌رنگ تنظیمات «ظاهر و رنگ‌بندی» باشد --}}
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; }
        body {
            font-family: Vazirmatn, Tahoma, -apple-system, sans-serif;
            background:
                radial-gradient(ellipse 70% 45% at 50% -10%, var(--of-glow, rgba(37,99,235,.26)), transparent),
                radial-gradient(ellipse 45% 32% at 85% 25%, var(--of-glow2, rgba(96,165,250,.10)), transparent),
                var(--of-bg, #eff4fb);
            color: var(--of-ink, #1e293b);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100svh;
            padding: 24px;
            text-align: center;
            -webkit-font-smoothing: antialiased;
        }
        html.dark body {
            background:
                radial-gradient(ellipse 70% 45% at 50% -10%, var(--of-glow, rgba(37,99,235,.30)), transparent),
                radial-gradient(ellipse 45% 32% at 85% 25%, var(--of-glow2, rgba(96,165,250,.12)), transparent),
                var(--of-bg-dark, #0b1220);
            color: var(--of-ink-dark, #e2e8f0);
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: var(--of-card, rgba(255,255,255,.92));
            border: 1px solid var(--of-line, rgba(148,163,184,.35));
            border-radius: 28px;
            padding: 42px 28px 34px;
            box-shadow: var(--of-shadow, 0 30px 80px rgba(15,23,42,.14)), inset 0 1px 0 var(--of-card-inset, rgba(255,255,255,.8));
            position: relative;
            overflow: hidden;
        }
        html.dark .card {
            background: var(--of-card-dark, rgba(15,23,42,.92));
            border-color: var(--of-line-dark, rgba(148,163,184,.22));
            box-shadow: 0 30px 80px rgba(0,0,0,.5), inset 0 1px 0 var(--of-card-inset-dark, rgba(255,255,255,.06));
        }
        .card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(135deg, transparent 0 9px, var(--of-stripe, rgba(37,99,235,.04)) 9px 10px);
            pointer-events: none;
        }
        .ico { position: relative; width: 104px; margin: 0 auto 8px; }
        .ico img {
            display: block;
            width: 104px;
            height: 104px;
            margin: 0 auto;
            animation: float 3.2s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(4px); }
            50%      { transform: translateY(-5px); }
        }
        h1 {
            margin: 14px 0 6px;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.02em;
        }
        p {
            margin: 0 auto;
            max-width: 32ch;
            font-size: 13.5px;
            line-height: 2;
            color: var(--of-sub, rgba(30,41,59,.62));
            font-weight: 300;
        }
        html.dark p { color: var(--of-sub-dark, rgba(226,232,240,.62)); }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 22px;
            padding: 7px 14px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.30);
            color: #dc2626;
            transition: all .4s ease;
        }
        html.dark .status { color: #fca5a5; }
        .status .dot {
            width: 8px; height: 8px; border-radius: 99px;
            background: #ef4444;
            animation: blink 1.6s ease-in-out infinite;
        }
        .status.on {
            background: rgba(52,211,153,.12);
            border-color: rgba(52,211,153,.35);
            color: #059669;
        }
        html.dark .status.on { color: #6ee7b7; }
        .status.on .dot { background: #34d399; animation: none; }
        @keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: .25; } }
        button {
            margin-top: 26px;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 14px 20px;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            background: var(--brand-grad, linear-gradient(90deg, #3b82f6, #2563eb));
            border: 0;
            border-radius: 16px;
            cursor: pointer;
            box-shadow: var(--of-btn-shadow, 0 14px 34px rgba(37,99,235,.38));
            transition: transform .25s ease, box-shadow .25s ease, opacity .25s ease;
        }
        button:hover { transform: translateY(-2px); }
        button:active { transform: translateY(0); }
        button:disabled { opacity: .55; cursor: wait; transform: none; }
        button svg { width: 16px; height: 16px; }
        button.spin svg { animation: rot 1s linear infinite; }
        @keyframes rot { to { transform: rotate(360deg); } }
        .foot {
            margin-top: 20px;
            font-size: 11px;
            color: var(--of-foot, rgba(71,85,105,.55));
            font-weight: 500;
        }
        html.dark .foot { color: var(--of-foot-dark, rgba(148,163,184,.55)); }
        @media (max-height: 640px) {
            .card { padding: 26px 22px 22px; }
            .ico { width: 84px; } .ico img { width: 84px; height: 84px; }
            h1 { font-size: 19px; } p { font-size: 12.5px; line-height: 1.8; }
        }
    </style>
    {{-- CSP پروژه اسکریپت درون‌خطی را بلاک می‌کند → فایل خارجی (پیش‌کش SW)
         v3 — در head اجرا می‌شود تا پوستهٔ پالت/تم قبل از اولین رندر اعمال شود (ضد-فلش) --}}
    <script src="{{ asset('assets/js/offline.js') }}?v=3"></script>
</head>
<body>
    <main class="card" role="main">
        <div class="ico" aria-hidden="true">
            {{-- آیکون برند (شفاف) — توسط SW پیش‌کش می‌شود و آفلاین هم می‌آید --}}
            <img src="{{ asset('icons/icon-192.png') }}" alt="" width="104" height="104">
        </div>

        <h1>اتصال اینترنت قطع است</h1>
        <p>اتصال شما قطع شده است؛ به‌محض وصل شدن دوباره، اپ همان‌جا که بودید ادامه می‌دهد.</p>

        <div class="status" id="status"><span class="dot"></span><span id="statusText">آفلاین</span></div>

        <button id="retry" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
            تلاش دوباره
        </button>

        <div class="foot">{{ config('app.name') }} · صفحه آفلاین</div>
    </main>
</body>
</html>
