<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    private function professor(): User
    {
        return User::factory()->create(['name' => 'Alisson Antunes', 'username' => 'alisson_antunes']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/usuarios')->assertRedirect('/login');
    }

    public function test_page_lists_active_users(): void
    {
        $this->actingAs($this->professor());
        User::factory()->create(['name' => 'Beatriz Lima', 'username' => 'bia_lima']);
        User::factory()->create(['name' => 'Carlos Removido', 'username' => 'carlos'])->delete();

        $this->get('/admin/usuarios')
            ->assertOk()
            ->assertSee('Alisson Antunes')
            ->assertSee('bia_lima')
            ->assertDontSee('Carlos Removido');
    }

    public function test_admin_layout_links_to_users_page(): void
    {
        $this->actingAs($this->professor());

        $this->get('/admin/dashboard')->assertSee(route('admin.users'));
    }

    public function test_creates_a_user_with_hashed_password(): void
    {
        $this->actingAs($this->professor());

        Livewire::test(Users::class)
            ->call('create')
            ->assertSet('modalOpen', true)
            ->set('name', 'Beatriz Lima')
            ->set('username', 'bia_lima')
            ->set('password', 'senha-da-bia')
            ->set('password_confirmation', 'senha-da-bia')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('modalOpen', false);

        $bia = User::where('username', 'bia_lima')->first();
        $this->assertNotNull($bia);
        $this->assertSame('Beatriz Lima', $bia->name);
        $this->assertTrue(Hash::check('senha-da-bia', $bia->password));
    }

    public function test_username_is_normalized_to_lowercase(): void
    {
        $this->actingAs($this->professor());

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Beatriz Lima')
            ->set('username', '  Bia_Lima ')
            ->set('password', 'senha-da-bia')
            ->set('password_confirmation', 'senha-da-bia')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotNull(User::where('username', 'bia_lima')->first());
    }

    public function test_username_must_follow_the_allowed_format(): void
    {
        $this->actingAs($this->professor());

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Beatriz Lima')
            ->set('username', 'bia lima!')
            ->set('password', 'senha-da-bia')
            ->set('password_confirmation', 'senha-da-bia')
            ->call('save')
            ->assertHasErrors(['username' => 'regex']);
    }

    public function test_username_must_be_unique_even_against_removed_users(): void
    {
        $this->actingAs($this->professor());
        User::factory()->create(['username' => 'carlos'])->delete();

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Carlos Novo')
            ->set('username', 'carlos')
            ->set('password', 'senha-do-carlos')
            ->set('password_confirmation', 'senha-do-carlos')
            ->call('save')
            ->assertHasErrors(['username' => 'unique']);
    }

    public function test_password_is_required_when_creating(): void
    {
        $this->actingAs($this->professor());

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Beatriz Lima')
            ->set('username', 'bia_lima')
            ->call('save')
            ->assertHasErrors(['password' => 'required']);
    }

    public function test_password_must_be_confirmed_and_have_eight_characters(): void
    {
        $this->actingAs($this->professor());

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Beatriz Lima')
            ->set('username', 'bia_lima')
            ->set('password', 'curta')
            ->set('password_confirmation', 'outra')
            ->call('save')
            ->assertHasErrors(['password' => ['min', 'confirmed']]);
    }

    public function test_editing_keeps_the_password_when_left_blank(): void
    {
        $this->actingAs($this->professor());
        $bia = User::factory()->create(['username' => 'bia_lima', 'password' => Hash::make('senha-da-bia')]);

        Livewire::test(Users::class)
            ->call('edit', $bia->id)
            ->assertSet('modalOpen', true)
            ->assertSet('username', 'bia_lima')
            ->set('name', 'Beatriz Lima Souza')
            ->set('username', 'bia_souza')
            ->call('save')
            ->assertHasNoErrors();

        $bia->refresh();
        $this->assertSame('Beatriz Lima Souza', $bia->name);
        $this->assertSame('bia_souza', $bia->username);
        $this->assertTrue(Hash::check('senha-da-bia', $bia->password));
    }

    public function test_editing_can_set_a_new_password(): void
    {
        $this->actingAs($this->professor());
        $bia = User::factory()->create(['username' => 'bia_lima', 'password' => Hash::make('senha-da-bia')]);

        Livewire::test(Users::class)
            ->call('edit', $bia->id)
            ->set('password', 'senha-nova-da-bia')
            ->set('password_confirmation', 'senha-nova-da-bia')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('senha-nova-da-bia', $bia->fresh()->password));
    }

    public function test_editing_keeps_the_own_username_without_unique_error(): void
    {
        $this->actingAs($this->professor());
        $bia = User::factory()->create(['username' => 'bia_lima']);

        Livewire::test(Users::class)
            ->call('edit', $bia->id)
            ->set('name', 'Beatriz')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_removing_a_user_soft_deletes_it(): void
    {
        $this->actingAs($this->professor());
        $bia = User::factory()->create(['username' => 'bia_lima']);

        Livewire::test(Users::class)
            ->call('remove', $bia->id)
            ->assertHasNoErrors()
            ->assertDontSee('bia_lima');

        $this->assertSoftDeleted($bia);
    }

    public function test_cannot_remove_yourself(): void
    {
        $professor = $this->professor();
        $this->actingAs($professor);
        User::factory()->create(['username' => 'bia_lima']);

        Livewire::test(Users::class)
            ->call('remove', $professor->id)
            ->assertHasErrors('remove');

        $this->assertNull($professor->fresh()->deleted_at);
    }

    public function test_cannot_remove_the_last_active_user(): void
    {
        $professor = $this->professor();
        $this->actingAs($professor);

        Livewire::test(Users::class)
            ->call('remove', $professor->id)
            ->assertHasErrors('remove');

        $this->assertSame(1, User::count());
    }

    public function test_removed_users_can_be_listed_and_restored(): void
    {
        $this->actingAs($this->professor());
        $carlos = User::factory()->create(['name' => 'Carlos Removido', 'username' => 'carlos']);
        $carlos->delete();

        Livewire::test(Users::class)
            ->assertDontSee('Carlos Removido')
            ->set('showRemoved', true)
            ->assertSee('Carlos Removido')
            ->call('restore', $carlos->id)
            ->assertHasNoErrors();

        $this->assertNull($carlos->fresh()->deleted_at);
    }
}
