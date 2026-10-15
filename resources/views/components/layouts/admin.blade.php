@props(['title' => 'Panel'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · Administración · {{ config('app.name') }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="min-h-dvh bg-stone-50">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-control focus:bg-white focus:px-4 focus:py-2">
        Saltar al contenido
    </a>

    @auth
        <header class="border-b border-line bg-white">
            <div class="container-content flex h-16 items-center justify-between gap-4">
                <a href="{{ route('admin.dashboard') }}" class="font-display font-bold text-earth">Panel MMM Huaraz</a>

                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden text-muted sm:inline">{{ auth()->user()->name }} · {{ auth()->user()->role->label() }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="ghost">Cerrar sesión</x-ui.button>
                    </form>
                </div>
            </div>
        </header>
    @endauth

    <main id="contenido" class="container-content py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
