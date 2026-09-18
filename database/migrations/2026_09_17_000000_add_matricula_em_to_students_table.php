<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('matricula_em')->nullable()->after('modalidade');
        });

        // Quem já estava matriculado começou a contar o ano no dia do cadastro.
        DB::table('students')->update(['matricula_em' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('matricula_em');
        });
    }
};
