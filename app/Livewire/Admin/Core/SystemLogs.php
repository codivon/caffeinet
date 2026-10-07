<?php

namespace App\Livewire\Admin\Core;

use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * لاگ سیستمی لاراول (تنظیمات ← لاگ سیستمی) — کامپوننت Livewire 4 [Task 3-b]
 *
 * منطق عیناً از App\Http\Controllers\Back\Admin\SystemLogsController
 * (index + data + clear + destroy) منتقل شده است — به‌جای AJAX، رندر
 * سمت سرور با فیلتر سطح/جستجو/تعداد در صفحه و صفحه‌بندی:
 *   • fileList/resolveFile/parseFile/levelStats/formatEntry — همان منطق
 *     و همان امنیت (فقط *.log داخل storage/logs؛ بدون پیمایش مسیر)
 *   • خالی‌کردن فایل (truncate) و حذف کامل فایل با همان AuditLogger و
 *     پیام‌های فارسی
 *
 * endpointهای settings/logs/data و clear/delete حفظ شده‌اند (روت عوض نشده).
 */
#[Layout('back.layouts.panel')]
class SystemLogs extends Component
{
    use WithPagination;

    /** بیشینهٔ حجم خوانده‌شده از انتهای هر فایل (مگابایت) */
    protected const MAX_READ_MB = 8;

    /** سطح‌های Monolog + برچسب فارسی و رنگ بج */
    public const LEVELS = [
        'emergency' => ['اضطراری', 'rose'],
        'alert' => ['هشدار فوری', 'rose'],
        'critical' => ['بحرانی', 'orange'],
        'error' => ['خطا', 'red'],
        'warning' => ['اخطار', 'amber'],
        'notice' => ['توجه', 'sky'],
        'info' => ['اطلاع', 'emerald'],
        'debug' => ['دیباگ', 'stone'],
    ];

    /** فایل انتخابی (نام ساده) — URL-sync */
    #[Url(as: 'file')]
    public string $file = '';

    /** فیلتر سطح */
    #[Url(as: 'level')]
    public string $level = '';

    /** جستجو در متن و stack */
    #[Url(as: 'q')]
    public string $q = '';

    /** تعداد ردیف در صفحه */
    #[Url(as: 'per_page')]
    public int $perPage = 25;

    /** ورودی انتخاب‌شده برای مودال جزئیات */
    public ?array $detail = null;

