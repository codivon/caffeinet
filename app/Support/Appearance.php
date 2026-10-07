<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * سیستم ظاهر و رنگ‌بندی پنل‌ها (پوستهٔ پویا)
 *
 * ── معماری ─────────────────────────────────────────────────────────
 *  • هر پنل (admin / app / coffeenet / operator / org / front) می‌تواند
 *    پالت رنگی مستقل خودش را داشته باشد (تنظیمات ← ظاهر و رنگ‌بندی).
 *  • ۱۲ انتخاب: «پیش‌فرض» (رنگ فعلی سامانه) + ۱۰ پالت آمادهٔ زیبا +
 *    «شخصی‌سازی» که در آن همهٔ بخش‌ها (طیف برند، پس‌زمینه، سایدبار،
 *    رنگ معنایی و دمای سرد/گرم) کنترل‌پذیر است.
 *  • سرویس یک CSS کوتاه تولید می‌کند که «بعد از» theme.css تزریق می‌شود
 *    و متغیرهای رنگ (طیف blue برند، توکن‌های ui/th، سایدبار، دارک‌مود)
 *    را بازنویسی می‌کند → کل پنل با همان پالت رنگ می‌گیرد، بدون تغییر Blade.
 *  • فقط کلیدهای مجاز ذخیره می‌شوند؛ ورودی‌ها اعتبارسنجی سخت هگز دارند.
 */
final class Appearance
{
    /** پنل‌های دارای پوستهٔ مستقل */
    public const PANELS = [
        'admin'     => 'پنل مدیریت کل',
        'app'       => 'اپ مشتری',
        'coffeenet' => 'پنل کافی‌نت',
        'operator'  => 'پنل اپراتور',
        'org'       => 'پنل سازمان',
        'front'     => 'لندینگ (صفحهٔ اصلی)',
    ];

    /** کلیدهای تنظیمات هر پنل */
    public static function paletteKey(string $panel): string
    {
        return "appearance.panel.{$panel}";
    }

    public static function customKey(string $panel): string
    {
        return "appearance.custom.{$panel}";
    }

    /* ═══════════════════════════ پالت‌ها ═══════════════════════════ */

