<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('layanan_id')->constrained('layanan')->restrictOnDelete();
            $table->string('nomor_surat', 100)->nullable()->index();
            $table->string('judul', 255);
            $table->date('tanggal')->index();
            $table->string('file_path')->nullable();
            // null untuk layanan arsip (layanan.butuh_tindak_lanjut = false)
            $table->string('status', 20)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};
