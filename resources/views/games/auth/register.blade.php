<x-games.shell title="Crear cuenta · Juegos bíblicos" :noindex="true">
    <div class="mx-auto grid max-w-4xl gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="rounded-card bg-white p-6 shadow-card ring-1 ring-black/5 sm:p-8">
            <h1 class="text-2xl font-extrabold">Crea tu cuenta de jugador</h1>
            <p class="mt-1 text-muted">Guarda tu avance, desbloquea los {{ \App\Services\GameCatalog::LEVELS }} niveles de cada juego y gana premios.</p>

            <form method="POST" action="{{ route('player.register') }}" class="mt-6 space-y-5" novalidate>
                @csrf
                <x-games.field name="nickname" label="Apodo (lo que verán los demás)" hint="De 3 a 20 letras o números, sin espacios. No uses tu nombre completo." autocomplete="nickname" required maxlength="20" />
                <x-games.field name="name" label="Tu nombre" hint="Privado: solo lo ve el equipo para entregarte un premio." autocomplete="name" required maxlength="100" />
                <x-games.field name="email" type="email" label="Correo electrónico" hint="Privado: para recuperar tu contraseña." autocomplete="email" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-games.field name="password" type="password" label="Contraseña" hint="Mínimo 8 caracteres." autocomplete="new-password" required />
                    <x-games.field name="password_confirmation" type="password" label="Repite la contraseña" autocomplete="new-password" required />
                </div>

                <div class="space-y-3 rounded-control bg-cream-light p-4 ring-1 ring-line">
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="age" value="1" @checked(old('age')) class="mt-0.5 size-5 shrink-0 rounded border-stone-300" @error('age') aria-invalid="true" @enderror>
                        <span>Tengo <strong>14 años o más</strong>.</span>
                    </label>
                    @error('age')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 size-5 shrink-0 rounded border-stone-300" @error('terms') aria-invalid="true" @enderror>
                        <span>Leí y acepto el <a href="{{ route('games.privacy') }}" target="_blank" rel="noopener" class="font-semibold text-brand-strong underline">aviso de privacidad de los juegos</a><span class="sr-only"> (pestaña nueva)</span>.</span>
                    </label>
                    @error('terms')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                </div>

                <x-ui.button type="submit" class="w-full">Crear mi cuenta</x-ui.button>
                <p class="text-center text-sm text-muted">¿Ya tienes cuenta? <a href="{{ route('player.login') }}" class="font-semibold text-brand-strong underline">Ingresa</a></p>
            </form>
        </div>

        <aside class="space-y-4 text-sm">
            <div class="rounded-card bg-cream p-5 ring-1 ring-line">
                <h2 class="font-display text-lg font-bold text-primary">¿Menor de 14 años?</h2>
                <p class="mt-1 text-ink/80">Puedes jugar los primeros {{ \App\Services\GameCatalog::GUEST_LEVELS }} niveles de cada juego sin cuenta, solo o con tu familia.</p>
                <a href="{{ route('games.index') }}" class="mt-2 inline-flex font-semibold text-brand-strong underline">Ir a los juegos</a>
            </div>
            <div class="rounded-card bg-white p-5 shadow-card ring-1 ring-black/5">
                <h2 class="font-display text-lg font-bold text-primary">Qué es público</h2>
                <p class="mt-1 text-muted">Solo tu apodo, tus estrellas, niveles y medallas (tarjeta de logros y ranking). Puedes ocultarlos o borrar tu cuenta cuando quieras.</p>
            </div>
        </aside>
    </div>
</x-games.shell>