    /**
     * پالت‌های آماده — طیف ۵۰..۹۵۰ + پس‌زمینهٔ صفحه + سایدبار (۴ ایست) + متن سایدبار.
     * «default» دقیقاً همان رنگ فعلی سامانه است (آبی سافایر).
     */
    public static function palettes(): array
    {
        return [
            'default' => [
                'name' => 'پیش‌فرض — آبی سافایر',
                'hint' => 'رنگ اصلی سامانه (آبی سرمه‌ای مدرن)',
                'ramp' => self::DEFAULT_RAMP,
                'page_bg' => '#dbeafe',
                'sidebar' => ['#172f6d', '#1d3e8d', '#14295f', '#0d1d4a'],
                'sidebar_text' => '#dbeafe',
            ],
            'ocean' => [
                'name' => 'اقیانوس آرام',
                'hint' => 'فیروزه‌ای عمیق و آرامش‌بخش',
                'ramp' => ['50' => '#ecfeff', '100' => '#cffafe', '200' => '#a5f3fc', '300' => '#67e8f9', '400' => '#22d3ee', '500' => '#06b6d4', '600' => '#0891b2', '700' => '#0e7490', '800' => '#155e75', '900' => '#164e63', '950' => '#083344'],
            ],
            'emerald' => [
                'name' => 'زمرد',
                'hint' => 'سبز زمردی — حس رشد و اعتماد',
                'ramp' => ['50' => '#ecfdf5', '100' => '#d1fae5', '200' => '#a7f3d0', '300' => '#6ee7b7', '400' => '#34d399', '500' => '#10b981', '600' => '#059669', '700' => '#047857', '800' => '#065f46', '900' => '#064e3b', '950' => '#022c22'],
            ],
            'violet' => [
                'name' => 'بنفش سلطنتی',
                'hint' => 'بنفش مدرن و لاکچری',
                'ramp' => ['50' => '#f5f3ff', '100' => '#ede9fe', '200' => '#ddd6fe', '300' => '#c4b5fd', '400' => '#a78bfa', '500' => '#8b5cf6', '600' => '#7c3aed', '700' => '#6d28d9', '800' => '#5b21b6', '900' => '#4c1d95', '950' => '#2e1065'],
            ],
            'rose' => [
                'name' => 'سرخ گلگون',
                'hint' => 'رز گرم و پرانرژی',
                'ramp' => ['50' => '#fff1f2', '100' => '#ffe4e6', '200' => '#fecdd3', '300' => '#fda4af', '400' => '#fb7185', '500' => '#f43f5e', '600' => '#e11d48', '700' => '#be123c', '800' => '#9f1239', '900' => '#881337', '950' => '#4c0519'],
            ],
            'amber' => [
                'name' => 'کهربای گرم',
                'hint' => 'هویت اولیهٔ کافی‌نت — گرم و صمیمی',
                'ramp' => ['50' => '#fffbeb', '100' => '#fef3c7', '200' => '#fde68a', '300' => '#fcd34d', '400' => '#fbbf24', '500' => '#f59e0b', '600' => '#d97706', '700' => '#b45309', '800' => '#92400e', '900' => '#78350f', '950' => '#451a03'],
            ],
            'sunset' => [
                'name' => 'غروب آتشین',
                'hint' => 'نارنجی پرشور و دیدنی',
                'ramp' => ['50' => '#fff7ed', '100' => '#ffedd5', '200' => '#fed7aa', '300' => '#fdba74', '400' => '#fb923c', '500' => '#f97316', '600' => '#ea580c', '700' => '#c2410c', '800' => '#9a3412', '900' => '#7c2d12', '950' => '#431407'],
            ],
            'midnight' => [
                'name' => 'نیلی شب',
                'hint' => 'نیلی عمیق و رسمی',
                'ramp' => ['50' => '#eef2ff', '100' => '#e0e7ff', '200' => '#c7d2fe', '300' => '#a5b4fc', '400' => '#818cf8', '500' => '#6366f1', '600' => '#4f46e5', '700' => '#4338ca', '800' => '#3730a3', '900' => '#312e81', '950' => '#1e1b4b'],
            ],
            'turquoise' => [
                'name' => 'فیروزهٔ خزر',
                'hint' => 'ترکیبی از سبز و آبی — تازه و شاداب',
                'ramp' => ['50' => '#f0fdfa', '100' => '#ccfbf1', '200' => '#99f6e4', '300' => '#5eead4', '400' => '#2dd4bf', '500' => '#14b8a6', '600' => '#0d9488', '700' => '#0f766e', '800' => '#115e59', '900' => '#134e4a', '950' => '#042f2e'],
            ],
            'orchid' => [
                'name' => 'ارغوانی نئون',
                'hint' => 'سرخابی متالیک و جسور',
                'ramp' => ['50' => '#fdf4ff', '100' => '#fae8ff', '200' => '#f5d0fe', '300' => '#f0abfc', '400' => '#e879f9', '500' => '#d946ef', '600' => '#c026d3', '700' => '#a21caf', '800' => '#86198f', '900' => '#701a75', '950' => '#4a044e'],
            ],
            'coral' => [
                'name' => 'مرجانی',
                'hint' => 'ترکیب گرم نارنجی و سرخ',
                'ramp' => ['50' => '#fff5f2', '100' => '#ffe6df', '200' => '#ffcbc0', '300' => '#ffa794', '400' => '#ff7d63', '500' => '#fa5a3c', '600' => '#ee4123', '700' => '#c93118', '800' => '#a02912', '900' => '#7e2410', '950' => '#470f07'],
            ],
            'custom' => [
                'name' => 'شخصی‌سازی',
                'hint' => 'کنترل کامل رنگ همهٔ بخش‌ها + دمای سرد/گرم',
                'ramp' => self::palettes_data_default_ramp_for_custom(),
            ],
        ];
    }

