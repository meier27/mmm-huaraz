<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * La convención deja de ser una configuración única: cualquier evento puede
 * activar módulos (programa, sedes, comidas con reserva, donaciones,
 * hospedaje y turismo). Los datos actuales de la convención se convierten en
 * un evento y cada registro queda enlazado a él. Los enlaces/QR de reservas no
 * cambian (dependen del token, no del evento).
 */
return new class extends Migration
{
    /** Tablas cuyo contenido pertenece a un evento. */
    private const SCOPED = ['program_days', 'venues', 'dishes', 'accommodations', 'tourist_places', 'reservations'];

    public function up(): void
    {
        Schema::table('church_events', function (Blueprint $table) {
            $table->string('organization', 150)->nullable()->after('slug');
            $table->string('theme', 150)->nullable()->after('organization');
            $table->string('city', 100)->nullable()->after('theme');
            $table->boolean('is_featured')->default(false)->after('is_published');

            $table->boolean('has_program')->default(false);
            $table->boolean('has_venues')->default(false);
            $table->boolean('has_food')->default(false);
            $table->boolean('has_donations')->default(false);
            $table->boolean('has_accommodations')->default(false);
            $table->boolean('has_tourism')->default(false);

            $table->boolean('reservations_open')->default(false);
            $table->string('reservations_title', 150)->nullable();
            $table->text('reservation_instructions')->nullable();
            $table->unsignedTinyInteger('deposit_min_percent')->nullable();

            $table->index(['is_published', 'is_featured']);
        });

        foreach (self::SCOPED as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('church_event_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            });
        }

        // Los aportes pueden no estar ligados a un evento (donación general).
        Schema::table('donations', function (Blueprint $table) {
            $table->foreignId('church_event_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $this->migrateConvention();

        // Dos eventos pueden tener programa el mismo día.
        Schema::table('program_days', function (Blueprint $table) {
            $table->dropUnique(['date']);
            $table->unique(['church_event_id', 'date']);
        });

        foreach (self::SCOPED as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('church_event_id')->nullable(false)->change();
            });
        }

        Schema::dropIfExists('event_settings');
    }

    /**
     * Crea el evento de la convención con lo que haya en event_settings (o los
     * datos verificados de config/convention.php) solo si existe algo que
     * migrar, y le asigna todo el contenido existente.
     */
    private function migrateConvention(): void
    {
        $setting = DB::table('event_settings')->first();
        $hasContent = collect([...self::SCOPED, 'donations'])->contains(fn ($t) => DB::table($t)->exists());

        if (! $setting && ! $hasContent) {
            return;
        }

        $name = $setting->name ?? config('convention.name');
        $startsOn = $setting->starts_on ?? config('convention.starts_at');
        $slug = Str::slug($name.' '.substr($startsOn, 0, 4)) ?: 'convencion';
        for ($i = 2; DB::table('church_events')->where('slug', $slug)->exists(); $i++) {
            $slug = Str::slug($name.' '.substr($startsOn, 0, 4)).'-'.$i;
        }

        $eventId = DB::table('church_events')->insertGetId([
            'title' => $name,
            'slug' => $slug,
            'organization' => $setting->organization ?? config('convention.organization'),
            'theme' => $setting->theme ?? config('convention.theme'),
            'city' => $setting->city ?? config('convention.city'),
            'starts_on' => $startsOn,
            'ends_on' => $setting->ends_on ?? config('convention.ends_at'),
            'location' => config('convention.venue'),
            'body' => $setting->summary ?? null,
            'is_published' => true,
            'is_featured' => true,
            'has_program' => true,
            'has_venues' => true,
            'has_food' => true,
            'has_donations' => true,
            'has_accommodations' => true,
            'has_tourism' => true,
            'reservations_open' => (bool) ($setting->reservations_open ?? false),
            'reservations_title' => $setting->reservations_title ?? null,
            'reservation_instructions' => $setting->reservation_instructions ?? null,
            'deposit_min_percent' => $setting->deposit_min_percent ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([...self::SCOPED, 'donations'] as $name) {
            DB::table($name)->whereNull('church_event_id')->update(['church_event_id' => $eventId]);
        }
    }

    public function down(): void
    {
        Schema::create('event_settings', function (Blueprint $table) {
            $table->id();
            $table->string('organization', 150);
            $table->string('name', 150);
            $table->string('theme', 150);
            $table->string('city', 100);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('summary')->nullable();
            $table->boolean('reservations_open')->default(false);
            $table->string('reservations_title', 150)->nullable();
            $table->text('reservation_instructions')->nullable();
            $table->unsignedTinyInteger('deposit_min_percent')->nullable();
            $table->timestamps();
        });

        $event = DB::table('church_events')->where('is_featured', true)->orderBy('id')->first();
        if ($event) {
            DB::table('event_settings')->insert([
                'organization' => $event->organization ?? config('convention.organization'),
                'name' => $event->title,
                'theme' => $event->theme ?? '',
                'city' => $event->city ?? '',
                'starts_on' => $event->starts_on,
                'ends_on' => $event->ends_on ?? $event->starts_on,
                'summary' => $event->body,
                'reservations_open' => $event->reservations_open,
                'reservations_title' => $event->reservations_title,
                'reservation_instructions' => $event->reservation_instructions,
                'deposit_min_percent' => $event->deposit_min_percent,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // MariaDB usa el índice único compuesto para la clave foránea:
        // primero la clave, luego el índice, luego la columna.
        Schema::table('program_days', function (Blueprint $table) {
            $table->dropForeign(['church_event_id']);
            $table->dropUnique(['church_event_id', 'date']);
            $table->dropColumn('church_event_id');
        });

        foreach (array_diff([...self::SCOPED, 'donations'], ['program_days']) as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('church_event_id');
            });
        }

        // Falla a propósito si dos eventos tienen programa el mismo día:
        // volver atrás perdería datos.
        Schema::table('program_days', function (Blueprint $table) {
            $table->unique('date');
        });

        // El evento vuelve a ser la configuración única: se quita de la lista
        // para que una nueva migración no lo duplique.
        if ($event) {
            DB::table('church_events')->where('id', $event->id)->delete();
        }

        Schema::table('church_events', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'is_featured']);
            $table->dropColumn([
                'organization', 'theme', 'city', 'is_featured',
                'has_program', 'has_venues', 'has_food', 'has_donations', 'has_accommodations', 'has_tourism',
                'reservations_open', 'reservations_title', 'reservation_instructions', 'deposit_min_percent',
            ]);
        });
    }
};
