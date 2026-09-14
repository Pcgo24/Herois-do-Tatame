<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CancelEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_cancelling_soft_deletes_the_student(): void
    {
        $student = Student::factory()->create(['name' => 'Aluno Cancelado']);

        Livewire::test(Dashboard::class)
            ->call('cancelEnrollment', $student->id)
            ->assertHasNoErrors()
            ->assertDontSee('Aluno Cancelado');

        $this->assertSoftDeleted($student);
    }

    public function test_cancelling_keeps_the_responsible_and_the_signed_ficha(): void
    {
        $student = Student::factory()->create(['termo_arquivo' => 'fichas-assinadas/x.pdf']);

        Livewire::test(Dashboard::class)->call('cancelEnrollment', $student->id);

        $cancelled = Student::withTrashed()->find($student->id);
        $this->assertSame('fichas-assinadas/x.pdf', $cancelled->termo_arquivo);
        $this->assertNull($cancelled->responsible->deleted_at);
    }

    public function test_cancelled_students_are_listed_only_on_demand(): void
    {
        Student::factory()->create(['name' => 'Aluno Ativo']);
        Student::factory()->create(['name' => 'Aluno Cancelado'])->delete();

        Livewire::test(Dashboard::class)
            ->assertSee('Aluno Ativo')
            ->assertDontSee('Aluno Cancelado')
            ->set('showCancelled', true)
            ->assertSee('Aluno Ativo')
            ->assertSee('Aluno Cancelado')
            ->assertSee('Cancelada');
    }

    public function test_cancelled_enrollment_can_be_restored(): void
    {
        $student = Student::factory()->create(['name' => 'Aluno Cancelado']);
        $student->delete();

        Livewire::test(Dashboard::class)
            ->set('showCancelled', true)
            ->call('restoreEnrollment', $student->id)
            ->assertHasNoErrors()
            ->assertSee('Aluno Cancelado');

        $this->assertNull($student->fresh()->deleted_at);
    }

    public function test_cancelled_student_ficha_is_not_available(): void
    {
        $student = Student::factory()->create();
        $ficha = route('admin.students.ficha', $student);
        $assinada = route('admin.students.ficha-assinada', $student);

        $student->delete();

        $this->get($ficha)->assertNotFound();
        $this->get($assinada)->assertNotFound();
    }

    public function test_modal_data_flags_cancelled_students(): void
    {
        Student::factory()->create(['name' => 'Aluno Cancelado'])->delete();

        Livewire::test(Dashboard::class)
            ->set('showCancelled', true)
            ->assertSeeHtml('\u0022cancelled\u0022:true');
    }
}
