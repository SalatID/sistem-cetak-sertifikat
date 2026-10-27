<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blangko_elemen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blangko_id')->constrained('blangkos')->cascadeOnDelete();

            $table->string('label', 100);                    // nama elemen di panel editor
            $table->enum('tipe', ['teks', 'data', 'varian', 'tabel'])->default('teks');

            // Posisi & ukuran dalam milimeter (satu unit untuk preview web & PDF)
            $table->unsignedSmallInteger('x_mm')->default(0);
            $table->unsignedSmallInteger('y_mm')->default(0);
            $table->unsignedSmallInteger('lebar_mm')->default(80);
            $table->unsignedSmallInteger('tinggi_mm')->default(10);

            $table->text('isi')->nullable();                 // teks bebas (tipe 'teks')
            $table->string('sumber_field', 50)->nullable(); // kolom data (tipe 'data')
            $table->string('varian_key', 50)->nullable();    // kunci varian (tipe 'varian')

            $table->string('font_family', 30)->default('sans');  // sans | serif | mono
            $table->unsignedSmallInteger('font_size')->default(11); // pt
            $table->boolean('tebal')->default(false);
            $table->boolean('miring')->default(false);
            $table->string('align', 10)->default('left');   // left | center | right | justify
            $table->string('valign', 10)->default('top');   // top | middle | bottom
            $table->string('warna', 20)->default('#0f172a');
            $table->unsignedSmallInteger('z_index')->default(10);
            $table->boolean('tampil')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blangko_elemen');
    }
};
