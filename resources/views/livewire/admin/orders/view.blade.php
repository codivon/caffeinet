{{-- «صفحهٔ کامل جزئیات سفارش» پنل مدیریت کل — نسخهٔ Livewire 4 [Task 3-c]
     ساختار و کلاس‌ها عیناً از renderDetail() نسخهٔ JS (show.js) و ویوی
     back/admin/orders/show.blade.php حفظ شده؛ جزئیات سمت سرور رندر می‌شود و
     اکشن‌ها (وضعیت/تخصیص/واگذاری/ری‌پخش/لغو) متد Livewire با همان منطق کنترلرند.

     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div wire:poll.30s.visible>

    {{-- پیام نتیجهٔ اکشن‌ها --}}
    @if ($flashMessage)
        <div class="ui-note mb-4" data-tone="{{ $flashType === 'error' ? 'err' : 'ok' }}" role="status">
            {{ $flashMessage }}
        </div>
    @endif

    {{-- ================== هدر صفحه ================== --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 animate-fade-up">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.orders.index') }}" wire:navigate
               class="grid place-items-center size-9 rounded-xl border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 transition-colors" title="بازگشت به فهرست سفارش‌ها" aria-label="بازگشت">
                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <span class="grid place-items-center size-11 rounded-2xl bg-amber-100 text-xl shrink-0">{{ $order->service?->category?->icon ?: '📄' }}</span>
            <div class="min-w-0">
                <h1 class="text-sm font-extrabold text-stone-800 font-mono" dir="ltr">{{ $order->order_number }}</h1>
                <p class="text-[11px] text-stone-400 mt-0.5 truncate">{{ $order->service?->name ?? '—' }}</p>
            </div>
            <span class="badge {{ $statusBadge($order->status) }} shrink-0">{{ $order->status->label() }}</span>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            @if ($chat['exists'] && $chat['meta']['enabled'])
                <a href="{{ route('admin.orders.chat', $order->id) }}" wire:navigate class="btn-ghost ui-press !py-2 !px-4 !text-xs {{ $order->status->value === 'cancelled' ? '!text-rose-600' : '' }}"
                   title="{{ $order->status->value === 'cancelled' ? 'مشاهدهٔ سوابق گفتگو (سفارش لغوشده — فقط خواندن)' : 'مشاهدهٔ گفتگوی مشتری با اپراتور' }}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                    گفتگوی سفارش
                </a>
            @endif
            @if (count($statusActions))
                <button type="button" wire:click="openStatusModal({{ $order->id }})" class="btn-primary btn-shine !py-2 !px-4 !text-xs">تغییر وضعیت</button>
            @endif
            @if ($canAssign)
                <button type="button" wire:click="openAssign({{ $order->id }})" class="btn-primary btn-shine !py-2 !px-4 !text-xs">تخصیص به کافی‌نت</button>
            @endif
            @if ($order->coffeenet_id && $order->status->value === 'accepted')
                <button type="button" wire:click="openOperatorModal({{ $order->id }})" class="btn-primary btn-shine !py-2 !px-4 !text-xs">واگذاری به اپراتور</button>
            @endif
            @if ($canRebroadcast)
                <button type="button" wire:click="rebroadcastOrder({{ $order->id }})" class="btn-ghost ui-press !py-2 !px-4 !text-xs">ری‌پخش</button>
            @endif
            @if ($canCancel)
                <button type="button" wire:click="openCancel({{ $order->id }})" class="btn-ghost ui-press !py-2 !px-4 !text-xs text-rose-600 hover:bg-rose-50">لغو سفارش</button>
            @endif
        </div>
    </div>

    {{-- ================== بدنه جزئیات ================== --}}
    <div class="cs-stack">
        @if ($order->status->value === 'broadcasting')
            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 flex items-center gap-3 animate-fade-up">
                <span class="grid place-items-center size-9 rounded-xl bg-sky-100 text-sky-600 shrink-0">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </span>
                <p class="text-[11px] leading-6 text-sky-800">
                    سفارش در حال پخش بین کافی‌نت‌هاست —
                    @if ($detail['seconds_left'] > 0)
                        مهلت باقی‌مانده: <strong class="tabular-nums">{{ fa_digits((string) $detail['seconds_left']) }} ثانیه</strong>
                    @else
                        مهلت در حال اتمام…
                    @endif
                    @if ($detail['attempts'] > 1)
                        (پخش {{ fa_digits((string) $detail['attempts']) }})
                    @endif
                </p>
            </div>
        @elseif ($order->status->value === 'queued')
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 flex items-center gap-3 animate-fade-up">
                <span class="grid place-items-center size-9 rounded-xl bg-amber-100 text-amber-600 shrink-0">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l2-3 4 6 2-3h6"/></svg>
                </span>
                <p class="text-[11px] leading-6 text-amber-800">
                    سفارش بعد از {{ fa_digits((string) $detail['attempts']) }} پخشِ بی‌پذیرش در صف تعیین‌تکلیف است (از {{ $order->queued_at ? fa_date($order->queued_at, 'Y/m/d H:i') : '—' }}).
                    <strong>تخصیص دستی، ری‌پخش یا لغو</strong> از دکمه‌های بالا.
                </p>
            </div>
        @elseif ($order->status->value === 'cancelled' && $order->cancel_reason)
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 flex items-center gap-3 animate-fade-up">
                <span class="grid place-items-center size-9 rounded-xl bg-rose-100 text-rose-600 shrink-0">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                </span>
                <p class="text-[11px] leading-6 text-rose-800"><strong>دلیل لغو:</strong> {{ $order->cancel_reason }}</p>
            </div>
        @endif

        {{-- متا --}}
        <div class="card animate-fade-up p-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                @php
                    $meta = [
                        ['مشتری', $detail['customer_name'], false],
                        ['موبایل مشتری', $order->customer?->mobile, true],
                        ['شهر مشتری', $detail['customer_city'], false],
                        ['کافی‌نت پذیرنده', $order->coffeenet?->name, false],
                        ['اپراتور', $detail['operator_name'], false],
                        ['زمان تقریبی', $detail['estimated_time'] ? 'حدود '.fa_digits((string) $detail['estimated_time']).' دقیقه' : '—', false],
                    ];
                    if ($detail['contact_preference_label']) {
                        $meta[] = ['راه ارتباطی مشتری', $detail['contact_preference_label'], false];
                    }
                @endphp
                @foreach ($meta as [$label, $value, $ltr])
                    <div class="rounded-xl bg-stone-50 px-3.5 py-2.5">
                        <p class="text-[10px] font-bold text-stone-400 mb-1">{{ $label }}</p>
                        <p class="text-xs font-bold text-stone-700 break-words {{ $ltr ? 'font-mono' : '' }}" @if($ltr) dir="ltr" @endif>{{ $value ?? '—' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- مبالغ --}}
        <div class="rounded-2xl border border-stone-100 bg-white p-4 animate-fade-up">
            <p class="text-[11px] font-extrabold text-stone-500 mb-3">مبالغ (اسنپ‌شات ثبت سفارش)</p>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div><p class="text-[10px] text-stone-400">کارمزد خدمت</p><p class="text-sm font-extrabold text-stone-700 tabular-nums">{{ fa_money((float) $order->price) }}</p></div>
                <div><p class="text-[10px] text-stone-400">هزینه‌های جانبی</p><p class="text-sm font-extrabold text-stone-700 tabular-nums">{{ fa_money((float) $order->expenses) }}</p></div>
                <div><p class="text-[10px] text-stone-400">مبلغ کل</p><p class="text-sm font-extrabold text-amber-700 tabular-nums">{{ fa_money($detail['total']) }}</p></div>
            </div>
        </div>

        {{-- فاز ۶۰ — پرداخت سریع: لینک امضاشدهٔ ۲۰ دقیقه‌ای + QR (اسکن پشت میز مشتری) --}}
        @if ($paymentShare['payable'] || $paymentShare['paid'])
            <div class="rounded-2xl border border-stone-100 bg-white p-4 animate-fade-up ak-qr-card" wire:key="quick-pay-card">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <p class="text-[11px] font-extrabold text-stone-500 flex items-center gap-1.5">
                        <svg class="size-3.5 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3z"/><path d="M21 14v3"/><path d="M14 21h3"/><path d="M21 21h.01"/></svg>
                        {{ $paymentShare['payable'] ? __('chrome.f60.pay_share_title') : __('chrome.f60.receipt_qr_title') }}
                    </p>
                    @if ($paymentShare['payable'])
                        <span class="text-[10px] text-stone-400">{{ __('chrome.f60.pay_share_hint') }}</span>
                    @endif
                </div>

                @if ($paymentShare['payable'])
                    {{-- وضعیت لینک فعال: URL امضاشده + کپی + QR --}}
                    <div class="{{ $paymentShare['has_pending'] ? '' : 'hidden' }}" data-ak-paylink-block>
                        <div class="flex flex-col sm:flex-row items-center gap-4">
                            <div class="ak-qr shrink-0" data-ak-qr="{{ $paymentShare['payment_url'] }}" data-ak-qr-size="116" role="img" aria-label="{{ __('chrome.f60.pay_share_qr_aria') }}"></div>
                            <div class="min-w-0 flex-1 w-full">
                                <p class="text-[10px] text-stone-400 mb-1.5">{{ __('chrome.f60.pay_share_qr_hint') }}</p>
                                <div class="flex items-center gap-2">
                                    <code class="ak-qr-url font-mono text-[10px] text-stone-500" dir="ltr">{{ \Illuminate\Support\Str::limit((string) $paymentShare['payment_url'], 64) }}</code>
                                    <button type="button" class="btn-ghost ui-press !py-1.5 !px-3 !text-[11px] shrink-0" data-ak-copy="{{ $paymentShare['payment_url'] }}" data-toast="{{ __('chrome.f60.pay_share_copied') }}">
                                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                        {{ __('chrome.f60.pay_share_copy') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- وضعیت بدون Payment معلق: ساخت لینک با همان مسیر «پرداخت آنلاین» مشتری --}}
                    <div class="{{ $paymentShare['has_pending'] ? 'hidden' : '' }}" data-ak-paylink-empty>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            {{-- فاز ۶۰ — تأیید با PanelUI.confirm (ساخت پرداخت معلق، پرداخت‌های معلق قبلی را باطل می‌کند) --}}
                            <button type="button" x-data
                                @click="window.PanelUI ? PanelUI.confirm({
                                    title: '{{ __('chrome.f60.pay_share_confirm_title') }}',
                                    desc: '{{ __('chrome.f60.pay_share_confirm_desc') }}',
                                    okText: '{{ __('chrome.f60.pay_share_confirm_ok') }}',
                                    cancelText: '{{ __('chrome.f60.pay_share_confirm_cancel') }}'
                                }, () => $wire.createPaymentLink()) : $wire.createPaymentLink()"
                                class="btn-primary btn-shine !py-2 !px-4 !text-xs shrink-0"
                                wire:loading.attr="disabled" wire:target="createPaymentLink">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                {{ __('chrome.f60.pay_share_create') }}
                            </button>
                            <p class="text-[10px] leading-5 text-stone-400">{{ __('chrome.f60.pay_share_inactive_note') }}</p>
                        </div>
                    </div>
                @else
                    {{-- سفارش پرداخت‌شده: QR رسید (شماره سفارش + مبلغ + تاریخ) --}}
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <div class="ak-qr shrink-0" data-ak-qr="{{ $paymentShare['receipt_text'] }}" data-ak-qr-size="100" role="img" aria-label="{{ __('chrome.f60.receipt_qr_aria') }}"></div>
                        <div class="min-w-0 text-[11px] leading-6 text-stone-500">
                            <p class="font-bold text-stone-600">{{ __('chrome.f60.receipt_qr_line1') }}</p>
                            <p>{{ __('chrome.f60.receipt_qr_line2') }} <strong class="text-amber-700 tabular-nums">{{ fa_money($paymentShare['amount']) }}</strong></p>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- زمان‌ها --}}
        <div class="card animate-fade-up p-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @php
                    $times = [
                        ['ثبت سفارش', fa_date($order->created_at, 'Y/m/d H:i')],
                        ['پرداخت', $order->paid_at ? fa_date($order->paid_at, 'Y/m/d H:i') : null],
                        ['پذیرش', $order->accepted_at ? fa_date($order->accepted_at, 'Y/m/d H:i') : null],
                        ['صف تعیین‌تکلیف', $order->queued_at ? fa_date($order->queued_at, 'Y/m/d H:i') : null],
                    ];
                @endphp
                @foreach ($times as [$label, $value])
                    <div class="rounded-xl bg-stone-50 px-3.5 py-2.5">
                        <p class="text-[10px] font-bold text-stone-400 mb-1">{{ $label }}</p>
                        <p class="text-xs font-bold text-stone-700 break-words">{{ $value ?? '—' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- فرم داینامیک --}}
        @if (count($detail['form_data_display']))
            <div class="rounded-2xl border border-stone-100 bg-white p-4 animate-fade-up">
                <p class="text-[11px] font-extrabold text-stone-500 mb-3">پاسخ‌های فرم مشتری</p>
                <div class="space-y-1.5">
                    @foreach ($detail['form_data_display'] as $f)
                        <div class="flex items-start justify-between gap-4 text-xs border-b border-dashed border-stone-100 pb-2">
                            <span class="text-stone-400 font-semibold shrink-0">{{ $f['label'] }}</span>
                            <span class="text-stone-700 font-bold text-left">{{ $f['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- مدارک --}}
        @if (count($detail['files']))
            <div class="rounded-2xl border border-stone-100 bg-white p-4 animate-fade-up">
                <p class="text-[11px] font-extrabold text-stone-500 mb-3">مدارک مشتری ({{ fa_digits((string) count($detail['files'])) }})</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($detail['files'] as $f)
                        <a href="{{ $f['url'] }}" target="_blank" rel="noopener" class="btn-ghost !py-2 !px-3 !text-[11px]">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                            {{ $f['original_name'] }} <span class="text-stone-400">({{ fa_digits((string) $f['size_kb']) }}KB)</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- گیرنده‌های پخش --}}
        @if (count($detail['broadcasts']))
            <div class="rounded-2xl border border-stone-100 bg-white p-4 animate-fade-up">
                <p class="text-[11px] font-extrabold text-stone-500 mb-3">گیرنده‌های پخش ({{ fa_digits((string) count($detail['broadcasts'])) }} کافی‌نت)</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($detail['broadcasts'] as $b)
                        <span class="badge bg-stone-50 text-stone-600 border border-stone-200 !py-2">
                            {{ $b['coffeenet_name'] }}
                            <span class="text-stone-400">· ارسال {{ $b['sent_fa'] }}{{ $b['seen_fa'] ? ' · دیده‌شده '.$b['seen_fa'] : '' }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- پرداخت‌ها --}}
        @if (count($detail['payments']))
            <div class="rounded-2xl border border-stone-100 bg-white p-4 animate-fade-up">
                <p class="text-[11px] font-extrabold text-stone-500 mb-3">پرداخت‌ها</p>
                <div class="space-y-1.5">
                    @foreach ($detail['payments'] as $p)
                        <div class="flex items-center justify-between gap-3 text-xs border-b border-dashed border-stone-100 pb-2">
                            <span class="text-stone-600 font-semibold">
                                {{ $p['driver'] === 'wallet' ? 'کیف پول' : ($p['driver'] === 'local' ? 'درگاه تست' : $p['driver']) }}
                                @if ($p['ref_id']) <span class="text-stone-400 font-mono" dir="ltr">{{ \Illuminate\Support\Str::limit((string) $p['ref_id'], 18, '') }}</span>@endif
                            </span>
                            <span class="text-stone-500">{{ fa_money($p['amount']) }} · {{ $p['status_label'] }}{{ $p['paid_at_fa'] ? ' · '.$p['paid_at_fa'] : '' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- تاریخچهٔ وضعیت (تایم‌لاین سبک) --}}
        @if (count($detail['status_history']))
            <div class="rounded-2xl border border-stone-100 bg-white p-4 animate-fade-up">
                <p class="text-[11px] font-extrabold text-stone-500 mb-3">تاریخچهٔ وضعیت</p>
                <div class="space-y-2">
                    @foreach ($detail['status_history'] as $h)
                        <div class="flex items-start gap-2.5 text-xs">
                            <span class="grid place-items-center size-6 rounded-full bg-sky-50 text-sky-600 shrink-0 mt-0.5">
                                <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="font-bold text-stone-700">{{ $h['to_status_label'] ?? '—' }}</span>
                                @if ($h['note']) <span class="text-stone-500"> — {{ $h['note'] }}</span>@endif
                                @if ($h['created_at_fa']) <span class="block text-[10px] text-stone-400 mt-0.5">{{ $h['created_at_fa'] }}</span>@endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- v33 — نظرسنجی مشتری (امتیاز + دلایل + دیدگاه) --}}
        @if ($detail['rating'])
            <div class="rounded-2xl border border-stone-100 bg-white p-4 animate-fade-up">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <p class="text-[11px] font-extrabold text-stone-500">نظرسنجی مشتری</p>
                    @if ($detail['rating']['rated_at_fa'])<span class="text-[10px] text-stone-400">{{ $detail['rating']['rated_at_fa'] }}</span>@endif
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <div class="rt-stars {{ \App\Livewire\Admin\Support\Ui::starsClass($detail['rating']['rating']) }}" role="img" aria-label="{{ fa_digits((string) $detail['rating']['rating']) }} از ۵">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="{{ $i <= $detail['rating']['rating'] ? 'on' : '' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.26L21.5 9.27l-5 4.87 1.18 6.88L12 17.77l-5.68 3.25 1.18-6.88-5-4.87 6.6-3.01Z"/></svg>
                        @endfor
                        <b class="rt-stars-num">{{ fa_digits((string) $detail['rating']['rating']) }}</b>
                    </div>
                    @if ($detail['rating']['operator_rating'] !== null)
                        <div class="text-[11px] text-stone-500">امتیاز اپراتور: <strong class="text-stone-700">{{ fa_digits((string) $detail['rating']['operator_rating']) }}</strong></div>
                    @endif
                    @foreach ($detail['rating']['options'] as $o)
                        <span class="rt-chip {{ $o['type'] === 'neg' ? 'rt-chip--neg' : '' }}">{{ $o['title'] }}</span>
                    @endforeach
                </div>
                @if ($detail['rating']['comment'])
                    <p class="rt-comment mt-3" title="{{ $detail['rating']['comment'] }}">{{ $detail['rating']['comment'] }}</p>
                @endif
            </div>
        @endif
    </div>

    {{-- ================== مودال تغییر وضعیت ================== --}}
    <div class="ui-modal-backdrop {{ $statusOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="status-title" wire:key="status-modal">
        <div class="absolute inset-0" aria-hidden="true" wire:click="closeStatusModal()"></div>

        <form wire:submit="updateStatus" wire:key="status-form" class="ui-modal adm-modal-sm adm-modal-text-start" data-tone="info" role="document" novalidate>
            <div class="adm-modal-head">
                <div>
                    <h3 id="status-title" class="text-sm font-extrabold text-stone-800">تغییر وضعیت سفارش</h3>
                    <p class="text-[11px] text-stone-400 mt-0.5 font-mono" dir="ltr">{{ $assignOrderNumber ?: '—' }}</p>
                </div>
                <button type="button" class="adm-modal-x" wire:click="closeStatusModal()" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="adm-modal-body space-y-4">
                <div>
                    <label class="lbl">وضعیت جدید <span class="text-amber-600">*</span></label>
                    <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="انتخاب وضعیت جدید">
                        @foreach ($statusActions as [$value, $label, $needsReason])
                            <button type="button" wire:click="$set('newStatus', '{{ $value }}')" wire:key="st-{{ $value }}"
                                    class="rounded-xl border px-3 py-2.5 text-xs font-bold transition-colors
                                           {{ $newStatus === $value ? 'border-blue-300 bg-blue-50/60 text-blue-800 ring-1 ring-blue-200' : 'border-stone-200 bg-white text-stone-600 hover:bg-stone-50' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="status-reason" class="lbl">یادداشت / دلیل @if ($newStatus && collect($statusActions)->first(fn ($a) => $a[0] === $newStatus && $a[2]))<span class="text-rose-500">*</span>@endif</label>
                    <textarea id="status-reason" rows="2" wire:model="statusReason" class="field !text-xs w-full" placeholder="یادداشت تاریخچه (برای لغو/بازگشت وجه اجباری)…"></textarea>
                </div>

                <div class="ui-note" data-tone="info">
                    گذارها بر اساس ماشین وضعیت اعتبارسنجی می‌شوند؛ پیام سیستمی در چت و اعلان/پیامک مشتری به‌طور خودکار ارسال می‌گردد.
                </div>

                @error('newStatus')<p class="field-error">{{ $message }}</p>@enderror
                @error('statusReason')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="adm-modal-foot">
                <button type="button" class="btn-ghost ui-press !py-2.5 !text-xs" wire:click="closeStatusModal()">انصراف</button>
                <button type="submit" class="btn-primary btn-shine !py-2.5 !text-xs" @disabled(!$newStatus)>اعمال وضعیت</button>
            </div>
        </form>
    </div>

    {{-- ================== مودال تخصیص به کافی‌نت ================== --}}
    <div class="ui-modal-backdrop {{ $assignOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="assign-title" wire:key="assign-modal">
        <div class="absolute inset-0" aria-hidden="true" wire:click="closeAssign()"></div>

        <form wire:submit="assignOrder" wire:key="assign-form" class="ui-modal adm-modal-md adm-modal-text-start" data-tone="info" role="document" novalidate>
            <div class="adm-modal-head">
                <div>
                    <h3 id="assign-title" class="text-sm font-extrabold text-stone-800">تخصیص دستی سفارش</h3>
                    <p class="text-[11px] text-stone-400 mt-0.5 font-mono" dir="ltr">{{ $assignOrderNumber ?: '—' }}</p>
                </div>
                <button type="button" class="adm-modal-x" wire:click="closeAssign()" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="adm-modal-body space-y-4">
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" placeholder="جستجوی نام کافی‌نت…" wire:model.live.debounce.300ms="assignSearch"
                           class="field !py-2.5 !text-xs w-full pl-10" autocomplete="off">
                </div>

                <div class="ui-stagger space-y-2 max-h-72 overflow-y-auto" role="radiogroup" aria-label="انتخاب کافی‌نت">
                    @forelse ($assignCoffeenets as $c)
                        <button type="button" wire:click="setAssignCoffeenet({{ $c['id'] }})" wire:key="assign-net-{{ $c['id'] }}"
                                class="w-full text-right rounded-2xl border px-4 py-3 flex items-center gap-3 transition-colors
                                       {{ $assignCoffeenetId === $c['id'] ? 'border-blue-300 bg-blue-50/60 ring-1 ring-blue-200' : 'border-stone-200 bg-white hover:bg-stone-50' }}">
                            <span class="grid place-items-center size-9 rounded-xl bg-sky-50 text-sky-600 shrink-0">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 7h20"/><path d="M12 7v5"/><rect width="18" height="14" x="3" y="7" rx="1"/></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-stone-700 truncate">{{ $c['name'] }}</span>
                                <span class="block text-[10px] text-stone-400 mt-0.5">{{ $c['city'] ?? '—' }} @if($c['phone']) · <span dir="ltr" class="font-mono">{{ $c['phone'] }}</span>@endif</span>
                            </span>
                            @if ($assignCoffeenetId === $c['id'])
                                <svg class="size-4 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </button>
                    @empty
                        <p class="text-xs text-stone-400 text-center py-6">
                            {{ $assignSearch !== '' ? 'کافی‌نتی با این نام یافت نشد.' : 'در حال دریافت کافی‌نت‌های فعال…' }}
                        </p>
                    @endforelse
                </div>

                @if ($assignCoffeenetId && count($assignOperators) > 0)
                    <div>
                        <label for="assign-operator" class="lbl">واگذاری به اپراتور (اختیاری)</label>
                        <select id="assign-operator" wire:model="assignOperatorId" class="field !text-xs">
                            <option value="">بدون اپراتور — تعیین تکلیف توسط مدیر کافی‌نت</option>
                            @foreach ($assignOperators as $op)
                                <option value="{{ $op['id'] }}">{{ $op['name'] }} {{ $op['mobile'] ? ' — '.$op['mobile'] : '' }} ({{ $op['position_label'] }})</option>
                            @endforeach
                        </select>
                        <p class="st-hint text-[11px] text-stone-400 mt-1">اپراتورهای فعال کافی‌نت انتخابی — اختیاری</p>
                    </div>
                @endif

                <div>
                    <label for="assign-note" class="lbl">یادداشت (اختیاری)</label>
                    <textarea id="assign-note" rows="2" wire:model="assignNote" class="field !text-xs w-full" placeholder="مثلاً دلیل تخصیص یا توضیح برای تاریخچه…"></textarea>
                </div>

                @error('assignCoffeenetId')<p class="field-error">{{ $message }}</p>@enderror
                @error('assignOperatorId')<p class="field-error">{{ $message }}</p>@enderror
                @error('assignNote')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="adm-modal-foot">
                <button type="button" class="btn-ghost ui-press !py-2.5 !text-xs" wire:click="closeAssign()">انصراف</button>
                <button type="submit" class="btn-primary btn-shine !py-2.5 !text-xs" @disabled(!$assignCoffeenetId)>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    تخصیص به کافی‌نت
                </button>
            </div>
        </form>
    </div>

    {{-- ================== مودال واگذاری به اپراتور ================== --}}
    <div class="ui-modal-backdrop {{ $operatorOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="operator-title" wire:key="operator-modal">
        <div class="absolute inset-0" aria-hidden="true" wire:click="closeOperatorModal()"></div>

        <form wire:submit="assignOperator" wire:key="operator-form" class="ui-modal adm-modal-md adm-modal-text-start" data-tone="info" role="document" novalidate>
            <div class="adm-modal-head">
                <div>
                    <h3 id="operator-title" class="text-sm font-extrabold text-stone-800">واگذاری به اپراتور</h3>
                    <p class="text-[11px] text-stone-400 mt-0.5 font-mono" dir="ltr">{{ $assignOrderNumber ?: '—' }}</p>
                </div>
                <button type="button" class="adm-modal-x" wire:click="closeOperatorModal()" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="adm-modal-body space-y-4">
                <div class="ui-stagger space-y-2 max-h-72 overflow-y-auto" role="radiogroup" aria-label="انتخاب اپراتور">
                    @forelse ($operators as $op)
                        <button type="button" wire:click="$set('operatorId', {{ $op['id'] }})" wire:key="op-{{ $op['id'] }}"
                                class="w-full text-right rounded-2xl border px-4 py-3 flex items-center gap-3 transition-colors
                                       {{ $operatorId === $op['id'] ? 'border-blue-300 bg-blue-50/60 ring-1 ring-blue-200' : 'border-stone-200 bg-white hover:bg-stone-50' }}">
                            <span class="grid place-items-center size-9 rounded-xl bg-teal-50 text-teal-600 shrink-0">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm18 0h-3a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-5Z"/><path d="M21 11a9 9 0 0 0-18 0"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/></svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-stone-700 truncate">{{ $op['name'] }}</span>
                                <span class="block text-[10px] text-stone-400 mt-0.5">{{ $op['position_label'] }} @if($op['mobile']) · <span dir="ltr" class="font-mono">{{ $op['mobile'] }}</span>@endif</span>
                            </span>
                        </button>
                    @empty
                        <p class="text-xs text-stone-400 text-center py-6">اپراتور فعالی برای کافی‌نت پذیرنده ثبت نشده است.</p>
                    @endforelse
                </div>

                <div>
                    <label for="operator-note" class="lbl">یادداشت (اختیاری)</label>
                    <textarea id="operator-note" rows="2" wire:model="operatorNote" class="field !text-xs w-full" placeholder="مثلاً توضیح واگذاری برای تاریخچه…"></textarea>
                </div>

                @error('operatorId')<p class="field-error">{{ $message }}</p>@enderror
                @error('operatorNote')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="adm-modal-foot">
                <button type="button" class="btn-ghost ui-press !py-2.5 !text-xs" wire:click="closeOperatorModal()">انصراف</button>
                <button type="submit" class="btn-primary btn-shine !py-2.5 !text-xs" @disabled(!$operatorId)>واگذاری به اپراتور</button>
            </div>
        </form>
    </div>

    {{-- ================== مودال لغو ================== --}}
    <div class="ui-modal-backdrop {{ $cancelOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="cancel-title" wire:key="cancel-modal">
        <div class="absolute inset-0" aria-hidden="true" wire:click="closeCancel()"></div>

        <form wire:submit="cancelOrder" wire:key="cancel-form" class="ui-modal adm-modal-sm adm-modal-text-start" data-tone="danger" role="document" novalidate>
            <div class="adm-modal-head">
                <div>
                    <h3 id="cancel-title" class="text-sm font-extrabold text-rose-600">لغو سفارش</h3>
                    <p class="text-[11px] text-stone-400 mt-0.5 font-mono" dir="ltr">{{ $cancelOrderNumber ?: '—' }}</p>
                </div>
                <button type="button" class="adm-modal-x" wire:click="closeCancel()" aria-label="بستن">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="adm-modal-body space-y-4">
                <div class="ui-note" data-tone="err">
                    لغو سفارش پرداخت‌شده به مشتری پیامک می‌شود و در تاریخچه ثبت می‌گردد.
                    <strong>وجه پرداختی طبق فرایند تسویه برگشت داده می‌شود.</strong>
                </div>
                <div>
                    <label for="cancel-reason" class="lbl">دلیل لغو <span class="text-rose-500">*</span></label>
                    <textarea id="cancel-reason" rows="3" wire:model="cancelReason" class="field !text-xs w-full" placeholder="دلیل لغو برای مشتری و تاریخچه…"></textarea>
                </div>
                @error('cancelReason')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="adm-modal-foot">
                <button type="button" class="btn-ghost ui-press !py-2.5 !text-xs" wire:click="closeCancel()">انصراف</button>
                <button type="submit" class="ui-btn-danger !py-2.5 !text-xs" wire:loading.attr="disabled">لغو سفارش</button>
            </div>
        </form>
    </div>

    @include('livewire.admin.orders.partials.toaster')
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pages/ratings.css') }}?v=1">
{{-- فاز ۶۰ — استایل باکس QR پرداخت/رسید (namespace .ak-qr + دارک‌مود) --}}
<link rel="stylesheet" href="{{ asset('assets/css/pages/order-qr.css') }}?v=2">
@endpush

@push('scripts')
{{-- فاز ۶۰ — کتابخانهٔ QR (qrcodejs 1.0.0 — بدون Node) + رندر سمت کلاینت --}}
<script src="{{ asset('assets/js/vendor/qrcode.min.js') }}?v=60" defer></script>
<script src="{{ asset('back/assets/js/pages/order-qr.js') }}?v=3" defer></script>
@endpush
