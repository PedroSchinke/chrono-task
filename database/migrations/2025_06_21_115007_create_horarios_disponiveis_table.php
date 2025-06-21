<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("
            CREATE TABLE horarios_disponiveis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_maquina INT NOT NULL,
                dia_semana ENUM('domingo','segunda','terca','quarta','quinta','sexta','sabado') NOT NULL,
                hora_inicio TIME NOT NULL,
                hora_fim TIME NOT NULL,
                FOREIGN KEY (id_maquina) REFERENCES maquinas(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            );
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_disponiveis');
    }
};
