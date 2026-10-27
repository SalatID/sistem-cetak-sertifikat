<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('jenis_dokumen', ['sertifikat_peserta', 'sk_juara', 'sertifikat_official']);
            $table->foreignId('peserta_id')->nullable()->constrained('pesertas')->nullOnDelete();
            $table->foreignId('official_id')->nullable()->constrained('officials')->nullOnDelete();
            $table->foreignId('kontingen_id')->nullable()->constrained('kontingens')->nullOnDelete();
            $table->unsignedInteger('jumlah_data')->default(1);
            $table->string('nomor_sk', 100)->nullable();
            $table->string('konteks', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_logs');
    }
};
