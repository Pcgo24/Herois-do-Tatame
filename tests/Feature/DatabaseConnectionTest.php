<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * @group neon
 *
 * Testa a conectividade e estrutura do banco de dados Neon (PostgreSQL).
 * Não modifica nenhum dado.
 *
 * Para rodar: ./vendor/bin/sail artisan test --group=neon
 */
class DatabaseConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!getenv('RUN_NEON_TESTS')) {
            $this->markTestSkipped('Teste Neon inativo. Para rodar: RUN_NEON_TESTS=1 ./vendor/bin/sail artisan test --group=neon');
        }

        config(['database.default' => 'pgsql']);
    }

    public function test_pgsql_connection_is_alive(): void
    {
        $pdo = DB::connection('pgsql')->getPdo();

        $this->assertNotNull($pdo, 'Não foi possível obter o PDO do PostgreSQL.');
    }

    public function test_pgsql_responds_to_simple_query(): void
    {
        $result = DB::connection('pgsql')->selectOne('SELECT 1 AS ok');

        $this->assertEquals(1, $result->ok);
    }

    public function test_responsibles_table_exists(): void
    {
        $exists = Schema::connection('pgsql')->hasTable('responsibles');

        $this->assertTrue($exists, 'Tabela "responsibles" não existe. Execute: ./vendor/bin/sail artisan migrate');
    }

    public function test_students_table_exists(): void
    {
        $exists = Schema::connection('pgsql')->hasTable('students');

        $this->assertTrue($exists, 'Tabela "students" não existe. Execute: ./vendor/bin/sail artisan migrate');
    }

    public function test_responsibles_has_expected_columns(): void
    {
        $columns = Schema::connection('pgsql')->getColumnListing('responsibles');
        $expected = ['id', 'name', 'phone_number', 'cpf', 'email', 'birth_date', 'address', 'created_at', 'updated_at', 'deleted_at'];

        foreach ($expected as $column) {
            $this->assertContains($column, $columns, "Coluna \"{$column}\" não encontrada em \"responsibles\".");
        }
    }

    public function test_students_has_expected_columns(): void
    {
        $columns = Schema::connection('pgsql')->getColumnListing('students');
        $expected = ['id', 'responsible_id', 'name', 'cpf', 'rg', 'birth_date', 'modalidade', 'created_at', 'updated_at', 'deleted_at'];

        foreach ($expected as $column) {
            $this->assertContains($column, $columns, "Coluna \"{$column}\" não encontrada em \"students\".");
        }
    }

    public function test_students_foreign_key_references_responsibles(): void
    {
        $foreignKeys = DB::connection('pgsql')
            ->select("
                SELECT kcu.column_name, ccu.table_name AS foreign_table
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                WHERE tc.constraint_type = 'FOREIGN KEY'
                  AND tc.table_name = 'students'
            ");

        $references = collect($foreignKeys)->pluck('foreign_table')->all();

        $this->assertContains('responsibles', $references, 'FK de students → responsibles não encontrada.');
    }
}
