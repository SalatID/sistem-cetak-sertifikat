<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Label "Peserta" bukan juara, jadi tidak berhak menyimpan nomor sertifikat.
     *
     * Nomor yang sudah terlanjur diberikan pada baris berlabel Peserta
     * dikosongkan. Penomoran baru juga dicegah di NomorSertifikat::assign().
     */
    public function up(): void
    {
        DB::table('pesertas')
            ->whereRaw('LOWER(TRIM(juara)) = ?', ['peserta'])
            ->whereNotNull('no_sertifikat')
            ->update(['no_sertifikat' => null]);
    }

    public function down(): void
    {
        // Nomor lama tidak dapat dikembalikan secara aman.
    }
};
