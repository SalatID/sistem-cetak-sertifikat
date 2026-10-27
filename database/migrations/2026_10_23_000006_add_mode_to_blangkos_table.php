<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blangkos', function (Blueprint $table) {
            // canvas = blangko gambar + elemen teks; blade = file resources/views/blangko/*.blade.php
            $table->enum('mode', ['canvas', 'blade'])->default('canvas')->after('jenis');
            $table->string('view_blade', 120)->nullable()->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('blangkos', function (Blueprint $table) {
            $table->dropColumn(['mode', 'view_blade']);
        });
    }
};
