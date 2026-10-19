{{-- Colores y fuentes de Panel › Apariencia. Va después de @vite para prevalecer sobre app.css. --}}
@php($themeCss = \App\Support\Theme::css(\App\Models\SiteSetting::current()))
@if ($themeCss !== '')
    <style id="tema-del-sitio">{!! $themeCss !!}</style>
@endif
