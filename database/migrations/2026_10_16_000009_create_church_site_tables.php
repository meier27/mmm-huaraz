<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La web pasa a ser el sitio del MMM Huaraz; la convención queda dentro de
 * «Eventos». Los datos de contacto y redes se trasladan de la convención al
 * sitio, que es a quien pertenecen.
 */
return new class extends Migration
{
    private const CONTACT_COLUMNS = ['contact_phone', 'contact_whatsapp', 'contact_email', 'facebook_url', 'instagram_url', 'youtube_url', 'tiktok_url'];

    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('church_name', 150);
            $table->string('tagline', 255)->nullable();
            $table->text('welcome_text')->nullable();
            $table->text('history')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->text('service_schedule')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_whatsapp', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('facebook_url', 500)->nullable();
            $table->string('instagram_url', 500)->nullable();
            $table->string('youtube_url', 500)->nullable();
            $table->string('tiktok_url', 500)->nullable();
            $table->string('bible_version', 10)->default('rvr');
            $table->string('bethel_channel_id', 40)->nullable();
            $table->timestamps();
        });

        $event = DB::table('event_settings')->first();
        DB::table('site_settings')->insert(array_merge(
            ['church_name' => 'Movimiento Misionero Mundial – Huaraz', 'bethel_channel_id' => config('bible.bethel_channel_id'), 'created_at' => now(), 'updated_at' => now()],
            $event ? array_intersect_key((array) $event, array_flip(self::CONTACT_COLUMNS)) : [],
        ));

        Schema::table('event_settings', function (Blueprint $table) {
            $table->dropColumn(self::CONTACT_COLUMNS);
        });

        Schema::create('leaders', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('role', 150);
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('teachings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('category', 30);
            $table->string('excerpt', 500)->nullable();
            $table->text('body');
            $table->string('video_id', 20)->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('author_name', 150)->nullable();
            $table->date('published_on');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'published_on']);
            $table->index('category');
        });

        Schema::create('verses', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 100);
            $table->text('text');
            $table->string('version', 50)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('church_events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('time_label', 100)->nullable();
            $table->string('location', 200)->nullable();
            $table->string('summary', 500)->nullable();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('external_url', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_events');
        Schema::dropIfExists('verses');
        Schema::dropIfExists('teachings');
        Schema::dropIfExists('leaders');

        Schema::table('event_settings', function (Blueprint $table) {
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_whatsapp', 30)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('facebook_url', 500)->nullable();
            $table->string('instagram_url', 500)->nullable();
            $table->string('youtube_url', 500)->nullable();
            $table->string('tiktok_url', 500)->nullable();
        });

        if ($site = DB::table('site_settings')->first()) {
            DB::table('event_settings')->update(array_intersect_key((array) $site, array_flip(self::CONTACT_COLUMNS)));
        }

        Schema::dropIfExists('site_settings');
    }
};
