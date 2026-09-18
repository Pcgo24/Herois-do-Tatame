<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentEnrollmentExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-17');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function enrolledOn(string $date): Student
    {
        return Student::factory()->create(['matricula_em' => $date]);
    }

    public function test_a_new_student_is_enrolled_today_by_default(): void
    {
        $student = Student::factory()->create(['matricula_em' => null]);

        $this->assertSame('2026-09-17', $student->fresh()->matricula_em->toDateString());
    }

    public function test_enrollment_expires_one_year_after_it_started(): void
    {
        $this->assertSame('2027-03-01', $this->enrolledOn('2026-03-01')->vence_em->toDateString());
    }

    public function test_situacao_is_ok_more_than_thirty_days_before_expiry(): void
    {
        $this->assertSame('ok', $this->enrolledOn('2025-10-18')->situacaoMatricula()); // vence em 31 dias
    }

    public function test_situacao_is_vencendo_within_thirty_days(): void
    {
        $this->assertSame('vencendo', $this->enrolledOn('2025-10-17')->situacaoMatricula()); // 30 dias
        $this->assertSame('vencendo', $this->enrolledOn('2025-09-17')->situacaoMatricula()); // vence hoje
    }

    public function test_situacao_is_vencida_after_expiry(): void
    {
        $this->assertSame('vencida', $this->enrolledOn('2025-09-16')->situacaoMatricula());
    }

    public function test_texto_describes_the_remaining_time_in_portuguese(): void
    {
        $this->assertSame('faltam 9 meses', $this->enrolledOn('2026-06-17')->textoVencimento());
        $this->assertSame('falta 1 mês', $this->enrolledOn('2025-10-20')->textoVencimento());
        $this->assertSame('faltam 30 dias', $this->enrolledOn('2025-10-17')->textoVencimento());
        $this->assertSame('falta 1 dia', $this->enrolledOn('2025-09-18')->textoVencimento());
        $this->assertSame('vence hoje', $this->enrolledOn('2025-09-17')->textoVencimento());
        $this->assertSame('vencida há 3 dias', $this->enrolledOn('2025-09-14')->textoVencimento());
        $this->assertSame('vencida há 1 dia', $this->enrolledOn('2025-09-16')->textoVencimento());
        $this->assertSame('vencida há 2 meses', $this->enrolledOn('2025-07-10')->textoVencimento());
    }

    public function test_renewing_restarts_the_year_from_today(): void
    {
        $student = $this->enrolledOn('2025-01-10');

        $student->renovarMatricula();

        $this->assertSame('2026-09-17', $student->fresh()->matricula_em->toDateString());
        $this->assertSame('2027-09-17', $student->fresh()->vence_em->toDateString());
    }
}
