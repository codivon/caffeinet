{{-- نشست‌های فعال (فاز ۵۰) — دستگاه‌های واردشده با همین حساب + خروج ریموت
     از «تنظیمات ← نشست‌های فعال» باز می‌شود. --}}
<div x-data>
<style>[x-cloak]{display:none!important}</style>
<div class="sc-stack">

    {{-- ================== هدر ================== --}}
    <div class="card ui-lift animate-fade-up overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">
            <span class="grid place-items-center size-14 rounded-2xl bg-gradient-to-br from-stone-800 to-stone-900 text-sky-200 shadow-lg shadow-stone-900/20 shrink-0" aria-hidden="true">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="8" x="5" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 18h.01"/><path d="M10 18h.01"/></svg>
            </span>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg sm:text-xl font-extrabold text-stone-800 dark:text-stone-100">نشست‌های فعال</h1>
                <p class="mt-1 text-xs sm:text-sm leading-6 text-stone-500 dark:text-stone-400">
                    همهٔ دستگاه‌ها و مرورگرهایی که الان با حساب شما وارد پنل شده‌اند.
                    اگر دستگاهی آشنا نیست، از همهٔ دستگاه‌های دیگر خارج شوید و رمز را عوض کنید.
                </p>
            </div>
            <div class="shrink-0">
                <button type="button" wire:click="logoutOthers"
                        @click="window.PanelUI ? PanelUI.confirm({ title: 'خروج از سایر دستگاه‌ها؟', desc: 'نشست همین دستگاه حفظ می‌شود و بقیه در اولین کلیک لاگ‌اوت می‌شوند.', okText: 'بله، خارج شوند', cancelText: 'انصراف', danger: true, icon: 'warn' }, () => $wire.logoutOthers()) : $wire.logoutOthers()"
                        class="btn-ghost !py-2.5 !px-4 !text-xs text-rose-600 hover:!bg-rose-50 dark:text-rose-400 dark:hover:!bg-rose-900/20 ui-press whitespace-nowrap">
                    <svg class="inline-block size-4" style="vertical-align:-3px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                    خروج از سایر دستگاه‌ها
                </button>
            </div>
        </div>
    </div>

    {{-- پیام موفقیت --}}
    @if (session('ps-ok'))
        <div class="card p-3 sm:p-4 border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm font-bold animate-fade-up dark:!bg-emerald-900/30 dark:!border-emerald-800 dark:!text-emerald-300" role="status">{{ session('ps-ok') }}</div>
    @endif

    {{-- ================== فهرست دستگاه‌ها ================== --}}
    <div class="card p-5 animate-fade-up" style="animation-delay:.05s">
        <h2 class="text-sm font-extrabold text-stone-800 dark:text-stone-100 mb-4">دستگاه‌ها ({{ fa_number($sessions->count()) }})</h2>

        <div class="flex flex-col gap-3">
            @forelse ($sessions as $s)
                <div class="flex items-center gap-3 rounded-2xl border border-stone-200 dark:border-stone-700/70 bg-white dark:bg-stone-800/40 p-4 {{ $s->is_current ? 'ring-2 ring-emerald-400/60' : '' }}" wire:key="ps-{{ $s->id }}">
                    <span class="grid place-items-center size-9 rounded-xl shrink-0 bg-stone-100 dark:bg-stone-700/60 text-stone-500 dark:text-stone-300" aria-hidden="true">
                        @if (str_contains($s->device, 'موبایل') || str_contains($s->device, 'آیفون'))
                            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                        @else
                            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                        @endif
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-extrabold text-sm text-stone-800 dark:text-stone-100">{{ $s->device }}</span>
                            <span class="text-[11px] font-bold text-stone-400">{{ $s->browser }}</span>
                            @if ($s->is_current)
                                <span class="badge bg-emerald-100 text-emerald-700 dark:!bg-emerald-900/40 dark:!text-emerald-300">این دستگاه</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-[11px] text-stone-400 font-medium">
                            آخرین فعالیت: <bdi dir="ltr">{{ $s->last }}</bdi>
                            <span class="mx-1">·</span>
                            IP: <bdi dir="ltr">{{ $s->ip }}</bdi>
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-center text-sm text-stone-400 py-6 font-bold">نشستی یافت نشد.</p>
            @endforelse
        </div>
    </div>

</div>
</div>
