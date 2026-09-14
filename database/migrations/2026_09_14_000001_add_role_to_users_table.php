<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 'professor' usa o sistema; 'admin' só administra os professores e
            // não aparece para eles. String em vez de enum para não amarrar o
            // Postgres a um tipo próprio por dois valores.
            $table->string('role', 20)->default('professor')->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
