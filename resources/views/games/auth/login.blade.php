<x-games.shell title="Ingresar · Juegos bíblicos" :noindex="true">
    <div class="mx-auto max-w-md rounded-card bg-white p-6 shadow-card ring-1 ring-black/5 sm:p-8">
        <h1 class="text-2xl font-extrabold">Ingresa a tu cuenta</h1>
        <p class="mt-1 text-muted">Para seguir donde te quedaste y reclamar tus premios.</p>

        <form method="POST" action="{{ route('player.login') }}" class="mt-6 space-y-5" novalidate>
            @csrf
            <x-games.field name="email" type="email" label="Correo electrónico" autocomplete="username" required autofocus />
            <x-games.field name="password" type="password" label="Contraseña" autocomplete="current-password" required />
            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                <label class="inline-flex min-h-11 items-center gap-2">
                    <input type="checkbox" name="remember" value="1" checked class="size-5 rounded border-stone-300"> Mantener la sesión iniciada
                </label>
                <a href="{{ route('player.password.request') }}" class="font-semibold text-brand-strong underline">Olvidé mi contraseña</a>
            </div>
            <x-ui.button type="submit" class="w-full">Ingresar</x-ui.button>
        </form>
        <p class="mt-6 border-t border-line pt-5 text-center text-sm text-muted">
            ¿Aún no tienes cuenta? <a href="{{ route('player.register') }}" class="font-semibold text-brand-strong underline">Créala gratis</a>
        </p>
    </div>
</x-games.shell>
