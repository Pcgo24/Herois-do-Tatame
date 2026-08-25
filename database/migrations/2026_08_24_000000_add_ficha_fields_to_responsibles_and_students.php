<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responsibles', function (Blueprint $table) {
            $table->string('rg', 20)->nullable()->after('cpf');
            $table->string('neighborhood', 80)->nullable()->after('address');
            $table->string('home_phone', 11)->nullable()->after('phone_number');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('school', 120)->nullable()->after('birth_date');
            $table->string('grade', 30)->nullable()->after('school');
            $table->string('father_name', 80)->nullable()->after('grade');
            $table->string('mother_name', 80)->nullable()->after('father_name');
            $table->boolean('no_father')->default(false)->after('mother_name');
            $table->boolean('no_mother')->default(false)->after('no_father');
            $table->string('phone', 11)->nullable()->after('no_mother');
            $table->string('email')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('responsibles', function (Blueprint $table) {
            $table->dropColumn(['rg', 'neighborhood', 'home_phone']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'school', 'grade', 'father_name', 'mother_name',
                'no_father', 'no_mother', 'phone', 'email',
            ]);
        });
    }
};
