<?php

namespace Tests\Feature;

use App\Models\Responsible;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFichaTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $studentAttributes = [], array $responsibleAttributes = []): Student
    {
        $responsible = Responsible::factory()->create(array_merge([
            'name' => 'Maria da Silva',
            'rg' => '111111111',
            'address' => 'Rua das Flores, 123',
            'neighborhood' => 'Centro',
            'phone_number' => '42999998888',
            'home_phone' => '4232241234',
            'email' => 'maria@example.com',
        ], $responsibleAttributes));

        return Student::factory()->create(array_merge([
            'responsible_id' => $responsible->id,
            'name' => 'João da Silva',
            'rg' => '222222222',
            'birth_date' => '2015-06-10',
            'school' => 'Colégio Estadual de Prudentópolis',
            'grade' => '5º ano',
            'father_name' => 'José da Silva',
            'mother_name' => 'Maria da Silva',
            'modalidade' => 'Boxe',
        ], $studentAttributes));
    }

    private function fichaView(Student $student)
    {
        return $this->view('pdf.ficha-atleta', [
            'student' => $student,
            'responsible' => $student->responsible,
            'issuedAt' => Carbon::parse('2026-08-24'),
        ]);
    }

    public function test_guest_cannot_download_the_ficha(): void
    {
        $student = $this->student();

        $this->get(route('admin.students.ficha', $student))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_downloads_a_pdf(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->student();

        $response = $this->get(route('admin.students.ficha', $student));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_ficha_shows_the_student_data(): void
    {
        $this->fichaView($this->student())
            ->assertSee('João da Silva')
            ->assertSee('22.222.222-2')
            ->assertSee('10/06/2015')
            ->assertSee('Colégio Estadual de Prudentópolis')
            ->assertSee('5º ano')
            ->assertSee('José da Silva')
            ->assertSee('Centro')
            ->assertSee('(42) 3224-1234');
    }

    public function test_ficha_shows_the_authorization_with_both_rgs(): void
    {
        $this->fichaView($this->student())
            ->assertSee('AUTORIZAÇÃO DE PARTICIPAÇÃO')
            ->assertSee('Maria da Silva')
            ->assertSee('11.111.111-1')
            ->assertSee('22.222.222-2');
    }

    public function test_ficha_title_follows_the_student_modalidade(): void
    {
        $this->fichaView($this->student(['modalidade' => 'Muay Thai']))
            ->assertSee('FICHA DE CADASTRO DE ATLETA')
            ->assertSee('MUAY THAI');
    }

    public function test_ficha_prints_the_issue_date_in_portuguese(): void
    {
        $this->fichaView($this->student())
            ->assertSee('Prudentópolis, 24 de agosto de 2026');
    }

    public function test_declared_missing_father_prints_nao_declarado(): void
    {
        $this->fichaView($this->student([
            'father_name' => null,
            'no_father' => true,
        ]))->assertSee('Não declarado');
    }

    public function test_ficha_keeps_the_signature_notice(): void
    {
        $this->fichaView($this->student())
            ->assertSee('Assinatura do pai ou responsável')
            ->assertSee('Assinatura do atleta');
    }
}
