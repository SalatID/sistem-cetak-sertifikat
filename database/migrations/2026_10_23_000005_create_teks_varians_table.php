<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teks_varians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blangko_id')->constrained('blangkos')->cascadeOnDelete();
            $table->string('key', 50);        // mis. 'prestasi', 'pemasalan'
            $table->string('judul', 100);     // label di dropdown
            $table->text('isi');             // teks panjang (boleh pakai {NAMA} dll.)
            $table->timestamps();

            $table->unique(['blangko_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teks_varians');
    }
};
