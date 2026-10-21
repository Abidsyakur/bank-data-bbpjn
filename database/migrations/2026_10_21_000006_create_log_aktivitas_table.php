<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            // null untuk aktivitas non-dokumen (login, logout, backup, dll.)
            $table->foreignId('dokumen_id')->nullable()->constrained('dokumen')->nullOnDelete();
            $table->string('aksi', 50)->index();
            $table->string('keterangan', 255)->nullable();
            $table->timestamp('waktu')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
