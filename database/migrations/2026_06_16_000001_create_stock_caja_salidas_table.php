<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_caja_salidas')) {
            Schema::create('stock_caja_salidas', function (Blueprint $table) {
                $table->id();

                $table->date('fecha')->index();
                $table->decimal('monto', 10, 2);
                $table->string('metodo', 20); // efectivo | transferencia
                $table->text('descripcion');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_caja_salidas');
    }
};

