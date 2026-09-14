<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O identificador de login muda de CPF para nome de usuário. Nenhum
        // usuário existente tem username, logo nenhum conseguiria entrar; e não
        // há dado real a preservar. Zerar a tabela deixa o ProfessorSeeder
        // recriar o primeiro usuário no mesmo deploy, em vez de sobrar um
        // cadastro fantasma que ninguém consegue usar nem remover.
        DB::table('users')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_cpf_unique');
            $table->dropColumn('cpf');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->unique()->after('name');
            $table->string('email')->nullable()->change();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        DB::table('users')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('username');
            $table->string('email')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf', 11)->nullable()->unique()->after('name');
        });
    }
};
