<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Users;
use App\Livewire\Auth\Login;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\ProfessorSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['name' => 'Paulo Admin', 'username' => 'paulo_admin']);
    }

    private function professor(): User
    {
        return User::factory()->create(['name' => 'Alisson Antunes', 'username' => 'alisson_antunes']);
    }

    // --- acesso ---------------------------------------------------------

    public function test_admin_cannot_open_the_dashboard(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_cannot_download_fichas(): void
    {
        $this->actingAs($this->admin());
        $student = Student::factory()->create();

        $this->get(route('admin.students.ficha', $student))->assertForbidden();
        $this->get(route('admin.students.ficha-assinada', $student))->assertForbidden();
    }

    public function test_admin_can_open_users_and_change_password(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/usuarios')->assertOk();
        $this->get('/admin/senha')->assertOk();
    }

    public function test_admin_login_lands_on_users_page(): void
    {
        $this->admin()->update(['password' => Hash::make('senha-admin-1')]);

        Livewire::test(Login::class)
            ->set('username', 'paulo_admin')
            ->set('password', 'senha-admin-1')
            ->call('login')
            ->assertRedirect(route('admin.users'));
    }

    public function test_authenticated_admin_is_redirected_from_login_to_users(): void
    {
        $this->actingAs($this->admin());

        $this->get('/login')->assertRedirect(route('admin.users'));
    }

    public function test_admin_layout_hides_the_students_link_for_admins(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/usuarios')->assertDontSee(route('admin.dashboard'));
    }

    public function test_professor_still_sees_the_students_link(): void
    {
        $this->actingAs($this->professor());

        $this->get('/admin/usuarios')->assertSee(route('admin.dashboard'));
    }

    // --- invisibilidade -------------------------------------------------

    public function test_users_list_never_shows_admins(): void
    {
        $this->admin();
        $this->actingAs($this->professor());

        Livewire::test(Users::class)
            ->assertSee('Alisson Antunes')
            ->assertDontSee('Paulo Admin')
            ->set('showRemoved', true)
            ->assertDontSee('Paulo Admin');
    }

    public function test_admin_does_not_see_itself_in_the_list(): void
    {
        $this->actingAs($this->admin());
        $this->professor();

        Livewire::test(Users::class)
            ->assertSee('Alisson Antunes')
            ->assertDontSee('Paulo Admin');
    }

    public function test_professor_cannot_edit_remove_or_restore_an_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($this->professor());

        foreach (['edit', 'remove', 'restore'] as $acao) {
            $this->assertThrows(
                fn () => Livewire::test(Users::class)->call($acao, $admin->id),
                ModelNotFoundException::class,
            );
        }

        $this->assertNull($admin->fresh()->deleted_at);
    }

    public function test_users_created_from_the_panel_are_professors(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Beatriz Lima')
            ->set('username', 'bia_lima')
            ->set('password', 'senha-da-bia')
            ->set('password_confirmation', 'senha-da-bia')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('professor', User::where('username', 'bia_lima')->first()->role);
    }

    public function test_admin_can_manage_professors(): void
    {
        $this->actingAs($this->admin());
        $alisson = $this->professor();
        User::factory()->create(['username' => 'outro_prof']);

        Livewire::test(Users::class)
            ->call('edit', $alisson->id)
            ->set('password', 'senha-redefinida')
            ->set('password_confirmation', 'senha-redefinida')
            ->call('save')
            ->assertHasNoErrors()
            ->call('remove', $alisson->id)
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('senha-redefinida', $alisson->fresh()->password));
        $this->assertSoftDeleted($alisson);
    }

    public function test_last_active_professor_guard_ignores_admins(): void
    {
        $this->actingAs($this->admin());
        $alisson = $this->professor();

        Livewire::test(Users::class)
            ->call('remove', $alisson->id)
            ->assertHasErrors('remove');

        $this->assertNull($alisson->fresh()->deleted_at);
    }

    // --- seeders ----------------------------------------------------------

    public function test_admin_seeder_creates_the_admin_from_config(): void
    {
        config()->set('admin.username', 'paulo_admin');
        config()->set('admin.password', 'senha-admin-1');
        config()->set('admin.name', 'Paulo');

        $this->seed(AdminSeeder::class);

        $admin = User::where('username', 'paulo_admin')->first();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('senha-admin-1', $admin->password));
    }

    public function test_admin_seeder_does_nothing_when_an_admin_exists(): void
    {
        $this->admin();
        config()->set('admin.username', 'outro_admin');

        $this->seed(AdminSeeder::class);

        $this->assertSame(1, User::withTrashed()->where('role', 'admin')->count());
    }

    public function test_seeders_do_not_block_each_other(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(ProfessorSeeder::class);

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(1, User::where('role', 'professor')->count());
    }

    public function test_professor_seeder_ignores_existing_admins_but_not_professors(): void
    {
        $this->admin();
        $this->seed(ProfessorSeeder::class);
        $this->assertSame(1, User::where('role', 'professor')->count());

        config()->set('professor.username', 'segundo');
        $this->seed(ProfessorSeeder::class);
        $this->assertSame(1, User::where('role', 'professor')->count());
    }
}
