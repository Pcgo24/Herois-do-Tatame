<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TermoFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        Student::factory()->create(['name' => 'Aluno Pendente', 'termo_status' => 'pendente']);
        Student::factory()->create(['name' => 'Aluno Entregue', 'termo_status' => 'entregue']);
        Student::factory()->create(['name' => 'Aluno Assinado', 'termo_status' => 'assinado']);
    }

    public function test_shows_everyone_by_default(): void
    {
        Livewire::test(Dashboard::class)
            ->assertSet('termoFilter', '')
            ->assertSee('Aluno Pendente')
            ->assertSee('Aluno Entregue')
            ->assertSee('Aluno Assinado');
    }

    public function test_filters_by_termo_status(): void
    {
        Livewire::test(Dashboard::class)
            ->set('termoFilter', 'entregue')
            ->assertSee('Aluno Entregue')
            ->assertDontSee('Aluno Pendente')
            ->assertDontSee('Aluno Assinado')
            ->set('termoFilter', '')
            ->assertSee('Aluno Pendente');
    }

    public function test_combines_with_the_attention_filter(): void
    {
        Student::factory()->create(['name' => 'Assinado Vencido', 'termo_status' => 'assinado', 'matricula_em' => today()->subYears(2)]);

        Livewire::test(Dashboard::class)
            ->set('termoFilter', 'assinado')
            ->set('onlyAttention', true)
            ->assertSee('Assinado Vencido')
            ->assertDontSee('Aluno Assinado');
    }
}
