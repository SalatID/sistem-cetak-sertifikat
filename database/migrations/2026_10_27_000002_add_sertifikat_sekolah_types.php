<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah jenis blangko & log untuk sertifikat sekolah.
     *
     * Kolom enum diubah menjadi string agar penambahan jenis dokumen
     * berikutnya tidak memerlukan ALTER ENUM lagi.
     */
    public function up(): void
    {
        Schema::table('blangkos', function (Blueprint $table) {
            $table->string('jenis', 30)->default('sertifikat')->change();
        });

        Schema::table('print_logs', function (Blueprint $table) {
            $table->string('jenis_dokumen', 40)->change();
        });
    }

    public function down(): void
    {
        // Hapus data yang memakai jenis baru sebelum kolom dikembalikan ke enum.
        DB::table('print_logs')->where('jenis_dokumen', 'sertifikat_sekolah')->delete();
        DB::table('blangkos')->where('jenis', 'sertifikat_sekolah')->delete();

        Schema::table('print_logs', function (Blueprint $table) {
            $table->enum('jenis_dokumen', ['sertifikat_peserta', 'sk_juara', 'sertifikat_official'])->change();
        });

        Schema::table('blangkos', function (Blueprint $table) {
            $table->enum('jenis', ['sertifikat', 'sk'])->default('sertifikat')->change();
        });
    }
};