    /** طیف پیش‌فرض — مرجع گزینهٔ شخصی‌سازی */
    private const DEFAULT_RAMP = ['50' => '#eff6ff', '100' => '#dbeafe', '200' => '#bfdbfe', '300' => '#93c5fd', '400' => '#60a5fa', '500' => '#3b82f6', '600' => '#2563eb', '700' => '#1d4ed8', '800' => '#1e3a8a', '900' => '#1e3a5f', '950' => '#172554'];

    /** طیف اولیهٔ گزینهٔ شخصی‌سازی (کپی از پیش‌فرض) */
    private static function palettes_data_default_ramp_for_custom(): array
    {
        return self::DEFAULT_RAMP;
    }

    public static function paletteKeys(): array
    {
        return array_keys(self::palettes());
    }

    /* ═════════════════ دادهٔ صفحهٔ تنظیمات (Blade ساده بماند) ═════════════════ */

    /**
     * دادهٔ کامل تب «ظاهر و رنگ‌بندی» — همهٔ حلقه‌ها/گرادیان‌ها از پیش ساخته می‌شوند
     * تا Blade فقط HTML ساده رندر کند (سازگاری با کامپایلر Livewire).
     */
    public static function settingsViewData(): array
    {
        $panels = self::PANELS;
        $palettes = self::palettes();

        $selected = [];
        $customs = [];
        foreach (array_keys($panels) as $key) {
            $selected[$key] = self::selectedPalette($key);
            $customs[$key] = self::customTokens($key);
        }

        // کارت‌های پالت — گرادیان سواچ از پیش ساخته‌شده
        $cards = [];
        foreach ($palettes as $key => $pal) {
            $r = $pal['ramp'];
            $cards[$key] = [
                'name' => $pal['name'],
                'hint' => $pal['hint'],
                'grad' => $key === 'custom'
                    ? 'linear-gradient(120deg, #64748b, #f59e0b, #0ea5a4, #d946ef)'
                    : 'linear-gradient(135deg, '.$r['500'].', '.$r['700'].' 55%, '.$r['900'].')',
                'is_selected' => $selected['admin'] === $key,
            ];
        }

        // سایه‌های اولیهٔ ویرایشگر شخصی‌سازی (از پالت پیش‌فرض)
        $shades = [];
        foreach (self::SHADES as $s) {
            $shades[] = ['key' => $s, 'hex' => self::DEFAULT_RAMP[$s]];
        }

        return [
            'panels' => $panels,
            'cards' => $cards,
            'selected' => $selected,
            'selected_name' => $palettes[$selected['admin']]['name'] ?? '—',
            'shades' => $shades,
            'json' => json_encode([
                'panels' => $panels,
                'palettes' => collect($palettes)->map(fn ($p) => [
                    'name' => $p['name'], 'hint' => $p['hint'], 'ramp' => $p['ramp'],
                    'page_bg' => $p['page_bg'] ?? null,
                    'sidebar' => $p['sidebar'] ?? null,
                    'sidebar_text' => $p['sidebar_text'] ?? null,
                ])->all(),
                'selected' => $selected,
                'customs' => $customs,
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /* ═════════════════ خواندن/نوشتن تنظیمات هر پنل ═════════════════ */

    /** کلید پالت انتخابی پنل (با کش ۱۰ دقیقه‌ای SettingsService) */
    public static function selectedPalette(string $panel): string
    {
        $key = app(\App\Services\Settings\SettingsService::class)
            ->get(self::paletteKey($panel), 'default');

        return in_array($key, self::paletteKeys(), true) ? $key : 'default';
    }

    /** توکن‌های شخصی‌سازی پنل (آرایه یا null) — اعتبارسنجی‌شده */
    public static function customTokens(string $panel): ?array
    {
        $raw = app(\App\Services\Settings\SettingsService::class)
            ->get(self::customKey($panel));

        return is_array($raw) ? self::sanitizeCustom($raw) : null;
    }

    /** توکن‌های نهایی پنل = پالت انتخابی (اگر custom → توکن‌های ذخیره‌شده) */
    public static function tokensFor(string $panel): array
    {
        $palette = self::selectedPalette($panel);
        $def = self::palettes()[$palette] ?? self::palettes()['default'];

        $tokens = [
            'ramp' => $def['ramp'],
            'page_bg' => $def['page_bg'] ?? $def['ramp']['100'],
            'sidebar' => $def['sidebar'] ?? self::deriveSidebar($def['ramp']),
            'sidebar_text' => $def['sidebar_text'] ?? $def['ramp']['100'],
        ];

        if ($palette === 'custom') {
            $custom = self::customTokens($panel);
            if ($custom) {
                $tokens = array_merge($tokens, $custom);
            }
        }

        return $tokens;
    }

    /** ذخیرهٔ انتخاب پنل (پالت + توکن‌های شخصی) */
    public static function save(string $panel, string $palette, ?array $custom = null): void
    {
        if (! isset(self::PANELS[$panel]) || ! in_array($palette, self::paletteKeys(), true)) {
            return;
        }

        self::putSetting(self::paletteKey($panel), $palette, 'string', 'پالت رنگی پنل '.$panel);

        if ($palette === 'custom') {
            $clean = self::sanitizeCustom($custom ?? []);
            self::putSetting(self::customKey($panel), $clean, 'json', 'توکن‌های شخصی‌سازی پنل '.$panel);
        }
    }

    private static function putSetting(string $key, mixed $value, string $cast, string $label): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'group' => 'appearance',
                'value' => $cast === 'json'
                    ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : (string) $value,
                'cast' => $cast,
                'label' => $label,
                'is_sensitive' => false,
            ],
        );

        app(\App\Services\Settings\SettingsService::class)->flush();
    }

    /* ═════════════════════ اعتبارسنجی ورودی ═════════════════════ */

    public const SHADES = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];

