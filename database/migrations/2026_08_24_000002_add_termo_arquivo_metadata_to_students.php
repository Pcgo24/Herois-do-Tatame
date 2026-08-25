<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('termo_arquivo_nome')->nullable()->after('termo_arquivo');
            $table->timestamp('termo_arquivo_enviado_em')->nullable()->after('termo_arquivo_nome');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['termo_arquivo_nome', 'termo_arquivo_enviado_em']);
        });
    }
};
