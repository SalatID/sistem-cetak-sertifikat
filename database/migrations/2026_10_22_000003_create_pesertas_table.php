<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontingen_id')->constrained('kontingens')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('kategori', 120);
            $table->string('kelas', 120)->nullable();
            $table->unsignedTinyInteger('juara')->nullable();
            $table->unsignedInteger('no_sertifikat')->nullable()->unique();
            $table->string('no_sk', 100)->nullable();
            $table->timestamps();

            $table->index('kategori');
            $table->index('juara');
        });

        DB::statement('ALTER TABLE pesertas ADD CONSTRAINT pesertas_juara_check
            CHECK (juara IN (1,2,3) OR juara IS NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pesertas');
    }
};
