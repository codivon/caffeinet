{{-- «گفتگوی سفارش» پنل مدیریت کل — Shell Livewire + موتور JS قبلی [Task 3-c]
     عیناً از back/operator/chat/show.blade.php کپی شده (ادمین کل) با همان
     #page-data payload؛ show.js (پولینگ after_id + Pusher + آپلود + اکشن وضعیت)
     با همان endpointهای admin.orders.chat.data / send / status کار می‌کند.

     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div>

    {{-- داده‌های سرور برای JS — payload در کامپوننت PHP ساخته می‌شود (سازگار با کامپایلر Livewire) --}}
    <div id="page-data" hidden data-payload="{{ json_encode($pagePayload, JSON_UNESCAPED_UNICODE) }}"></div>

    {{-- سربرگ صفحه --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 animate-fade-up">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.chats.index') }}" wire:navigate
               class="grid place-items-center size-9 rounded-xl border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 transition-colors" title="بازگشت به گفتگوها" aria-label="بازگشت به گفتگوها">
                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
            </a>
            <div>
                <p class="text-sm font-extrabold text-stone-700">گفتگوی سفارش <span class="font-mono" dir="ltr">{{ $order->order_number }}</span></p>
                <p class="text-[11px] text-stone-400 mt-0.5 leading-5">
                    خدمت «{{ $order->service?->name ?? '—' }}»
                    @if ($order->operator)
                        — اپراتور مسئول: {{ trim(($order->operator->name ?? '').' '.($order->operator?->family ?? '')) }}
                    @endif
                </p>
            </div>
        </div>

        {{-- عملیات‌های سریع وضعیت --}}
        <div class="flex items-center gap-1.5 flex-wrap" id="status-actions">
            @foreach ($statusActions as $action)
                <button type="button" class="status-action-btn btn-ghost ui-press !py-2 !px-4 !text-xs {{ ($action[2] ?? false) ? 'text-rose-600 hover:bg-rose-50' : '' }}"
                        data-status="{{ $action[0] }}" @if ($action[2] ?? false) data-reason="1" @endif>{{ $action[1] }}</button>
            @endforeach
            @if ($order->paid_at)
                <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200" id="orderPaidBadge">✓ پرداخت‌شده</span>
            @else
                <span class="badge bg-amber-50 text-amber-700 border border-amber-200 animate-pulse" id="orderPaidBadge" title="شروع کار پس از پرداخت مشتری فعال می‌شود">⏳ در انتظار پرداخت</span>
            @endif
            <span id="orderStatusBadge" class="badge {{ \App\Livewire\Admin\Support\Ui::statusBadge($order->status) }}">{{ $order->status->label() }}</span>
        </div>
    </div>

    {{-- رابط گفتگو --}}
    <div class="cnchat cnchat--back animate-fade-up" id="cnchat" aria-label="گفتگو با مشتری">

        {{-- سربرگ گفتگو: مشتری --}}
        <div class="cnchat-head">
            <span class="ch-avatar" aria-hidden="true">{{ $initial }}</span>
            <div class="ch-info">
                <strong class="ch-name">{{ $customerName }}</strong>
                <span class="ch-sub">
                    <span class="relative flex size-1.5 inline-flex me-1"><span class="absolute inline-flex size-full rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex size-1.5 rounded-full bg-emerald-400"></span></span>
                    مشتری سفارش — پاسخگویی در همین گفتگو
                </span>
            </div>
        </div>

        {{-- ناحیه پیام‌ها --}}
        <div class="cnchat-pane" id="chatPane" role="log" aria-live="polite" aria-label="پیام‌های گفتگو">
            <div class="cnchat-msgs" id="chatMsgs">
                <div class="cnchat-empty">
                    <span class="e-ico" aria-hidden="true">☕</span>
                    <p class="e-t">در حال بارگذاری گفتگو…</p>
                </div>
            </div>
        </div>

        {{-- شمارش پیام جدید --}}
        <button type="button" class="new-msgs-pill" id="newMsgsPill">↓ پیام جدید</button>

        {{-- فقط-خواندن --}}
        <div class="cnchat-readonly hidden {{ ($chatMeta['cancelled'] ?? false) ? 'cnchat-readonly--cancelled' : '' }}" id="chatReadonly">
            @if (($chatMeta['cancelled'] ?? false))
                این سفارش لغو شده است — سوابق گفتگو برای بررسی حفظ شده و ارسال پیام غیرفعال است.
                @if ($order->cancel_reason)
                    <span class="block mt-1 text-[11px] opacity-80">دلیل لغو: {{ $order->cancel_reason }}</span>
                @endif
            @else
                این گفتگو بسته شده است (وضعیت سفارش: تحویل/تکمیل) — پیام‌ها قابل مشاهده‌اند اما ارسال فعال نیست.
            @endif
        </div>

        {{-- فاز ۱۲ — پیش‌نمایش/آپلودر زیبا (بالای نوار ارسال) --}}
        <div class="composer-preview" id="composerPreview">
            <span class="p-thumb t-file" id="pThumb">
                <img id="pThumbImg" alt="" hidden>
                <svg id="pThumbIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                <span class="p-ext" id="pExt" hidden></span>
            </span>
            <div class="p-info">
                <span id="pName">فایل</span>
                <small id="pMeta">—</small>
                <div class="p-track" id="uploadBar"><i id="uploadFill"></i></div>
            </div>
            <span class="p-pct" id="pPct">۰٪</span>
            <button type="button" class="p-rm" id="pRemove" title="حذف پیوست" aria-label="حذف پیوست">✕</button>
        </div>

        {{-- نوار ارسال --}}
        <div class="cnchat-composer" id="chatComposer">
            <button type="button" class="cch-btn attach" id="attachBtn" title="ارسال فایل" aria-label="ارسال فایل" aria-haspopup="menu" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
            </button>

            <div class="attach-sheet" id="attachMenu" role="menu" aria-label="انتخاب نوع فایل">
                <span class="as-handle" aria-hidden="true"></span>
                <div class="as-head">
                    <div class="as-ttl">
                        <strong>ارسال فایل</strong>
                        <small>چه چیزی می‌خواهید بفرستید؟</small>
                    </div>
                    <button type="button" class="as-close" id="attachClose" aria-label="بستن" title="بستن">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <div class="as-grid">
                    <button type="button" class="as-item" data-attach="image" role="menuitem">
                        <span class="as-tile t-image" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="3"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4.35-4.35a1.5 1.5 0 0 0-2.12 0L5 20"/></svg>
                        </span>
                        <span><strong>تصویر</strong><small>JPG · PNG · WebP — تا ۵ مگابایت</small></span>
                    </button>
                    <button type="button" class="as-item" data-attach="video" role="menuitem">
                        <span class="as-tile t-video" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 13 5.2-3.1a.6.6 0 0 1 .8.5v3.2a.6.6 0 0 1-.8.5L16 11"/><rect width="14" height="10" x="2" y="7" rx="2"/><path d="m6 11 2 2 4-4"/></svg>
                        </span>
                        <span><strong>ویدیو</strong><small>MP4 · WebM — تا ۵۰ مگابایت</small></span>
                    </button>
                    <button type="button" class="as-item" data-attach="audio" role="menuitem">
                        <span class="as-tile t-audio" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v11"/><path d="M8 6.5a6 6 0 0 0 0 11"/><path d="M16 6.5a6 6 0 0 1 0 11"/></svg>
                        </span>
                        <span><strong>صدا</strong><small>MP3 · OGG · WAV — تا ۱۰ مگابایت</small></span>
                    </button>
                    <button type="button" class="as-item" data-attach="file" role="menuitem">
                        <span class="as-tile t-file" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                        </span>
                        <span><strong>فایل</strong><small>PDF · Office · ZIP — تا ۲۰ مگابایت</small></span>
                    </button>
                </div>
            </div>

            <div class="ta-wrap">
                <textarea id="chatInput" rows="1" placeholder="پیام خود را بنویسید…" maxlength="2000" aria-label="متن پیام"></textarea>
            </div>

            <button type="button" class="cch-btn send" id="sendBtn" disabled title="ارسال" aria-label="ارسال پیام">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4Z"/></svg>
            </button>
        </div>

        {{-- ورودی‌های فایل (پنهان) --}}
        <input type="file" id="fileImage" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
        <input type="file" id="fileVideo" accept="video/mp4,video/webm,video/x-matroska,video/quicktime" hidden>
        <input type="file" id="fileAudio" accept="audio/mpeg,audio/ogg,audio/wav,audio/mp4,audio/aac,audio/opus" hidden>
        <input type="file" id="fileFile" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z" hidden>

        {{-- فاز ۱۲ — پس‌زمینهٔ شیت پیوست --}}
        <div class="attach-backdrop" id="attachBackdrop" hidden></div>
    </div>

    {{-- مودال دلیل (لغو / بازگشت وجه / عدم امکان انجام) — مدیریت‌شده توسط show.js --}}
    <div id="reason-modal" class="ui-modal-backdrop hidden" role="dialog" aria-labelledby="reason-title">
        <div data-close-reason class="absolute inset-0" aria-hidden="true"></div>
        <form id="reason-form" class="ui-modal adm-modal-md adm-modal-text-start" data-tone="danger" role="document" novalidate>
            <div class="adm-modal-head">
                <div>
                    <h3 id="reason-title" class="text-sm font-extrabold text-stone-800">ذکر دلیل الزامی است</h3>
                    <p id="reason-sub" class="text-[11px] text-stone-400 mt-1 leading-5">این دلیل برای مشتری پیامک می‌شود و در تاریخچهٔ سفارش ثبت می‌گردد.</p>
                </div>
            </div>
            <div class="p-5">
                <label class="block text-xs font-bold text-stone-600 mb-2" for="reason-input">دلیل</label>
                <textarea id="reason-input" rows="3" maxlength="490" class="field !text-xs w-full"
                          placeholder="مثلاً: فایل قابل ویرایش نیست / اپراتور در دسترس نیست…" required></textarea>
                <p id="reason-error" class="field-error mt-2 hidden">حداقل ۳ حرف لازم است.</p>
            </div>
            <div class="px-5 pb-5 flex items-center gap-2">
                <button type="button" id="reason-cancel" class="btn-ghost ui-press !py-2.5 !px-4 !text-xs flex-1">انصراف</button>
                <button type="submit" id="reason-save" class="btn-primary btn-shine !py-2.5 !px-4 !text-xs flex-1">ثبت و اجرا</button>
            </div>
        </form>
    </div>

    @include('livewire.admin.orders.partials.toaster')
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chat.css') }}?v=20">
@endpush

@push('scripts')
<script src="{{ asset('back/assets/js/pages/operator/chat/show.js') }}?v=19"></script>
@endpush
