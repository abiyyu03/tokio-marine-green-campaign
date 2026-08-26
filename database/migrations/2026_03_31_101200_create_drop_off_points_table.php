<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direktori "Temukan Rumah Pilah Terdekat!" di halaman hasil.
 * Tiap kartu menampilkan foto, nama, alamat, jam operasional, nomor telepon,
 * lalu tombol "Lihat di Maps" dan "Hubungi WhatsApp".
 *
 * `latitude`/`longitude` disiapkan untuk pengurutan "terdekat" bila nanti
 * lokasi pengguna tersedia; untuk sekarang urutannya memakai `sort_order`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drop_off_points', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image_file')->nullable();
            $table->text('address');
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('opening_hours')->nullable();  // "Senin - Sabtu (08.00 - 16.00 WIB)"
            $table->string('phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('maps_url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drop_off_points');
    }
};
