{{-- «نظرسنجی‌های مشتریان» پنل کافی‌نت — نسخهٔ Livewire 4 [Task 4]
     ساختار و کلاس‌ها عیناً از back/admin/ratings/index.blade.php (نسخهٔ کافی‌نت:
     بدون فیلتر کافی‌نت و بدون مدیریت گزینه‌ها) با آمار/لیست سمت سرور و
     فیلترهای wire:model.live. مدیریت گزینه‌ها فقط مدیر کل — همان admin می‌ماند.

     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div>

    {{-- ============ چیپ‌های آماری ============ --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
        <div class="card p-4 flex items-center gap-3 animate-fade-up" role="status">
            <span class="grid place-items-center size-11 rounded-2xl bg-amber-100 text-amber-600 shrink-0">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-stone-500">کل نظرات ثبت‌شده</p>
                <p class="text-xl font-extrabold tabular-nums text-amber-700">{{ fa_digits((string) $stats['total']) }} <span class="text-[10px] font-medium">نظر</span></p>
            </div>
        </div>

        <div class="card p-4 flex items-center gap-3 animate-fade-up delay-1" role="status">
            <span class="grid place-items-center size-11 rounded-2xl bg-emerald-100 text-emerald-600 shrink-0">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-stone-500">میانگین امتیاز کافی‌نت ({{ $coffeenet->name }})</p>
                <p class="text-xl font-extrabold tabular-nums text-emerald-700">{{ $stats['avg'] !== null ? fa_digits((string) $stats['avg']) : '—' }} <span class="text-[10px] font-medium">از ۵</span></p>
            </div>
        </div>

        <button type="button" wire:click="filterByRating('with_operator')"
                class="stat-chip card p-4 flex items-center gap-3 text-right animate-fade-up delay-2 {{ $rating === 'with_operator' ? 'ring-2 ring-sky-300' : '' }}">
            <span class="grid place-items-center size-11 rounded-2xl bg-teal-100 text-teal-600 shrink-0">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm18 0h-3a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-5Z"/><path d="M21 11a9 9 0 0 0-18 0"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-stone-500">میانگین امتیاز اپراتورها</p>
                <p class="text-xl font-extrabold tabular-nums text-teal-700">{{ $stats['avg_operator'] !== null ? fa_digits((string) $stats['avg_operator']) : '—' }} <span class="text-[10px] font-medium">از ۵</span></p>
            </div>
        </button>

        <button type="button" wire:click="filterByRating('low')"
                class="stat-chip card p-4 flex items-center gap-3 text-right animate-fade-up delay-3 {{ $rating === 'low' ? 'ring-2 ring-sky-300' : '' }}">
            <span class="grid place-items-center size-11 rounded-2xl bg-rose-100 text-rose-600 shrink-0">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-stone-500">نظرات منفی (۱ و ۲ ستاره)</p>
                <p class="text-xl font-extrabold tabular-nums text-rose-600">{{ fa_digits((string) $stats['low']) }} <span class="text-[10px] font-medium">نظر</span></p>
            </div>
        </button>
    </div>

    {{-- ============ توزیع امتیاز + برترین دلایل ============ --}}
    <div class="grid lg:grid-cols-3 gap-4 mb-5">
        <div class="card p-5 animate-fade-up delay-1 lg:col-span-2">
            <h2 class="text-sm font-extrabold text-stone-800 mb-4">توزیع امتیاز کلی (کافی‌نت)</h2>
            @php $maxDist = max(1, max($stats['distribution'])); @endphp
            <div class="space-y-2.5" role="list" aria-label="توزیع امتیازها">
                @foreach ([5, 4, 3, 2, 1] as $stars)
                    <div class="rt-dist-row">
                        <button type="button" class="rt-dist-label" wire:click="filterByRating('{{ $stars }}')" title="فقط نظرات {{ fa_digits((string) $stars) }} ستاره">{{ fa_digits((string) $stars) }} ستاره</button>
                        <div class="rt-dist-bar {{ $stars <= 2 ? 'rt-bad' : '' }}">
                            <span style="width: {{ $stats['total'] ? round(($stats['distribution'][$stars] / $maxDist) * 100) : 0 }}%"></span>
                        </div>
                        <span class="rt-dist-count tabular-nums">{{ fa_digits((string) $stats['distribution'][$stars]) }}</span>
                    </div>
                @endforeach
            </div>
            <p class="text-[11px] text-stone-400 mt-3">روی هر ردیف کلیک کنید تا لیست همان امتیاز فیلتر شود</p>
        </div>

        <div class="card p-5 animate-fade-up delay-2">
            <h2 class="text-sm font-extrabold text-stone-800 mb-4">پرتکرارترین دلایل</h2>
            <div class="space-y-2">
                @forelse ($stats['top_reasons'] as $title => $count)
                    <button type="button" wire:click="filterByReason('{{ addslashes($title) }}')" wire:key="reason-{{ md5($title) }}"
                            class="w-full flex items-center justify-between gap-2 rounded-xl bg-stone-50 hover:bg-stone-100 px-3.5 py-2.5 text-right transition-colors">
                        <span class="text-xs font-bold text-stone-600 truncate">{{ $title }}</span>
                        <span class="text-[10px] font-extrabold text-blue-700 tabular-nums shrink-0">{{ fa_digits((string) $count) }}×</span>
                    </button>
                @empty
                    <p class="text-xs text-stone-400 text-center py-4">هنوز دلیلی ثبت نشده است</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ============ جدول نظرات ============ --}}
    <div class="card animate-fade-up delay-2 overflow-hidden">
        <div class="adm-card-head adm-card-head-stacked rt-filters">
            <div class="rt-search-row">
                <svg class="rt-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" placeholder="جستجو در نظرسنجی‌ها: شماره سفارش، نام یا موبایل مشتری، متن دیدگاه…"
                       wire:model.live.debounce.350ms="q" class="rt-search-input" autocomplete="off">
            </div>

            <div class="flex flex-wrap items-center gap-2 rt-filter-row">
                <select wire:model.live="rating" class="field !py-2.5 !text-xs !w-auto min-w-40" aria-label="فیلتر امتیاز کلی">
                    <option value="">همهٔ امتیازها</option>
                    <option value="5">۵ ستاره</option>
                    <option value="4">۴ ستاره</option>
                    <option value="3">۳ ستاره</option>
                    <option value="2">۲ ستاره</option>
                    <option value="1">۱ ستاره</option>
                    <option value="high">عالی (۴+)</option>
                    <option value="low">ضعیف (۲ و کمتر)</option>
                    <option value="with_operator">دارای امتیاز اپراتور</option>
                    <option value="with_comment">دارای متن دیدگاه</option>
                </select>

                <select wire:model.live="operatorRating" class="field !py-2.5 !text-xs !w-auto min-w-36" aria-label="فیلتر امتیاز اپراتور">
                    <option value="">امتیاز اپراتور: همه</option>
                    <option value="5">اپراتور ۵★</option>
                    <option value="4">اپراتور ۴★</option>
                    <option value="3">اپراتور ۳★</option>
                    <option value="2">اپراتور ۲★</option>
                    <option value="1">اپراتور ۱★</option>
                    <option value="high">اپراتور عالی (۴+)</option>
                    <option value="low">اپراتور ضعیف (۲-)</option>
                </select>

                <select wire:model.live="operatorId" class="field !py-2.5 !text-xs !w-auto min-w-36" aria-label="فیلتر اپراتور">
                    <option value="">همهٔ اپراتورها</option>
                    @foreach ($operators as $op)
                        <option value="{{ $op['id'] }}">{{ $op['name'] }}</option>
                    @endforeach
                </select>

                <select wire:model.live="optionId" class="field !py-2.5 !text-xs !w-auto min-w-36" aria-label="فیلتر دلیل">
                    <option value="">همهٔ دلایل</option>
                    @foreach ($options as $option)
                        <option value="{{ $option->id }}">{{ $option->title }}</option>
                    @endforeach
                </select>

                <select wire:model.live="sort" class="field !py-2.5 !text-xs !w-auto min-w-32" aria-label="مرتب‌سازی">
                    <option value="newest">جدیدترین</option>
                    <option value="worst">بدترین امتیاز</option>
                    <option value="best">بهترین امتیاز</option>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-2 rt-filter-row">
                <div class="flex items-center gap-1.5">
                    <input type="date" wire:model.live="from" class="field !py-2.5 !text-xs !w-auto" aria-label="از تاریخ">
                    <span class="text-[11px] text-stone-400">تا</span>
                    <input type="date" wire:model.live="to" class="field !py-2.5 !text-xs !w-auto" aria-label="تا تاریخ">
                </div>
                <button type="button" wire:click="clearFilters" class="btn-ghost ui-press !py-2.5 !px-4 !text-xs">پاک‌سازی فیلترها</button>
            </div>
        </div>

        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                    <thead>
                    <tr>
                        <th>امتیاز کافی‌نت</th>
                        <th>امتیاز اپراتور</th>
                        <th>دلایل</th>
                        <th>دیدگاه مشتری</th>
                        <th>مشتری</th>
                        <th>اپراتور</th>
                        <th>سفارش</th>
                        <th>تاریخ</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="rating-{{ $row['id'] }}" class="group">
                            <td>
                                @if ($row['rating'] !== null)
                                    <div class="rt-stars {{ \App\Livewire\Coffeenet\Support\Ui::starsClass($row['rating']) }}" role="img" aria-label="{{ fa_digits((string) $row['rating']) }} از ۵">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="{{ $i <= $row['rating'] ? 'on' : '' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>
                                        @endfor
                                        <b class="rt-stars-num">{{ fa_digits((string) $row['rating']) }}</b>
                                    </div>
                                @else
                                    <span class="text-[11px] text-stone-300">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['operator_rating'] !== null)
                                    <div class="rt-stars {{ \App\Livewire\Coffeenet\Support\Ui::starsClass($row['operator_rating']) }}" role="img" aria-label="{{ fa_digits((string) $row['operator_rating']) }} از ۵">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="{{ $i <= $row['operator_rating'] ? 'on' : '' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>
                                        @endfor
                                        <b class="rt-stars-num">{{ fa_digits((string) $row['operator_rating']) }}</b>
                                    </div>
                                @else
                                    <span class="text-[11px] text-stone-300">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1 max-w-56">
                                    @forelse ($row['options'] as $o)
                                        <span class="rt-chip {{ $o['type'] === 'neg' ? 'rt-chip--neg' : '' }}">{{ $o['title'] }}</span>
                                    @empty
                                        <span class="text-[11px] text-stone-300">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="max-w-52">
                                @if ($row['comment'])
                                    <p class="rt-comment" title="{{ $row['comment'] }}">{{ $row['comment'] }}</p>
                                @else
                                    <span class="text-[11px] text-stone-300">—</span>
                                @endif
                            </td>
                            <td>
                                <p class="text-xs font-semibold text-stone-700">{{ $row['customer_name'] }}</p>
                                @if ($row['customer_mobile'])
                                    <p class="text-[10px] text-stone-400 font-mono" dir="ltr">{{ $row['customer_mobile'] }}</p>
                                @endif
                            </td>
                            <td class="text-xs text-stone-600">{{ $row['operator_name'] ?? '—' }}</td>
                            <td>
                                <a href="{{ $row['view_url'] }}" wire:navigate class="text-[11px] font-mono text-amber-700 hover:underline" dir="ltr" title="فهرست سفارش‌ها با جستجوی همین شماره">{{ $row['order_number'] }}</a>
                            </td>
                            <td class="text-[11px] text-stone-400 whitespace-nowrap">{{ $row['rated_at_fa'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="!py-10">
                            <div class="ui-empty">
                                <span class="ui-empty-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>
                                </span>
                                <p class="text-xs font-bold text-stone-500">نظری یافت نشد</p>
                                <p class="text-[11px] text-stone-400 mt-1">با تغییر فیلترها دوباره تلاش کنید.</p>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="adm-table-foot">
            <p class="text-[11px] text-stone-400">
                @if ($rows->total())
                    نمایش {{ fa_digits((string) $rows->firstItem()) }} تا {{ fa_digits((string) $rows->lastItem()) }} از {{ fa_digits((string) $rows->total()) }} نظر
                @else
                    —
                @endif
            </p>
            {{ $rows->links('livewire.coffeenet.partials.pagination') }}
        </div>
    </div>

    @include('livewire.coffeenet.partials.toaster')
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pages/ratings.css') }}?v=2">
@endpush
