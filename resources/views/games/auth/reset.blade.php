<x-games.shell title="Nueva contraseña · Juegos bíblicos" :noindex="true">
    <div class="mx-auto max-w-md rounded-card bg-white p-6 shadow-card ring-1 ring-black/5 sm:p-8">
        <h1 class="text-2xl font-extrabold">Crea una contraseña nueva</h1>

        <form method="POST" action="{{ route('player.password.update') }}" class="mt-6 space-y-5" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-games.field name="email" type="email" label="Correo electrónico" :value="$email" autocomplete="username" required />
            <x-games.field name="password" type="password" label="Contraseña nueva" hint="Mínimo 8 caracteres." autocomplete="new-password" required autofocus />
            <x-games.field name="password_confirmation" type="password" label="Repite la contraseña" autocomplete="new-password" required />
            <x-ui.button type="submit" class="w-full">Guardar contraseña</x-ui.button>
        </form>
    </div>
</x-games.shell>
