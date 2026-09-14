<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ChangePassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private function professor(): User
    {
        return User::factory()->create(['password' => Hash::make('senha-atual')]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/senha')->assertRedirect('/login');
    }

    public function test_page_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->professor());

        $this->get('/admin/senha')->assertOk()->assertSee('Alterar senha');
    }

    public function test_password_is_changed_with_the_current_one(): void
    {
        $professor = $this->professor();
        $this->actingAs($professor);

        Livewire::test(ChangePassword::class)
            ->set('current_password', 'senha-atual')
            ->set('password', 'senha-nova-12')
            ->set('password_confirmation', 'senha-nova-12')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved', true)
            ->assertSet('password', '');

        $this->assertTrue(Hash::check('senha-nova-12', $professor->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $professor = $this->professor();
        $this->actingAs($professor);

        Livewire::test(ChangePassword::class)
            ->set('current_password', 'errada')
            ->set('password', 'senha-nova-12')
            ->set('password_confirmation', 'senha-nova-12')
            ->call('save')
            ->assertHasErrors(['current_password' => 'current_password']);

        $this->assertTrue(Hash::check('senha-atual', $professor->fresh()->password));
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $this->actingAs($this->professor());

        Livewire::test(ChangePassword::class)
            ->set('current_password', 'senha-atual')
            ->set('password', 'senha-nova-12')
            ->set('password_confirmation', 'outra')
            ->call('save')
            ->assertHasErrors(['password' => 'confirmed']);
    }

    public function test_new_password_needs_at_least_eight_characters(): void
    {
        $this->actingAs($this->professor());

        Livewire::test(ChangePassword::class)
            ->set('current_password', 'senha-atual')
            ->set('password', 'curta')
            ->set('password_confirmation', 'curta')
            ->call('save')
            ->assertHasErrors(['password' => 'min']);
    }

    public function test_admin_layout_links_to_change_password(): void
    {
        $this->actingAs($this->professor());

        $this->get('/admin/dashboard')->assertSee(route('admin.password'));
    }
}
