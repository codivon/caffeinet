{{-- «برداشت از کیف پول» سازمان — نسخهٔ Livewire 4 [Task 6]
     ساختار و کلاس‌ها عیناً از back/org/withdrawals/index.blade.php حفظ شده است.
     تاریخچه سمت سرور با فیلتر wire:model.live رندر می‌شود؛ ثبت درخواست با همان
     endpoint کنترلر (POST org.withdrawals.store) از طریق App.ajax — نگاشت خطای
     ۴۲۲ فیلد amount و اسپینر دکمه مثل نسخهٔ JS قبلی.
     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- فرم درخواست --}}
    <div class="card ui-lift p-6 animate-fade-up space-y-4 h-fit overflow-hidden relative">
        <span class="ui-orb" data-tone="teal" data-pos="bl" aria-hidden="true"></span>
        <h2 class="no-card-title text-sm font-extrabold text-stone-700 relative">درخواست برداشت جدید</h2>

        <div class="rounded-2xl bg-teal-50/70 border border-teal-200/60 px-4 py-3.5 relative">
            <p class="text-[11px] text-teal-800 font-semibold flex items-center gap-2">
                <span class="ui-dot text-emerald-500"></span>
                موجودی قابل برداشت
            </p>
            <p class="mt-1 text-2xl font-extrabold tabular-nums text-teal-700">{{ fa_money($balance) }}</p>
        </div>

        <form class="space-y-4" novalidate x-on:submit.prevent="submit" x-ref="form"
              x-data="{
                  busy: false,
                  async submit() {
                      const errEl = this.$refs.amountErr;
                      const input = this.$refs.amount;
                      errEl.classList.add('hidden');
                      input.classList.remove('field-error');

                      const amount = input.value;
                      const note = this.$refs.note.value.trim();

                      if (!amount || +amount < 1000) {
                          errEl.textContent = 'حداقل مبلغ برداشت ۱٬۰۰۰ تومان است.';
                          errEl.classList.remove('hidden');
                          input.classList.add('field-error');
                          return;
                      }

                      this.busy = true;
                      try {
                          const res = await App.ajax('{{ route('org.withdrawals.store') }}', {
                              method: 'POST',
                              body: { amount: +amount, note: note || null },
                          });
                          const data = await res.json().catch(() => ({}));

                          if (res.ok) {
                              App.toast(data.message || 'درخواست ثبت شد.', 'success');
                              this.$refs.form.reset();
                              $wire.$refresh();
                          } else if (res.status === 422 && data.errors) {
                              errEl.textContent = (data.errors.amount && data.errors.amount[0]) || data.message;
                              errEl.classList.remove('hidden');
                              input.classList.add('field-error');
                          } else {
                              App.toast(data.message || 'خطا در ثبت درخواست.', 'error');
                          }
                      } finally {
                          this.busy = false;
                      }
                  }
              }">
            <div>
                <label class="lbl" for="w-amount">مبلغ برداشت (تومان) <span class="text-rose-500">*</span></label>
                <input id="w-amount" type="number" min="1000" step="1000" dir="ltr" class="field" placeholder="50000" x-ref="amount">
                <p class="err text-[11px] text-rose-500 mt-1 hidden" data-for="amount" x-ref="amountErr"></p>
            </div>

            <div>
                <label class="lbl" for="w-note">توضیحات (اختیاری)</label>
                <textarea id="w-note" class="field" rows="2" placeholder="مثلاً شماره حساب/شبا جهت واریز" x-ref="note"></textarea>
            </div>

            <button type="submit" class="btn-primary btn-shine no-btn-teal w-full !py-3" x-bind:disabled="busy">
                <span x-show="!busy">ثبت درخواست برداشت</span>
                <span x-show="busy" style="display:none" class="inline-flex items-center gap-2">
                    <span class="size-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span> در حال ثبت...
                </span>
            </button>
        </form>

        <p class="text-[11px] text-stone-400 leading-6 border-t border-stone-100 pt-3 relative">
            مبلغ درخواست بلافاصله از کیف پول شما کسر و تا پرداخت توسط مدیریت کل نزد پلتفرم بلوکه می‌شود.
            {{ $pending > 0 ? 'شما <strong class="text-amber-600">'.fa_number($pending).' درخواست در انتظار بررسی</strong> دارید.' : '' }}
        </p>
    </div>

    {{-- لیست برداشت‌ها --}}
    <section class="lg:col-span-2 card ui-lift animate-fade-up delay-1 h-fit overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex flex-wrap items-center gap-3 justify-between">
            <h2 class="no-card-title text-sm font-extrabold text-stone-700">تاریخچه برداشت‌ها</h2>
            <select wire:model.live="status" class="field !py-2 !w-auto !text-xs min-w-36" aria-label="فیلتر وضعیت">
                <option value="">همه</option>
                <option value="pending">در انتظار</option>
                <option value="paid">پرداخت‌شده</option>
                <option value="rejected">ردشده</option>
            </select>
        </div>

        <div class="table-wrap">
            <table class="table-panel table-modern">
                <thead>
                    <tr>
                        <th>مبلغ</th>
                        <th>وضعیت</th>
                        <th>تاریخ درخواست</th>
                        <th>توضیحات</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        /* نقشهٔ کلاس بج‌ها — عیناً statusBadges نسخهٔ JS قبلی */
                        $statusBadges = [
                            'amber' => 'bg-amber-50 text-amber-700 border border-amber-200',
                            'sky' => 'bg-sky-50 text-sky-700 border border-sky-200',
                            'emerald' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                            'rose' => 'bg-rose-50 text-rose-600 border border-rose-200',
                            'stone' => 'bg-stone-100 text-stone-500 border border-stone-200',
                        ];
                    @endphp
                    @forelse ($rows as $w)
                        <tr wire:key="wd-{{ $w['id'] }}">
                            <td class="font-extrabold tabular-nums text-amber-600">{{ fa_money($w['amount'], false) }} <span class="text-[10px] font-normal text-stone-400">تومان</span></td>
                            <td>
                                <span class="badge {{ $statusBadges[$w['status_color']] ?? $statusBadges['stone'] }}">{{ $w['status_label'] }}</span>
                                @if ($w['reviewer'])
                                    <p class="text-[10px] text-stone-400 mt-1">{{ $w['reviewer'] }}{{ $w['reviewed_at'] ? ' · '.$w['reviewed_at'] : '' }}</p>
                                @endif
                            </td>
                            <td class="text-stone-500 text-xs">{{ $w['requested_at'] }}</td>
                            <td class="max-w-56 truncate text-stone-400 text-xs" title="{{ $w['note'] ?? '' }}">{{ $w['note'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="!py-10">
                                <div class="ui-empty">
                                    <span class="ui-empty-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                                    </span>
                                    <p class="text-sm font-semibold text-stone-500">برداشتی ثبت نشده است.</p>
                                    <p class="text-xs text-stone-400 mt-1">درخواست اولین برداشت را از فرم روبه‌رو ثبت کنید.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی — عیناً فوتر قبلی (renderPagination) --}}
        <div class="px-5 py-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500">
            @if ($rows->lastPage() > 1)
                <span>{{ fa_number($rows->total()) }} درخواست — صفحه {{ fa_number($rows->currentPage()) }} از {{ fa_number($rows->lastPage()) }}</span>
                <div class="flex gap-2">
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="previousPage" @disabled($rows->onFirstPage())>قبلی</button>
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" wire:click="nextPage" @disabled(! $rows->hasMorePages())>بعدی</button>
                </div>
            @else
                <span>{{ fa_number($rows->total()) }} درخواست</span>
            @endif
        </div>
    </section>
</div>
</div>
