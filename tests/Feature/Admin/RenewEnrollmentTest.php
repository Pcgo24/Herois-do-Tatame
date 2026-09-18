<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class RenewEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-17');
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_renewing_sets_the_enrollment_date_to_today(): void
    {
        $student = Student::factory()->create(['matricula_em' => '2025-06-01']);

        Livewire::test(Dashboard::class)
            ->call('renovarMatricula', $student->id)
            ->assertHasNoErrors();

        $this->assertSame('2026-09-17', $student->fresh()->matricula_em->toDateString());
    }

    public function test_a_cancelled_enrollment_cannot_be_renewed(): void
    {
        $student = Student::factory()->create(['matricula_em' => '2025-06-01']);
        $student->delete();

        $this->assertThrows(
            fn () => Livewire::test(Dashboard::class)->call('renovarMatricula', $student->id),
            ModelNotFoundException::class,
        );

        $this->assertSame('2025-06-01', Student::withTrashed()->find($student->id)->matricula_em->toDateString());
    }

    public function test_the_list_shows_how_long_each_enrollment_has_left(): void
    {
        Student::factory()->create(['name' => 'Aluno Novo', 'matricula_em' => '2026-06-17']);
        Student::factory()->create(['name' => 'Aluno Vencendo', 'matricula_em' => '2025-10-05']);
        Student::factory()->create(['name' => 'Aluno Vencido', 'matricula_em' => '2025-09-14']);

        Livewire::test(Dashboard::class)
            ->assertSeeInOrder(['Aluno Vencido', 'vencida há 3 dias', 'Aluno Vencendo', 'faltam 18 dias', 'Aluno Novo', 'faltam 9 meses']);
    }

    public function test_the_list_can_be_filtered_to_enrollments_needing_attention(): void
    {
        Student::factory()->create(['name' => 'Aluno Novo', 'matricula_em' => '2026-06-17']);
        Student::factory()->create(['name' => 'Aluno Vencendo', 'matricula_em' => '2025-10-05']);
        Student::factory()->create(['name' => 'Aluno Vencido', 'matricula_em' => '2025-09-14']);

        Livewire::test(Dashboard::class)
            ->set('onlyAttention', true)
            ->assertSee('Aluno Vencido')
            ->assertSee('Aluno Vencendo')
            ->assertDontSee('Aluno Novo');
    }

    public function test_the_modal_carries_the_enrollment_dates_and_situation(): void
    {
        Student::factory()->create(['matricula_em' => '2025-09-14']);

        Livewire::test(Dashboard::class)
            ->assertSeeHtml('\\u0022matricula_em\\u0022:\\u002214\\\\\\/09\\\\\\/2025\\u0022')
            ->assertSeeHtml('\\u0022vence_em\\u0022:\\u002214\\\\\\/09\\\\\\/2026\\u0022')
            ->assertSeeHtml('\\u0022situacao\\u0022:\\u0022vencida\\u0022');
    }
}
