<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Models\Responsible;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Dashboard::class)->assertStatus(200);
    }

    public function test_dashboard_shows_student_name(): void
    {
        $this->actingAs(User::factory()->create());
        Student::factory()->create(['name' => 'João da Silva']);

        Livewire::test(Dashboard::class)->assertSee('João da Silva');
    }

    public function test_dashboard_shows_responsible_name_and_phone(): void
    {
        $this->actingAs(User::factory()->create());
        $responsible = Responsible::factory()->create([
            'name' => 'Maria Souza',
            'phone_number' => '11987654321',
        ]);
        Student::factory()->create(['responsible_id' => $responsible->id]);

        Livewire::test(Dashboard::class)
            ->assertSee('Maria Souza')
            ->assertSee('(11) 98765-4321');
    }

    public function test_dashboard_shows_all_students(): void
    {
        $this->actingAs(User::factory()->create());
        Student::factory()->create(['name' => 'Aluno Um']);
        Student::factory()->create(['name' => 'Aluno Dois']);

        Livewire::test(Dashboard::class)
            ->assertSee('Aluno Um')
            ->assertSee('Aluno Dois');
    }

    public function test_dashboard_shows_termo_status(): void
    {
        $this->actingAs(User::factory()->create());
        Student::factory()->create(['termo_status' => 'pendente']);

        Livewire::test(Dashboard::class)->assertSee('Pendente');
    }

    public function test_update_termo_status_to_entregue(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create(['termo_status' => 'pendente']);

        Livewire::test(Dashboard::class)
            ->call('updateTermoStatus', $student->id, 'entregue')
            ->assertHasNoErrors();

        $this->assertEquals('entregue', $student->fresh()->termo_status);
    }

    public function test_update_termo_status_to_assinado(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create(['termo_status' => 'entregue']);

        Livewire::test(Dashboard::class)
            ->call('updateTermoStatus', $student->id, 'assinado');

        $this->assertEquals('assinado', $student->fresh()->termo_status);
    }

    public function test_invalid_termo_status_is_ignored(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create(['termo_status' => 'pendente']);

        Livewire::test(Dashboard::class)
            ->call('updateTermoStatus', $student->id, 'invalido');

        $this->assertEquals('pendente', $student->fresh()->termo_status);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_reaches_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_dashboard_embeds_student_details_for_modal(): void
    {
        $this->actingAs(User::factory()->create());
        $responsible = Responsible::factory()->create([
            'name' => 'Ana Responsável',
            'email' => 'ana@example.com',
        ]);
        Student::factory()->create([
            'responsible_id' => $responsible->id,
            'name' => 'Pedro Estudante',
            'modalidade' => 'Judô',
        ]);

        // O modal é client-side: os detalhes completos vão embutidos no HTML
        // (via @js no @click da linha) para abrir sem ida ao servidor.
        Livewire::test(Dashboard::class)
            ->assertSee('Pedro Estudante')
            ->assertSee('Ana Responsável')
            ->assertSee('ana@example.com')
            ->assertSee('Judô');
    }

    public function test_dashboard_links_to_the_student_ficha(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->assertSee(route('admin.students.ficha', $student), escape: false);
    }

    public function test_guest_cannot_download_a_signed_ficha(): void
    {
        $student = Student::factory()->create();

        $this->get(route('admin.students.ficha-assinada', $student))
            ->assertRedirect(route('login'));
    }
}
