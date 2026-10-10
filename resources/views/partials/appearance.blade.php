{{-- پوستهٔ پویا (Appearance) — پالت رنگی اختصاصی هر پنل
     ورودی: $panel (admin | app | coffeenet | operator | org | front)
     باید «بعد از» theme.css / استایل‌های اصلی لود شود تا برندهٔ cascade باشد. --}}
@php
    $appearanceCss = \App\Support\Appearance::css($panel ?? 'admin');
@endphp
<style id="appearance-css" data-panel="{{ $panel ?? 'admin' }}">
{!! $appearanceCss !!}
</style>
