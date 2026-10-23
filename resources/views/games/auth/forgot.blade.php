<x-games.shell title="Recuperar contraseña · Juegos bíblicos" :noindex="true">
    <div class="mx-auto max-w-md rounded-card bg-white p-6 shadow-card ring-1 ring-black/5 sm:p-8">
        <h1 class="text-2xl font-extrabold">Recupera tu contraseña</h1>
        <p class="mt-1 text-muted">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una contraseña nueva.</p>

        <form method="POST" action="{{ route('player.password.email') }}" class="mt-6 space-y-5" novalidate>
            @csrf
            <x-games.field name="email" type="email" label="Correo electrónico" autocomplete="email" required autofocus />
            <x-ui.button type="submit" class="w-full">Enviar enlace</x-ui.button>
        </form>
        <p class="mt-6 text-center text-sm"><a href="{{ route('player.login') }}" class="font-semibold text-brand-strong underline">Volver a ingresar</a></p>
    </div>
</x-games.shell>
