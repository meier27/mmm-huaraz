<x-layouts.admin title="Inicio">
    <h1 class="text-2xl font-bold">Hola, {{ auth()->user()->name }}</h1>
    <p class="mt-1 text-muted">Rol: {{ auth()->user()->role->label() }}</p>
</x-layouts.admin>
