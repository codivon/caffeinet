{{-- صفحه‌بندی سبک مشترک بخش‌های Livewire پنل کافی‌نت [Task 4]
     همان ظاهر pg-btn/pg-active پنل؛ ناوبری صفحه با اکشن gotoPage کامپوننت --}}
@if ($paginator->hasPages())
    <div class="flex items-center gap-1.5">
        <button type="button" class="pg-btn" wire:click="gotoPage({{ $paginator->currentPage() - 1 }})"
                @disabled($paginator->onFirstPage()) aria-label="صفحهٔ قبلی">قبلی</button>

        @foreach ($paginator->toArray()['links'] as $element)
            @if (is_string($element))
                <span class="text-stone-300 text-xs px-1">…</span>
            @elseif (is_array($element))
                <button type="button" class="pg-btn {{ $element['active'] ? 'pg-active' : '' }}"
                        wire:click="gotoPage({{ $element['page'] }})">{{ fa_digits((string) $element['page']) }}</button>
            @endif
        @endforeach

        <button type="button" class="pg-btn" wire:click="gotoPage({{ $paginator->currentPage() + 1 }})"
                @disabled($paginator->hasMorePages() === false) aria-label="صفحهٔ بعدی">بعدی</button>
    </div>
@endif