    /** پاک‌سازی توکن‌های شخصی — هر مقدار باید هگز معتبر باشد */
    public static function sanitizeCustom(array $in): array
    {
        $hex = fn ($v) => self::normalizeHex((string) $v);

        $out = [];

        if (! empty($in['ramp']) && is_array($in['ramp'])) {
            $ramp = [];
            foreach (self::SHADES as $s) {
                if (! empty($in['ramp'][$s]) && ($c = $hex($in['ramp'][$s]))) {
                    $ramp[$s] = $c;
                }
            }
            if (count($ramp) === 11) {
                $out['ramp'] = $ramp;
            }
        }

        if (! empty($in['page_bg']) && ($c = $hex($in['page_bg']))) {
            $out['page_bg'] = $c;
        }

        if (! empty($in['sidebar']) && is_array($in['sidebar'])) {
            $stops = [];
            foreach ($in['sidebar'] as $stop) {
                if ($c = $hex((string) $stop)) {
                    $stops[] = $c;
                }
            }
            if (count($stops) === 4) {
                $out['sidebar'] = $stops;
            }
        }

        if (! empty($in['sidebar_text']) && ($c = $hex($in['sidebar_text']))) {
            $out['sidebar_text'] = $c;
        }

        if (! empty($in['semantic']) && is_array($in['semantic'])) {
            $sem = [];
            foreach (['ok', 'warn', 'err', 'info'] as $k) {
                if (! empty($in['semantic'][$k]) && ($c = $hex($in['semantic'][$k]))) {
                    $sem[$k] = $c;
                }
            }
            if ($sem) {
                $out['semantic'] = $sem;
            }
        }

        if (isset($in['base']) && ($c = $hex($in['base']))) {
            $out['base'] = $c;
        }
        if (isset($in['temperature'])) {
            $out['temperature'] = max(-40, min(40, (int) $in['temperature']));
        }

        return $out;
    }

