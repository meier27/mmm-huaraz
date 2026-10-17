<x-layouts.admin title="Iniciar sesión">
    <div class="mx-auto mt-8 max-w-md rounded-card bg-white p-6 shadow-card sm:p-8">
        <h1 class="text-2xl font-bold">Acceso al panel</h1>
        <p class="mt-1 text-sm text-muted">Solo para el equipo organizador.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5" novalidate>
            @csrf

            @error('email')
                <x-ui.alert type="error" id="email-error">{{ $message }}</x-ui.alert>
            @enderror

            <div>
                <label for="email" class="block text-sm font-medium">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                       class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 px-3 py-2 focus:border-brand-strong">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Contraseña</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="mt-1 block min-h-11 w-full rounded-control border border-stone-300 px-3 py-2 focus:border-brand-strong">
                @error('password')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-stone-300">
                Mantener la sesión iniciada
            </label>

            <x-ui.button type="submit" class="w-full">Entrar</x-ui.button>
        </form>
    </div>
</x-layouts.admin>
