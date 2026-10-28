<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stempel legalisir: satu desain = satu posisi tetap di halaman sertifikat.
     *
     * Posisi & ukuran memakai milimeter (desimal) agar sama dengan satuan
     * blangko, preview browser, dan DOMPDF.
     */
    public function up(): void
    {
        Schema::create('stempel_legalisirs', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique();
            $table->string('gambar'); // path relatif di storage/app/public
            $table->decimal('x_mm', 6, 2)->default(237);
            $table->decimal('y_mm', 6, 2)->default(20);
            $table->decimal('lebar_mm', 6, 2)->default(40);
            $table->decimal('tinggi_mm', 6, 2)->default(40);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index('aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stempel_legalisirs');
    }
};