    /** هگز #rgb / #rrggbb → نرمال به #rrggbb (اگر معتبر نبود null) */
    public static function normalizeHex(string $value): ?string
    {
        $value = trim($value);
        if (! preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value, $m)) {
            return null;
        }
        $hex = $m[1];
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return '#'.strtolower($hex);
    }

    /* ═════════════════════ ابزار رنگ ═════════════════════ */

    /** [#rrggbb] → [r, g, b] */
    public static function toRgb(string $hex): array
    {
        $hex = ltrim(self::normalizeHex($hex) ?? '#000000', '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function toHex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', ...array_map(fn ($v) => max(0, min(255, (int) round($v))), $rgb));
    }

    /** ترکیب دو رنگ — ratio نسبت رنگ دوم */
    public static function mix(string $a, string $b, float $ratio): string
    {
        $a = self::toRgb($a);
        $b = self::toRgb($b);

        return self::toHex([
            $a[0] * (1 - $ratio) + $b[0] * $ratio,
            $a[1] * (1 - $ratio) + $b[1] * $ratio,
            $a[2] * (1 - $ratio) + $b[2] * $ratio,
        ]);
    }

    /** rgba(r, g, b, alpha) برای تزریق در CSS */
    public static function rgba(string $hex, float $alpha): string
    {
        [$r, $g, $b] = self::toRgb($hex);

        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    /** سایدبار خودکار از طیف (برای پالت‌های بدون تعریف صریح) */
    public static function deriveSidebar(array $ramp): array
    {
        $r900 = $ramp['900'] ?? $ramp['950'];

        return [
            self::mix($r900, '#ffffff', 0.08),
            $r900,
            self::mix($r900, '#000000', 0.22),
            self::mix($r900, '#000000', 0.42),
        ];
    }

    /* ═════════════════════ سازندهٔ CSS ═════════════════════ */

    /** CSS نهایی برای یک پنل (بر اساس پالت/شخصی‌سازی ذخیره‌شده) */
    public static function css(string $panel): string
    {
        $template = match ($panel) {
            'app' => 'app',
            'front' => 'front',
            default => 'back',
        };

        return self::buildCss(self::tokensFor($panel), $template);
    }

    /** ساخت CSS از توکن‌های مشخص (برای پیش‌نمایش زندهٔ صفحهٔ تنظیمات) */
    public static function buildCss(array $t, string $template = 'back'): string
    {
        $r = [];
        foreach (self::SHADES as $s) {
            $r[$s] = self::normalizeHex($t['ramp'][$s] ?? '') ?? self::palettes()['default']['ramp'][$s];
        }
        $bg = self::normalizeHex($t['page_bg'] ?? '') ?: $r['100'];
        $sb = [];
        foreach ((array) ($t['sidebar'] ?? []) as $stop) {
            if ($c = self::normalizeHex((string) $stop)) {
                $sb[] = $c;
            }
        }
        if (count($sb) !== 4) {
            $sb = self::deriveSidebar($r);
        }
        $sbt = self::normalizeHex($t['sidebar_text'] ?? '') ?: $r['100'];

        return match ($template) {
            'app' => self::cssApp($r, $bg),
            'front' => self::cssFront($r, $bg),
            default => self::cssBack($r, $bg, $sb, $sbt),
        };
    }

    /* ---------- قالب پنل‌های مدیریتی (back: admin/org/coffeenet/operator) ---------- */
    private static function cssBack(array $r, string $bg, array $sb, string $sbt): string
    {
        // سطوح تیره — خنثی با ته‌رنگ پالت (خوانایی حفظ می‌شود)
        $darkBg = self::mix('#15151f', $r['950'], 0.35);
        $darkSurf = self::mix('#20202e', $r['900'], 0.30);
        $darkTint = self::mix('#161c30', $r['900'], 0.25);
        $darkThead = self::mix('#0e1220', $r['900'], 0.35);

        $lines = [];
        $lines[] = ':root {';
        foreach (self::SHADES as $s) {
            $lines[] = "    --color-blue-{$s}: {$r[$s]};";
        }
        foreach (self::SHADES as $s) {
            $lines[] = "    --color-brand-{$s}: {$r[$s]};";
        }
        $lines = array_merge($lines, [
            "    --ui-brand: {$r['500']};",
            "    --ui-brand-600: {$r['600']};",
            "    --ui-brand-700: {$r['700']};",
            "    --ui-ink: {$r['900']};",
            "    --ui-ink-soft: {$r['600']};",
            "    --ui-warn: {$r['500']};",
            "    --ui-info: {$r['500']};",
            "    --th-bg: {$bg};",
            "    --th-surface-2: {$r['50']};",
            "    --th-ink: {$r['900']};",
            "    --th-ink-soft: {$r['600']};",
            "    --th-ink-faint: {$r['400']};",
            '}',
        ]);

        $lines[] = 'html:not(.dark) body {';
        $lines[] = '    background-color: '.$bg.';';
        $lines[] = '    background-image:';
        $lines[] = '        radial-gradient(circle at 12% -10%, '.self::rgba($r['500'], 0.07).', transparent 42%),';
        $lines[] = '        radial-gradient(circle at 88% 0%, '.self::rgba($r['600'], 0.045).', transparent 38%),';
        $lines[] = '        radial-gradient(rgba(51, 65, 85, 0.055) 1px, transparent 1.4px);';
        $lines[] = '    background-size: auto, auto, 26px 26px;';
        $lines[] = '    background-attachment: fixed;';
        $lines[] = '}';

        $lines[] = '.panel-sidebar {';
        $lines[] = '    background-image:';
        $lines[] = '        radial-gradient(ellipse 90% 34% at 50% -10%, '.self::rgba($r['400'], 0.18).', transparent 62%),';
        $lines[] = "        linear-gradient(185deg, {$sb[0]} 0%, {$sb[1]} 40%, {$sb[2]} 72%, {$sb[3]} 100%);";
        $lines[] = '    color: '.$sbt.';';
        $lines[] = '}';

        /* ---------- تیره ---------- */
        $lines[] = 'html.dark {';
        $lines = array_merge($lines, [
            "    --th-bg: {$darkBg};",
            "    --th-surface: {$darkSurf};",
            "    --th-surface-2: {$darkSurf};",
            "    --th-line: {$darkSurf};",
            "    --th-ink: {$r['100']};",
            "    --th-ink-soft: {$r['400']};",
            "    --th-ink-faint: {$r['500']};",
            "    --ui-brand: {$r['500']};",
            "    --ui-brand-600: {$r['400']};",
            "    --ui-brand-700: {$r['400']};",
            "    --ui-ink: {$r['100']};",
            "    --ui-ink-soft: {$r['400']};",
            "    --ui-line: {$darkSurf};",
            "    --color-stone-50: {$darkSurf};",
            "    --color-stone-100: {$darkBg};",
            "    --color-stone-200: {$darkSurf};",
            "    --color-stone-300: {$r['800']};",
            "    --color-stone-400: {$r['500']};",
            "    --color-stone-500: {$r['400']};",
            "    --color-stone-600: {$r['400']};",
            "    --color-stone-700: {$r['200']};",
            "    --color-stone-800: {$r['100']};",
            "    --color-stone-900: {$r['100']};",
            "    --color-stone-950: {$r['50']};",
            '}',
        ]);

        $lines[] = 'html.dark body {';
        $lines[] = '    background-color: '.$darkBg.';';
        $lines[] = '    background-image:';
        $lines[] = '        radial-gradient(circle at 15% -12%, '.self::rgba($r['400'], 0.09).', transparent 44%),';
        $lines[] = '        radial-gradient(circle at 85% -8%, '.self::rgba($r['500'], 0.06).', transparent 40%);';
        $lines[] = '    background-attachment: fixed;';
        $lines[] = '}';

        $lines = array_merge($lines, [
            'html.dark .table-panel thead th { background-color: '.self::rgba($darkThead, 0.88).' !important; }',
            'html.dark .table-panel tbody tr:hover { background-color: '.self::rgba($r['400'], 0.08).'; }',
            'html.dark ::-webkit-scrollbar-thumb { background: '.$r['800'].'; border-color: '.$darkBg.'; }',
            'html.dark ::-webkit-scrollbar-thumb:hover { background: '.$r['700'].'; }',
            'html.dark .ui-empty { border-color: '.$r['800'].'; }',
            'html.dark .ui-empty-icon { color: '.$r['500'].'; }',
            'html.dark .ui-note { border-color: '.$r['900'].'; color: '.$r['400'].'; }',
            'html.dark .bg-stone-600 { background-color: '.$r['700'].' !important; }',
            'html.dark .bg-amber-50 { background-color: '.$darkBg.' !important; }',
            'html.dark .bg-amber-100 { background-color: '.self::mix($r['950'], '#000000', 0.25).' !important; }',
            'html.dark .bg-amber-50\\/60 { background-color: '.self::rgba($darkTint, 0.6).' !important; }',
            'html.dark .bg-amber-50\\/70 { background-color: '.self::rgba($darkTint, 0.7).' !important; }',
            'html.dark .bg-amber-50\\/80 { background-color: '.self::rgba($darkTint, 0.8).' !important; }',
            'html.dark .border-amber-100 { border-color: '.self::mix($r['900'], '#000000', 0.30).' !important; }',
            'html.dark .border-amber-200 { border-color: '.$r['800'].' !important; }',
            'html.dark .border-amber-300 { border-color: '.$r['700'].' !important; }',
            'html.dark .text-amber-500 { color: '.$r['500'].' !important; }',
            'html.dark .text-amber-600 { color: '.$r['400'].' !important; }',
            'html.dark .text-amber-700 { color: '.$r['400'].' !important; }',
            'html.dark .text-amber-800 { color: '.$r['400'].' !important; }',
            'html.dark .hover\\:text-amber-500:hover { color: '.$r['500'].' !important; }',
            'html.dark .hover\\:text-amber-600:hover { color: '.$r['400'].' !important; }',
            'html.dark .hover\\:text-amber-700:hover { color: '.$r['400'].' !important; }',
            'html.dark .hover\\:bg-amber-50:hover { background-color: '.$darkBg.' !important; }',
            'html.dark .hover\\:bg-amber-50\\/60:hover { background-color: '.self::rgba($darkTint, 0.6).' !important; }',
        ]);

        return "/* پوستهٔ پویا (Appearance) — تولید خودکار؛ ویرایش دستی نکنید */\n".implode("\n", $lines)."\n";
    }

    /* ---------- قالب اپ مشتری (front/assets/css/app.css مبتنی بر توکن) ---------- */
    private static function cssApp(array $r, string $bg): string
    {
        [$ir, $ig, $ib] = self::toRgb($r['900']);

        $lines = [];
        $lines[] = ':root {';
        foreach (self::SHADES as $s) {
            $lines[] = "    --brand-{$s}: {$r[$s]};";
        }
        $lines = array_merge($lines, [
            "    --brand-grad: linear-gradient(135deg, {$r['500']} 0%, {$r['700']} 100%);",
            '    --brand-grad-soft: linear-gradient(135deg, '.self::rgba($r['500'], 0.13).', '.self::rgba($r['700'], 0.13).');',
            '    --ring: '.self::rgba($r['500'], 0.32).';',
            "    --warn-500: {$r['600']};",
            "    --warn-50: {$r['100']};",
            "    --warn-600: {$r['700']};",
            "    --paper: {$bg};",
            "    --ink-soft: {$r['700']};",
            "    --ink-faint: {$r['500']};",
            "    --line: {$r['200']};",
            "    --line-soft: {$r['100']};",
            "    --shadow-1: 0 1px 3px rgba({$ir}, {$ig}, {$ib}, .07), 0 4px 14px rgba({$ir}, {$ig}, {$ib}, .06);",
            "    --shadow-2: 0 4px 24px rgba({$ir}, {$ig}, {$ib}, .13);",
            "    --shadow-nav: 0 -6px 24px rgba({$ir}, {$ig}, {$ib}, .1);",
            '}',
        ]);

        /* حالت تیرهٔ اپ مشتری (بلوک html.dark در app.css) */
        $lines[] = 'html.dark {';
        $lines = array_merge($lines, [
            "    --brand-400: {$r['400']};",
            "    --warn-500: {$r['500']};",
            "    --ink-faint: {$r['400']};",
            '}',
        ]);

        /* انتخابگرهای هاردکد باقیمانده در app.css */
        $lines = array_merge($lines, [
            ".order-cardv2.st-amber .oc-strip { background: linear-gradient(180deg, #fbbf24, {$r['600']}); }",
            ".order-cardv2.st-orange .oc-strip { background: linear-gradient(180deg, {$r['500']}, {$r['600']}); }",
            ".svc-state--expired .sb-ico { background: linear-gradient(135deg, {$r['600']}, {$r['700']}); }",
            ".svc-flag--expired { background: {$r['600']}; color: #fff; }",
            ".ann-card::before { background: linear-gradient(90deg, {$r['500']}, {$r['600']}); }",
            ".ann-icon { background: linear-gradient(135deg, {$r['600']}, {$r['700']}); }",
            ".pf-hero-cover { background: radial-gradient(circle at 82% -30%, rgba(255,255,255,.22) 0 52px, transparent 53px), linear-gradient(135deg, {$r['600']}, {$r['900']}); }",
            ".pf-avatar { background: linear-gradient(135deg, {$r['600']}, {$r['900']}); }",
            'html.dark .s-opt.on { border-color: '.$r['600'].'; }',
            "html.dark .s-opt.on .s-opt-check { background: {$r['600']}; border-color: {$r['600']}; }",
            'html.dark .s-opt-check { border-color: '.$r['800'].'; }',
        ]);

        return "/* پوستهٔ پویا (Appearance) — اپ مشتری؛ تولید خودکار */\n".implode("\n", $lines)."\n";
    }

    /* ---------- قالب لندینگ (متغیرهای برند + پس‌زمینهٔ ملایم) ---------- */
    private static function cssFront(array $r, string $bg): string
    {
        $lines = [];
        $lines[] = ':root {';
        foreach (self::SHADES as $s) {
            $lines[] = "    --brand-{$s}: {$r[$s]};";
        }
        $lines = array_merge($lines, [
            "    --brand-grad: linear-gradient(135deg, {$r['500']} 0%, {$r['700']} 100%);",
            '    --ring: '.self::rgba($r['500'], 0.32).';',
            '}',
        ]);

        return "/* پوستهٔ پویا (Appearance) — لندینگ؛ تولید خودکار */\n".implode("\n", $lines)."\n";
    }

    /* ═════════════════ رنگ نمانیفست PWA هر پنل ═════════════════ */

    /** theme_color مانیفست = طیف ۸۰۰ پالت پنل */
    public static function manifestThemeColor(string $panel): string
    {
        return self::tokensFor($panel)['ramp']['800'] ?? '#1e3a8a';
    }
}
