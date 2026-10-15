<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('name', 150);
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('price_range', 100)->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('maps_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('tourist_places', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('distance_note', 150)->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('maps_url', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('donation_methods', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('label', 150);
            $table->string('holder_name', 150)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('cci', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('qr_image_path')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_methods');
        Schema::dropIfExists('tourist_places');
        Schema::dropIfExists('accommodations');
        Schema::dropIfExists('faqs');
    }
};
