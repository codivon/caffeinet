{{-- سلامت سیستم (فاز ۵۴) — نگاه یکجا به ورودی‌های حیاتی هاست/زمان‌بندی/صف‌ها --}}
<div x-data>
<div class="sc-stack">

    {{-- هدر --}}
    <div class="card ui-lift animate-fade-up overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">
            <span class="grid place-items-center size-14 rounded-2xl shadow-lg shrink-0 {{ $allOk ? 'bg-gradient-to-br from-emerald-400 to-emerald-700 text-white' : 'bg-gradient-to-br from-amber-400 to-amber-700 text-white' }}" aria-hidden="true">
                @if ($allOk)
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                @else
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                @endif
            </span>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg sm:text-xl font-extrabold text-stone-800 dark:text-stone-100">
                    سلامت سیستم — {{ $allOk ? 'همه‌چیز سالم است' : 'مواردی نیاز به توجه دارد' }}
                </h1>
                <p class="mt-1 text-xs sm:text-sm leading-6 text-stone-500 dark:text-stone-400">
                    وضعیت لحظه‌ای بستر اجرا، کرون، صف پیامک و سفارش‌های گیرکرده.
                    برای رفرش، صفحه را دوباره باز کنید.
                </p>
            </div>
        </div>
    </div>

    @foreach ($checks as $group)
        <div class="card p-5 animate-fade-up" wire:key="hg-{{ $loop->index }}">
            <h2 class="text-sm font-extrabold text-stone-800 dark:text-stone-100 mb-3">{{ $group['group'] }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach ($group['items'] as $item)
                    <div class="flex items-center gap-3 rounded-2xl border p-3.5 {{ $item['ok'] ? 'border-stone-200 dark:border-stone-700/70 bg-white dark:bg-stone-800/40' : 'border-rose-200 bg-rose-50 dark:!border-rose-800 dark:!bg-rose-900/20' }}" wire:key="hi-{{ $loop->parent->index }}-{{ $loop->index }}">
                        <span class="grid place-items-center size-8 rounded-xl shrink-0 {{ $item['ok'] ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400' }}" aria-hidden="true">
                            @if ($item['ok'])
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            @else
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4"/><path d="M12 16h.01"/><circle cx="12" cy="12" r="10"/></svg>
                            @endif
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-bold text-stone-400">{{ $item['label'] }}</p>
                            <p class="text-[13px] font-extrabold text-stone-800 dark:text-stone-100 truncate" title="{{ $item['value'] }}">{{ $item['value'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

</div>
</div>
