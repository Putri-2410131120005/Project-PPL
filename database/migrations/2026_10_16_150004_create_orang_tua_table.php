<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orang_tua', function (Blueprint $table) {
            $table->increments('id_orang_tua');
            $table->unsignedInteger('id_pengguna')->unique();
            $table->string('no_telepon', 20)->nullable();
            $table->string('alamat', 255)->nullable();

            $table->foreign('id_pengguna')->references('id_pengguna')->on('pengguna')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orang_tua');
    }
};
