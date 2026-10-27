<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kontingen_id')->constrained('kontingens')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->string('jabatan', 60)->default('Coach');
            $table->timestamps();

            $table->index('kontingen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officials');
    }
};
