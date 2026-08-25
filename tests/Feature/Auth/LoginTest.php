<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function professor(): User
    {
        return User::factory()->create([
            'cpf' => '12345678909',
            'password' => Hash::make('senha-correta'),
        ]);
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Entrar');
    }

    public function test_professor_logs_in_with_valid_credentials(): void
    {
        $professor = $this->professor();

        Livewire::test(Login::class)
            ->set('cpf', '12345678909')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($professor);
    }

    public function test_wrong_password_does_not_authenticate(): void
    {
        $this->professor();

        Livewire::test(Login::class)
            ->set('cpf', '12345678909')
            ->set('password', 'senha-errada')
            ->call('login')
            ->assertHasErrors('cpf');

        $this->assertGuest();
    }

    public function test_unknown_cpf_shows_the_same_generic_message(): void
    {
        $this->professor();

        Livewire::test(Login::class)
            ->set('cpf', '99999999999')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasErrors('cpf');

        $this->assertGuest();
    }

    public function test_cpf_must_have_eleven_digits(): void
    {
        Livewire::test(Login::class)
            ->set('cpf', '123')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasErrors(['cpf' => 'regex']);

        $this->assertGuest();
    }

    public function test_sixth_attempt_is_rate_limited(): void
    {
        $this->professor();

        $component = Livewire::test(Login::class)
            ->set('cpf', '12345678909')
            ->set('password', 'senha-errada');

        for ($i = 0; $i < 5; $i++) {
            $component->call('login');
        }

        $component->set('password', 'senha-correta')->call('login');

        $this->assertGuest();
        $this->assertStringContainsString(
            'Muitas tentativas',
            $component->errors()->first('cpf'),
        );
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs($this->professor());

        $this->get('/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs($this->professor());

        $this->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }
}
