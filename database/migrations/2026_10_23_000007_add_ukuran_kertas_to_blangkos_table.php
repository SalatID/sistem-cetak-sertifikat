<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blangkos', function (Blueprint $table) {
            // Ukuran kertas standar; lebar/tinggi mm diturunkan dari ukuran + orientasi.
            $table->enum('ukuran_kertas', ['a4', 'f4', 'letter', 'custom'])
                ->default('a4')
                ->after('mode');
        });

        // Baris lama: tandai sesuai dimensinya agar tidak salah label.
        DB::table('blangkos')->orderBy('id')->get()->each(function (object $blangko): void {
            $dimensi = [(int) $blangko->lebar_mm, (int) $blangko->tinggi_mm];

            $ukuran = match (true) {
                in_array($dimensi, [[210, 297], [297, 210]], true) => 'a4',
                in_array($dimensi, [[215, 330], [330, 215]], true) => 'f4',
                in_array($dimensi, [[216, 279], [279, 216]], true) => 'letter',
                default => 'custom',
            };

            DB::table('blangkos')->where('id', $blangko->id)->update(['ukuran_kertas' => $ukuran]);
        });
    }

    public function down(): void
    {
        Schema::table('blangkos', function (Blueprint $table) {
            $table->dropColumn('ukuran_kertas');
        });
    }
};
