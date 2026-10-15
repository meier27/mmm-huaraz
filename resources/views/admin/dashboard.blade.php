<x-layouts.admin title="Inicio">
    <h1 class="text-2xl font-bold">Hola, {{ auth()->user()->name }}</h1>
    <p class="mt-1 text-muted">Rol: {{ auth()->user()->role->label() }}</p>

    @can('manage-content')
        @php
            $sections = [
                ['route' => 'admin.program', 'label' => 'Actividades publicadas', 'count' => \App\Models\ProgramItem::published()->count()],
                ['route' => 'admin.venues', 'label' => 'Ubicaciones publicadas', 'count' => \App\Models\Venue::published()->count()],
                ['route' => 'admin.donation-methods', 'label' => 'Métodos de donación publicados', 'count' => \App\Models\DonationMethod::published()->count()],
                ['route' => 'admin.accommodations', 'label' => 'Hospedajes publicados', 'count' => \App\Models\Accommodation::published()->count()],
                ['route' => 'admin.tourist-places', 'label' => 'Lugares turísticos publicados', 'count' => \App\Models\TouristPlace::published()->count()],
                ['route' => 'admin.faqs', 'label' => 'Preguntas publicadas', 'count' => \App\Models\Faq::published()->count()],
            ];
        @endphp

        <h2 class="mt-8 text-lg font-bold">Estado del contenido</h2>
        <ul class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($sections as $section)
                <li>
                    <a href="{{ route($section['route']) }}" wire:navigate class="block rounded-card bg-white p-4 shadow-card hover:ring-2 hover:ring-brand">
                        <span class="block font-display text-3xl font-bold text-earth">{{ $section['count'] }}</span>
                        <span class="text-sm text-muted">{{ $section['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endcan
</x-layouts.admin>
