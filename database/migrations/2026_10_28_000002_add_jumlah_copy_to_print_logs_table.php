<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jumlah copy per sertifikat. Total halaman = jumlah_data * jumlah_copy.
     */
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_copy')->default(1)->after('jumlah_data');
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            $table->dropColumn('jumlah_copy');
        });
    }
};
