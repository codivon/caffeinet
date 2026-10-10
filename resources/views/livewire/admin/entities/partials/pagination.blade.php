{{-- صفحه‌بندی مشترک صفحات موجودیت — هم‌شکل adm-table-foot قدیمی (قبلی/بعدی + شمارنده) --}}
@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
    $unit = $paginatorUnit ?? 'رکورد';
@endphp
<div class="adm-table-foot text-xs text-stone-500" wire:key="pager-{{ $paginator->getPageName() }}">
    @if ($paginator->lastPage() > 1)
        <span>{{ fa_digits($paginator->total()) }} {{ $unit }} — صفحه {{ fa_digits($paginator->currentPage()) }} از {{ fa_digits($paginator->lastPage()) }}</span>
        <div class="flex gap-2">
            <button type="button" class="btn-ghost !py-1.5 !px-3 !text-xs" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" wire:target="previousPage" @disabled($paginator->onFirstPage())>قبلی</button>
            <button type="button" class="btn-ghost !py-1.5 !px-3 !text-xs" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" wire:target="nextPage" @disabled(! $paginator->hasMorePages())>بعدی</button>
        </div>
    @else
        <span>{{ fa_digits($paginator->total()) }} {{ $unit }}</span>
    @endif
</div>
