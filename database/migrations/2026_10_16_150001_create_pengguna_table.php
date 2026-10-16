<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->increments('id_pengguna');
            $table->unsignedInteger('id_role');
            $table->string('nama', 100);
            $table->string('username', 50)->nullable()->unique();
            $table->string('password', 255);
            $table->string('email', 100)->unique();
            $table->enum('status_akun', ['Aktif', 'Nonaktif'])->default('Aktif');

            $table->foreign('id_role')->references('id_role')->on('role')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }
};
