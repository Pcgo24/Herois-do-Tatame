<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function professor(): User
    {
        return User::factory()->create([
            'username' => 'alisson_antunes',
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
            ->set('username', 'alisson_antunes')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($professor);
    }

    public function test_username_is_case_insensitive_and_trimmed(): void
    {
        $professor = $this->professor();

        Livewire::test(Login::class)
            ->set('username', '  Alisson_Antunes ')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($professor);
    }

    public function test_wrong_password_does_not_authenticate(): void
    {
        $this->professor();

        Livewire::test(Login::class)
            ->set('username', 'alisson_antunes')
            ->set('password', 'senha-errada')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }

    public function test_unknown_username_shows_the_same_generic_message(): void
    {
        $this->professor();

        $component = Livewire::test(Login::class)
            ->set('username', 'ninguem')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertSame('Usuário ou senha inválidos.', $component->errors()->first('username'));
        $this->assertGuest();
    }

    public function test_username_is_required(): void
    {
        Livewire::test(Login::class)
            ->set('username', '')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasErrors(['username' => 'required']);

        $this->assertGuest();
    }

    public function test_removed_user_cannot_log_in(): void
    {
        $this->professor()->delete();

        Livewire::test(Login::class)
            ->set('username', 'alisson_antunes')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasErrors('username');

        $this->assertGuest();
    }

    // actingAs() injeta o usuário direto no guard; só a sessão faz o guard
    // buscá-lo no banco a cada requisição, que é onde o soft delete o barra.
    public function test_removed_user_loses_the_active_session(): void
    {
        $professor = $this->professor();
        $sessao = [Auth::guard('web')->getName() => $professor->id];

        $this->withSession($sessao)->get('/admin/dashboard')->assertOk();

        $professor->delete();
        Auth::forgetGuards(); // cada requisição real começa com o guard vazio

        $this->withSession($sessao)->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_sixth_attempt_is_rate_limited(): void
    {
        $this->professor();

        $component = Livewire::test(Login::class)
            ->set('username', 'alisson_antunes')
            ->set('password', 'senha-errada');

        for ($i = 0; $i < 5; $i++) {
            $component->call('login');
        }

        $component->set('password', 'senha-correta')->call('login');

        $this->assertGuest();
        $this->assertStringContainsString(
            'Muitas tentativas',
            $component->errors()->first('username'),
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
