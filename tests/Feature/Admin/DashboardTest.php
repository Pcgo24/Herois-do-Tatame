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
            'name'         => 'Maria Souza',
            'phone_number' => '11987654321',
        ]);
        Student::factory()->create(['responsible_id' => $responsible->id]);

        Livewire::test(Dashboard::class)
            ->assertSee('Maria Souza')
            ->assertSee('11987654321');
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

    public function test_admin_dashboard_is_publicly_accessible(): void
    {
        $this->get('/admin/dashboard')->assertStatus(200);
    }
}
