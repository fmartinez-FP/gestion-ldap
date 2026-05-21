<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aplicaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 50)->unique();   // ffe, guardias, inventarios
            $table->string('descripcion', 255)->nullable();
            $table->string('url', 255)->nullable();
            $table->string('color', 7)->default('#3B82F6');
            $table->string('icono', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aplicaciones');
    }
};
