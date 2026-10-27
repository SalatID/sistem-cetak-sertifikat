<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blangkos', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->enum('jenis', ['sertifikat', 'sk'])->default('sertifikat');
            $table->string('gambar')->nullable();          // path relatif di storage/app/public
            $table->enum('orientasi', ['landscape', 'portrait'])->default('landscape');
            $table->unsignedSmallInteger('lebar_mm')->default(297);
            $table->unsignedSmallInteger('tinggi_mm')->default(210);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blangkos');
    }
};
