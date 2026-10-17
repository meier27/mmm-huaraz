<?php

namespace Tests\Concerns;

use App\Models\ChurchEvent;
use App\Models\Dish;
use App\Models\DishOffering;

trait CreatesOfferings
{
    /**
     * Evento con el módulo de comidas: el destacado si ya lo tiene (p. ej. la
     * convención sembrada) o uno de prueba.
     */
    protected function foodEvent(): ChurchEvent
    {
        $featured = ChurchEvent::featured();

        if ($featured?->has_food) {
            return $featured;
        }

        return ChurchEvent::query()->where('slug', 'evento-de-prueba')->first() ?? ChurchEvent::create([
            'title' => 'Evento de prueba',
            'slug' => 'evento-de-prueba',
            'city' => 'Huaraz',
            'starts_on' => today()->toDateString(),
            'ends_on' => today()->addDays(5)->toDateString(),
            'is_published' => true,
            'is_featured' => true,
            'has_food' => true,
        ]);
    }

    protected function offering(array $attributes = [], array $dish = [], ?ChurchEvent $event = null): DishOffering
    {
        $dishModel = new Dish(array_merge(['name' => 'Plato de prueba', 'is_published' => true], $dish));
        $dishModel->church_event_id = ($event ?? $this->foodEvent())->id;
        $dishModel->save();

        return DishOffering::create(array_merge([
            'dish_id' => $dishModel->id,
            'serve_date' => today()->addDays(2)->toDateString(),
            'slot_start' => '12:00',
            'slot_end' => '14:00',
            'price' => '12.50',
            'stock' => 10,
            'is_active' => true,
        ], $attributes));
    }

    protected function openReservations(?ChurchEvent $event = null): void
    {
        ($event ?? $this->foodEvent())->update(['has_food' => true, 'reservations_open' => true]);
    }

    protected function reservationsUrl(?ChurchEvent $event = null): string
    {
        return route('events.reservations', $event ?? $this->foodEvent());
    }
}
