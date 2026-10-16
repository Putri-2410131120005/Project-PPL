<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->increments('id_kelas');
            $table->string('nama_kelas', 50);
            $table->string('tingkat', 10);
            $table->string('tahun_ajaran', 20);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
