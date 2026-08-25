<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SignedFichaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->actingAs(User::factory()->create());
    }

    public function test_upload_stores_the_file_and_marks_the_termo_as_signed(): void
    {
        $student = Student::factory()->create(['termo_status' => 'entregue']);

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('ficha.pdf', 200, 'application/pdf'))
            ->call('uploadSignedFicha')
            ->assertHasNoErrors();

        $student->refresh();

        $this->assertNotNull($student->termo_arquivo);
        $this->assertSame('ficha.pdf', $student->termo_arquivo_nome);
        $this->assertNotNull($student->termo_arquivo_enviado_em);
        $this->assertSame('assinado', $student->termo_status);
        Storage::disk('local')->assertExists($student->termo_arquivo);
    }

    public function test_upload_accepts_a_photo_of_the_signed_sheet(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->image('ficha.jpg'))
            ->call('uploadSignedFicha')
            ->assertHasNoErrors();

        $this->assertSame('assinado', $student->fresh()->termo_status);
    }

    public function test_upload_rejects_a_forbidden_file_type(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('malicioso.exe', 10))
            ->call('uploadSignedFicha')
            ->assertHasErrors('signedFicha');

        $this->assertNull($student->fresh()->termo_arquivo);
    }

    public function test_upload_rejects_a_file_over_five_megabytes(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('gigante.pdf', 6000, 'application/pdf'))
            ->call('uploadSignedFicha')
            ->assertHasErrors('signedFicha');

        $this->assertNull($student->fresh()->termo_arquivo);
    }

    public function test_replacing_the_file_deletes_the_previous_one(): void
    {
        $student = Student::factory()->create();

        $component = Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('primeira.pdf', 100, 'application/pdf'))
            ->call('uploadSignedFicha');

        $first = $student->fresh()->termo_arquivo;

        $component
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('segunda.pdf', 100, 'application/pdf'))
            ->call('uploadSignedFicha');

        $second = $student->fresh()->termo_arquivo;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_removing_the_file_clears_the_columns_and_reverts_the_status(): void
    {
        $student = Student::factory()->create();

        $component = Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('ficha.pdf', 100, 'application/pdf'))
            ->call('uploadSignedFicha');

        $path = $student->fresh()->termo_arquivo;

        $component->call('removeSignedFicha', $student->id);

        $student->refresh();

        $this->assertNull($student->termo_arquivo);
        $this->assertNull($student->termo_arquivo_nome);
        $this->assertSame('entregue', $student->termo_status);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_authenticated_user_downloads_the_signed_ficha(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('ficha.pdf', 100, 'application/pdf'))
            ->call('uploadSignedFicha');

        $this->get(route('admin.students.ficha-assinada', $student))->assertOk();
    }

    public function test_download_returns_404_when_there_is_no_file(): void
    {
        $student = Student::factory()->create();

        $this->get(route('admin.students.ficha-assinada', $student))->assertNotFound();
    }
}
