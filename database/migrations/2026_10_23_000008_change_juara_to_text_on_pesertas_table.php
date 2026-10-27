<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubah kolom `juara` dari angka (1/2/3) menjadi teks bebas.
     *
     * Nilai yang dipakai panitia bisa berupa label seperti "Peserta" atau
     * "Pesilat Terbaik"; nilai lama 1/2/3 tetap dipertahankan sebagai teks.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $check = DB::selectOne(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ?
                   AND CONSTRAINT_TYPE = ?',
                ['pesertas', 'pesertas_juara_check', 'CHECK'],
            );

            if ($check) {
                DB::statement('ALTER TABLE pesertas DROP CHECK pesertas_juara_check');
            }
        }

        Schema::table('pesertas', function (Blueprint $table) {
            $table->string('juara', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Nilai teks tidak muat di kolom angka; kosongkan agar CHECK bisa dipasang.
        DB::table('pesertas')
            ->whereNotNull('juara')
            ->whereNotIn('juara', ['1', '2', '3'])
            ->update(['juara' => null]);

        Schema::table('pesertas', function (Blueprint $table) {
            $table->unsignedTinyInteger('juara')->nullable()->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE pesertas ADD CONSTRAINT pesertas_juara_check
                CHECK (juara IN (1,2,3) OR juara IS NULL)');
        }
    }
};
