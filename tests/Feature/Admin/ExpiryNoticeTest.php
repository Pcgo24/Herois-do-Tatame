<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ExpiryNoticeTest extends TestCase
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

    public function test_the_notice_opens_when_an_enrollment_needs_attention(): void
    {
        Student::factory()->create(['name' => 'Vencido há muito', 'matricula_em' => '2025-07-01']);
        Student::factory()->create(['name' => 'Vencido há pouco', 'matricula_em' => '2025-09-14']);
        Student::factory()->create(['name' => 'Vence depois', 'matricula_em' => '2025-10-10']);
        Student::factory()->create(['name' => 'Vence antes', 'matricula_em' => '2025-10-05']);
        Student::factory()->create(['name' => 'Aluno Novo', 'matricula_em' => '2026-06-17']);

        $component = Livewire::test(Dashboard::class)
            ->assertSet('avisoAberto', true)
            ->assertSeeHtml('data-cy="aviso-vencimentos"');

        // Vencidas: as mais recentes primeiro. A vencer: as mais próximas primeiro.
        $this->assertSame(['Vencido há pouco', 'Vencido há muito'], $component->get('avisoVencidas')->pluck('name')->all());
        $this->assertSame(['Vence antes', 'Vence depois'], $component->get('avisoVencendo')->pluck('name')->all());
    }

    public function test_the_notice_stays_closed_when_everything_is_fine(): void
    {
        Student::factory()->create(['matricula_em' => '2026-06-17']);

        Livewire::test(Dashboard::class)
            ->assertSet('avisoAberto', false)
            ->assertDontSeeHtml('data-cy="aviso-vencimentos"');
    }

    public function test_the_notice_ignores_cancelled_enrollments(): void
    {
        Student::factory()->create(['matricula_em' => '2025-09-14'])->delete();

        Livewire::test(Dashboard::class)->assertSet('avisoAberto', false);
    }

    public function test_dismissing_the_notice_keeps_it_closed_for_the_rest_of_the_session(): void
    {
        Student::factory()->create(['matricula_em' => '2025-09-14']);

        Livewire::test(Dashboard::class)
            ->assertSet('avisoAberto', true)
            ->call('dismissAviso')
            ->assertSet('avisoAberto', false);

        Livewire::test(Dashboard::class)->assertSet('avisoAberto', false);
    }

    public function test_the_notice_comes_back_on_a_new_session(): void
    {
        Student::factory()->create(['matricula_em' => '2025-09-14']);

        Livewire::test(Dashboard::class)->call('dismissAviso');
        session()->flush();

        Livewire::test(Dashboard::class)->assertSet('avisoAberto', true);
    }
}