    public function mount(): void
    {
        if (! in_array($this->perPage, [10, 25, 50, 100], true)) {
            $this->perPage = 25;
        }
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function updatedFile(): void
    {
        $this->resetPage();
        $this->detail = null;
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 25, 50, 100], true)) {
            $this->perPage = 25;
        }

        $this->resetPage();
    }

    /** انتخاب فایل از لیست (URL با history push تازه می‌شود) */
    public function selectFile(string $name): void
    {
        $this->file = $name;
    }

    /** جزئیات یک ورودی (مودال) — اندیس سراسری در لیست فیلترشده */
    public function showDetail(int $idx): void
    {
        $selected = $this->resolveFile($this->file, $this->fileList());

        if ($selected === null) {
            return;
        }

        $entries = array_reverse($this->parseFile($selected['path']));
        $entries = $this->filterEntries($entries);

        $entry = $entries[$idx] ?? null;

        if ($entry !== null) {
            $this->detail = $this->formatEntry($entry);
        }
    }

    public function closeDetail(): void
    {
        $this->detail = null;
    }

    /** خالی‌کردن فایل لاگ (truncate — عیناً SystemLogsController@clear) */
    public function clearFile(): void
    {
        $files = $this->fileList();
        $selected = $this->resolveFile($this->file, $files);

        if ($selected === null) {
            $this->js("window.App?.toast ? App.toast('فایل لاگ یافت نشد.', 'error') : null");

            return;
        }

        try {
            file_put_contents($selected['path'], '');

            AuditLogger::log('settings.logs_cleared', null,
                ['file' => $selected['name'], 'size' => $selected['size']],
                ['file' => $selected['name'], 'size' => 0],
                'خالی‌کردن فایل لاگ «'.$selected['name'].'» ('.fa_number($selected['size'] / 1024).' کیلوبایت)');

            $this->js("window.App?.toast ? App.toast('فایل «".$selected['name']."» خالی شد؛ لاگ‌های جدید از همین فایل ادامه می‌یابند.', 'success') : null");
        } catch (Throwable $e) {
            $this->js("window.App?.toast ? App.toast('خالی‌کردن فایل ممکن نشد: ".addslashes($e->getMessage())."', 'error') : null");
        }
    }

    /** حذف کامل فایل لاگ (عیناً SystemLogsController@destroy) */
    public function deleteFile(): void
    {
        $files = $this->fileList();
        $selected = $this->resolveFile($this->file, $files);

        if ($selected === null) {
            $this->js("window.App?.toast ? App.toast('فایل لاگ یافت نشد.', 'error') : null");

            return;
        }

        try {
            @unlink($selected['path']);

            AuditLogger::log('settings.logs_deleted', null,
                ['file' => $selected['name'], 'size' => $selected['size']],
                ['file' => $selected['name']],
                'حذف کامل فایل لاگ «'.$selected['name'].'» ('.fa_number($selected['size'] / 1024).' کیلوبایت)');

            $this->file = '';
            $this->level = '';
            $this->q = '';
            $this->resetPage();

            $this->js("window.App?.toast ? App.toast('فایل «".$selected['name']."» حذف شد؛ در صورت نیاز لاراول دوباره می‌سازدش.', 'success') : null");
        } catch (Throwable $e) {
            $this->js("window.App?.toast ? App.toast('حذف فایل ممکن نشد: ".addslashes($e->getMessage())."', 'error') : null");
        }
    }

    public function render()
    {
        $files = $this->fileList();
        $selected = $this->resolveFile($this->file, $files);

        // نام فایل URL-نامعتبر → برگشت به پیش‌فرض (تازه‌ترین) در props
        if ($this->file !== '' && $selected === null) {
            $this->file = $selected['name'] ?? '';
        } elseif ($this->file === '' && $selected !== null) {
            $this->file = $selected['name'];
        }

        $summary = null;
        $levelStats = [];
        $rows = null;

        if ($selected !== null) {
            $entries = $this->parseFile($selected['path']);
            $levelStats = $this->levelStats($entries);
            $summary = [
                'name' => $selected['name'],
                'size_fa' => fa_number($selected['size'] / 1024),
                'modified_fa' => fa_date($selected['modified'], 'Y/m/d H:i:s'),
                'count' => count($entries),
                'count_fa' => fa_number(count($entries)),
                'truncated' => ($this->lastMeta['truncated'] ?? false),
                'total_size' => $selected['size'],
            ];

            // جدیدترین اول (خوانش طبیعی قدیمی→جدید است)
            $entries = array_reverse($entries);
            $entries = $this->filterEntries($entries);

            $total = count($entries);
            $lastPage = max(1, (int) ceil($total / $this->perPage));
            $page = min(max(1, (int) $this->getPage()), $lastPage);
            $slice = array_values(array_slice($entries, ($page - 1) * $this->perPage, $this->perPage));

            $rows = new \Illuminate\Pagination\LengthAwarePaginator(
                array_map(fn ($e) => $this->formatEntry($e), $slice),
                $total,
                $this->perPage,
                $page,
                ['path' => url(request()->getPathInfo()), 'pageName' => 'page'],
            );
        }

        return view('livewire.admin.core.system-logs', [
            'files' => $files,
            'selected' => $selected,
            'summary' => $summary,
            'levelStats' => $levelStats,
            'levelMeta' => self::LEVELS,
            'rows' => $rows,
        ])->layoutData([
            'user' => auth()->user(),
            'pageTitle' => 'لاگ سیستمی لاراول',
            'breadcrumb' => 'پنل مدیریت کل ← تنظیمات ← لاگ سیستمی',
            'htmlTitle' => 'لاگ سیستمی',
        ]);
    }

    /* --------------------------------------------------------------
    |  ابزار داخلی (عیناً از SystemLogsController)
    * -------------------------------------------------------------- */

    /** @var array|null متای آخرین parseFile (truncated) */
    protected ?array $lastMeta = null;

    /**
     * فهرست فایل‌های لاگ موجود (نام، حجم، زمان آخرین نوشت) —
     * فقط *.log داخل storage/logs؛ بدون پیمایش مسیر.
     */
    protected function fileList(): array
    {
        $dir = storage_path('logs');

        if (! is_dir($dir)) {
            return [];
        }

        $files = [];

        foreach (glob($dir.'/*.log') ?: [] as $path) {
            $name = basename($path);

            if ($name === '' || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, "\0")) {
                continue;
            }

            $files[] = [
                'name' => $name,
                'path' => $path,
                'size' => (int) @filesize($path),
                'modified' => (int) @filemtime($path),
                'modified_carbon' => Carbon::createFromTimestamp((int) @filemtime($path)),
            ];
        }

        usort($files, fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $files;
    }

    /**
     * انتخاب فایل معتبر — نام خالی → پیش‌فرض (تازه‌ترین)؛ نام ناموجود → null.
     */
    protected function resolveFile(string $name, array $files): ?array
    {
        if ($name !== '' && (str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, "\0"))) {
            return null;
        }

        if ($name === '') {
            return $files[0] ?? null;
        }

        foreach ($files as $f) {
            if ($f['name'] === $name) {
                return $f;
            }
        }

        return null;
    }

    /**
     * تجزیهٔ فایل لاگ لاراول به ورودی‌های مجزا (عیناً کنترلر).
     */
    protected function parseFile(string $path, ?array &$meta = null): array
    {
        $meta = ['truncated' => false];
        $this->lastMeta = $meta;
        $entries = [];

        try {
            $size = (int) @filesize($path);

            if ($size === 0) {
                return $entries;
            }

            $maxBytes = self::MAX_READ_MB * 1024 * 1024;

            $handle = fopen($path, 'rb');

            if ($handle === false) {
                return $entries;
            }

            if ($size > $maxBytes) {
                fseek($handle, -$maxBytes, SEEK_END);
                stream_get_line($handle, 8192, "\n");
                $meta['truncated'] = true;
            }

            $content = stream_get_contents($handle) ?: '';
            fclose($handle);
        } catch (Throwable) {
            return $entries;
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];

        $current = null;
        $header = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+([a-zA-Z0-9_.-]+)?\.([A-Za-z]+):\s?(.*)$/';

        foreach ($lines as $line) {
            if (preg_match($header, $line, $m) === 1) {
                if ($current !== null) {
                    $entries[] = $current;
                }

                $level = strtolower($m[3]);

                $current = [
                    'ts' => $m[1],
                    'env' => $m[2] ?: '—',
                    'level' => array_key_exists($level, self::LEVELS) ? $level : strtolower($level),
                    'message' => $m[4],
                    'trace' => '',
                ];

                continue;
            }

            if ($current !== null) {
                $current['trace'] .= ($current['trace'] === '' ? '' : "\n").$line;
            }
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        foreach ($entries as &$e) {
            $e['trace'] = rtrim($e['trace']);

            if (! array_key_exists($e['level'], self::LEVELS)) {
                $e['level'] = 'debug';
            }
        }
        unset($e);

        $this->lastMeta = $meta;

        return $entries;
    }

    /** اعمال فیلتر سطح + جستجو (معادل بخش data کنترلر) */
    protected function filterEntries(array $entries): array
    {
        $level = strtolower(trim($this->level));

        if ($level !== '' && array_key_exists($level, self::LEVELS)) {
            $entries = array_values(array_filter($entries, fn ($e) => $e['level'] === $level));
        }

        if ($q = trim($this->q)) {
            $entries = array_values(array_filter($entries, function ($e) use ($q) {
                return mb_stripos($e['message'], $q) !== false
                    || ($e['trace'] !== '' && mb_stripos($e['trace'], $q) !== false);
            }));
        }

        return $entries;
    }

    /** شمار ورودی‌ها به تفکیک سطح */
    protected function levelStats(array $entries): array
    {
        $stats = array_fill_keys(array_keys(self::LEVELS), 0);

        foreach ($entries as $e) {
            if (isset($stats[$e['level']])) {
                $stats[$e['level']]++;
            }
        }

        return array_filter($stats, fn ($c) => $c > 0);
    }

    /** شکل نهایی هر ورودی برای نمایش (تاریخ شمسی + رنگ بج) */
    protected function formatEntry(array $e): array
    {
        $carbon = Carbon::parse($e['ts']);

        return [
            'ts' => $e['ts'],
            'date_fa' => fa_date($carbon, 'Y/m/d'),
            'time_fa' => fa_date($carbon, 'H:i:s'),
            'env' => $e['env'],
            'level' => $e['level'],
            'level_fa' => self::LEVELS[$e['level']][0] ?? $e['level'],
            'tone' => self::LEVELS[$e['level']][1] ?? 'stone',
            'icon' => self::levelIcon($e['level']),
            'message' => mb_substr($e['message'], 0, 400),
            'trace' => $e['trace'] !== '' ? mb_substr($e['trace'], 0, 12000) : null,
        ];
    }

    /** آیکون SVG سطح (عیناً LEVEL_ICONS اسکریپت قبلی) */
    public static function levelIcon(string $level): string
    {
        $p = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"';

        return match ($level) {
            'emergency' => "<svg $p><path d='M12 9v4'/><path d='M12 17h.01'/><path d='M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z'/></svg>",
            'alert' => "<svg $p><circle cx='12' cy='12' r='10'/><path d='M12 6v6'/><path d='M12 12v4'/><path d='M12 2h0'/></svg>",
            'critical', 'warning' => "<svg $p><path d='m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z'/><path d='M12 9v4'/><path d='M12 17h.01'/></svg>",
            'error' => "<svg $p><circle cx='12' cy='12' r='10'/><path d='m15 9-6 6'/><path d='m9 9 6 6'/></svg>",
            'notice', 'info' => "<svg $p><circle cx='12' cy='12' r='10'/><path d='M12 16v-4'/><path d='M12 8h.01'/></svg>",
            default => "<svg $p><path d='M12 8V4H8'/><rect width='16' height='12' x='4' y='8' rx='2'/><path d='M2 14h2'/><path d='M20 14h2'/><path d='M15 13v2'/><path d='M9 13v2'/></svg>",
        };
    }
}
