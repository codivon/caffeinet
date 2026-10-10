{{-- «مدیریت برداشت‌ها» پنل مدیریت کل — نسخهٔ Livewire 4 [Task 3-e]
     ساختار و کلاس‌ها عیناً از back/admin/withdrawals/index.blade.php حفظ شده؛
     جدول سمت سرور با فیلتر wire:model.live و مودال تعیین‌تکلیف Livewire.
     ثبت نهایی همان endpoint کنترلر (PATCH admin.withdrawals.review) است.

     نکته: Livewire فقط یک عنصر ریشه مجاز است — کل محتوا داخل یک div ریشه. --}}
<div>

    <section class="card ui-lift animate-fade-up overflow-hidden">

        {{-- هدر جدول --}}
        <div class="adm-card-head">
            <div class="flex items-center gap-3 flex-wrap">
                <div class="text-xs text-stone-500 leading-6">
                    مبلغ هر درخواست <span class="font-bold text-blue-600">از زمان ثبت بلوکه</span> شده و با رد شدن به کیف پول برمی‌گردد.
                </div>
            </div>
            <select wire:model.live="status" class="field !py-2.5 !w-auto min-w-40" aria-label="فیلتر وضعیت">
                <option value="">همه وضعیت‌ها</option>
                <option value="pending">در انتظار بررسی</option>
                <option value="approved">تأییدشده</option>
                <option value="paid">پرداخت‌شده</option>
                <option value="rejected">ردشده</option>
            </select>
        </div>

        {{-- جدول --}}
        <div class="table-wrap">
            <div class="overflow-x-auto">
                <table class="table-panel table-modern">
                    <thead>
                        <tr>
                            <th>درخواست‌دهنده</th>
                            <th>مبلغ</th>
                            <th>موجودی فعلی کیف پول</th>
                            <th>وضعیت</th>
                            <th>تاریخ درخواست</th>
                            <th class="text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr wire:key="w{{ $r['id'] }}">
                                <td>
                                    <p class="font-bold text-stone-800">{{ $r['holder'] }}</p>
                                    <p class="text-[11px] text-stone-400">{{ $r['holder_type'] }} · {{ $r['requester'] }}</p>
                                </td>
                                <td class="font-extrabold tabular-nums text-blue-600">{{ fa_money($r['amount'], false) }} <span class="text-[10px] font-normal text-stone-400">تومان</span></td>
                                <td class="tabular-nums text-stone-600">{{ fa_money($r['wallet_balance'], false) }} <span class="text-[10px] font-normal text-stone-400">تومان</span></td>
                                <td>
                                    <span class="badge {{ \App\Livewire\Admin\Support\Ui::withdrawalBadge($r['status']) }}">{{ \App\Livewire\Admin\Support\Ui::withdrawalLabel($r['status']) }}</span>
                                    @if ($r['reviewer'])
                                        <p class="text-[10px] text-stone-400 mt-1">{{ $r['reviewer'] }} · {{ $r['reviewed_at'] }}</p>
                                    @endif
                                </td>
                                <td class="text-stone-500">{{ $r['requested_at'] }}</td>
                                <td class="text-center">
                                    @if ($r['status'] === 'pending')
                                        <button type="button" wire:click="openReview({{ $r['id'] }})" class="btn-ghost !py-2 !px-4 !text-xs">
                                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v9h-9"/></svg>
                                            بررسی
                                        </button>
                                    @else
                                        <span class="text-[11px] text-stone-300">تعیین‌تکلیف شده</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-stone-400 text-xs">درخواستی یافت نشد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="adm-table-foot text-xs text-stone-500" wire:loading.remove>
            @if ($rows->lastPage() > 1)
                <span>{{ $rows->total() }} درخواست — صفحه {{ $rows->currentPage() }} از {{ $rows->lastPage() }}</span>
                <div class="flex gap-2">
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" @disabled($rows->onFirstPage()) wire:click="previousPage">قبلی</button>
                    <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs" @disabled($rows->onLastPage()) wire:click="nextPage">بعدی</button>
                </div>
            @else
                <span>{{ $rows->total() }} درخواست</span>
            @endif
        </div>
    </section>

    {{-- مودال تعیین‌تکلیف — ثبت نهایی با همان endpoint کنترلر --}}
    @if (count($review))
        <div class="ui-modal-backdrop flex" x-data x-cloak>
            <div class="absolute inset-0" wire:click="$set('review', [])" aria-hidden="true"></div>
            <form class="ui-modal adm-modal-sm adm-modal-text-start" data-tone="info" role="dialog" aria-modal="true" novalidate
                  x-data="{
                      busy: false,
                      async submit(action) {
                          if (this.busy) return;
                          this.busy = true;
                          try {
                              const res = await App.ajax('/admin/withdrawals/' + $wire.review.id + '/review', {
                                  method: 'PATCH',
                                  body: { action, note: $wire.reviewNote.trim() || null },
                              });
                              const data = await res.json().catch(() => ({}));
                              if (res.ok) {
                                  App.toast(data.message, 'success');
                                  $wire.review = [];
                                  $wire.reviewNote = '';
                              } else {
                                  App.toast(data.message || 'خطا', 'error');
                              }
                          } finally {
                              this.busy = false;
                          }
                      }
                  }">
                <div class="adm-modal-head">
                    <h3 class="text-sm font-extrabold text-stone-800">تعیین‌تکلیف برداشت</h3>
                    <button type="button" class="adm-modal-x" wire:click="$set('review', [])" aria-label="بستن">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <div class="adm-modal-body space-y-4">
                    <div class="rounded-2xl bg-stone-50 border border-stone-100 px-4 py-3 space-y-1.5 text-xs">
                        <div class="flex justify-between"><span class="text-stone-400">دارنده کیف پول</span><strong class="text-stone-700">{{ $review['holder'] }}</strong></div>
                        <div class="flex justify-between"><span class="text-stone-400">مبلغ</span><strong class="text-blue-600">{{ fa_money($review['amount']) }}</strong></div>
                    </div>

                    <div>
                        <label class="lbl" for="w-note">یادداشت (اختیاری — مثلاً شماره پیگیری واریز)</label>
                        <textarea id="w-note" class="field" rows="2" wire:model="reviewNote"></textarea>
                    </div>
                </div>

                <div class="adm-modal-foot">
                    <div class="adm-modal-actions-equal">
                        <button type="button" class="btn-primary btn-shine !py-2.5" x-on:click="submit('pay')" x-bind:disabled="busy">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            پرداخت شد
                        </button>
                        <button type="button" class="btn-danger-soft !py-2.5" x-on:click="submit('reject')" x-bind:disabled="busy">رد و بازگشت مبلغ</button>
                    </div>
                    <button type="button" class="btn-ghost !py-2.5 w-full" wire:click="$set('review', [])">انصراف</button>
                </div>
            </form>
        </div>
    @endif

</div>
