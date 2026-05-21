<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTermoStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_defaults_to_pendente_termo_status(): void
    {
        $student = Student::factory()->create();

        $this->assertEquals('pendente', $student->fresh()->termo_status);
    }

    public function test_termo_arquivo_defaults_to_null(): void
    {
        $student = Student::factory()->create();

        $this->assertNull($student->fresh()->termo_arquivo);
    }

    public function test_termo_status_can_be_updated(): void
    {
        $student = Student::factory()->create(['termo_status' => 'pendente']);

        $student->update(['termo_status' => 'entregue']);

        $this->assertEquals('entregue', $student->fresh()->termo_status);
    }
}
