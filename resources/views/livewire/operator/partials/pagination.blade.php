{{-- صفحه‌بندی سبک پنل اپراتور [Task 5]
     همان ظاهر pg-btn/pg-active؛ ناوبری صفحه با gotoPage کامپوننت.
     $pageName اختیاری است تا دو جدول صفحه‌بندی‌شده در یک صفحه (درآمد) تداخل نکنند --}}
@if ($paginator->hasPages())
    <div class="flex gap-2">
        <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs"
                wire:click="gotoPage({{ $paginator->currentPage() - 1 }}, '{{ $pageName ?? 'page' }}')"
                @disabled($paginator->onFirstPage()) aria-label="صفحهٔ قبلی">قبلی</button>

        <span class="text-stone-400 leading-7">{{ fa_number($paginator->total()) }} — صفحه {{ fa_number($paginator->currentPage()) }} از {{ fa_number($paginator->lastPage()) }}</span>

        <button type="button" class="pg-btn btn-ghost !py-1.5 !px-3 !text-xs"
                wire:click="gotoPage({{ $paginator->currentPage() + 1 }}, '{{ $pageName ?? 'page' }}')"
                @disabled(! $paginator->hasMorePages()) aria-label="صفحهٔ بعدی">بعدی</button>
    </div>
@elseif (isset($summaryLabel) && $summaryLabel)
    <span>{{ $summaryLabel }}</span>
@endif
