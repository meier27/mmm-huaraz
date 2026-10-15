<?php

namespace Tests\Concerns;

use App\Models\Dish;
use App\Models\DishOffering;
use App\Models\EventSetting;

trait CreatesOfferings
{
    protected function offering(array $attributes = [], array $dish = []): DishOffering
    {
        $dishModel = Dish::create(array_merge(['name' => 'Plato de prueba', 'is_published' => true], $dish));

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

    protected function openReservations(): void
    {
        EventSetting::query()->firstOrCreate([], EventSetting::defaults())->update(['reservations_open' => true]);
    }
}
