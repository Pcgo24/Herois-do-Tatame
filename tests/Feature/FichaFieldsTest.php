<?php

namespace Tests\Feature;

use App\Models\Responsible;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FichaFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsible_persists_ficha_fields(): void
    {
        $responsible = Responsible::factory()->create([
            'rg' => '123456789',
            'neighborhood' => 'Centro',
            'home_phone' => '4232241234',
        ]);

        $this->assertSame('123456789', $responsible->fresh()->rg);
        $this->assertSame('Centro', $responsible->fresh()->neighborhood);
        $this->assertSame('4232241234', $responsible->fresh()->home_phone);
    }

    public function test_student_persists_ficha_fields(): void
    {
        $student = Student::factory()->create([
            'school' => 'Colégio Estadual Prudentópolis',
            'grade' => '7º ano',
            'father_name' => 'José da Silva',
            'mother_name' => 'Maria da Silva',
            'phone' => '42999998888',
            'email' => 'joao@example.com',
        ]);

        $fresh = $student->fresh();

        $this->assertSame('Colégio Estadual Prudentópolis', $fresh->school);
        $this->assertSame('7º ano', $fresh->grade);
        $this->assertSame('José da Silva', $fresh->father_name);
        $this->assertSame('Maria da Silva', $fresh->mother_name);
        $this->assertSame('42999998888', $fresh->phone);
        $this->assertSame('joao@example.com', $fresh->email);
    }

    public function test_student_filiation_flags_are_booleans(): void
    {
        $student = Student::factory()->create([
            'father_name' => null,
            'no_father' => true,
        ]);

        $fresh = $student->fresh();

        $this->assertTrue($fresh->no_father);
        $this->assertFalse($fresh->no_mother);
        $this->assertNull($fresh->father_name);
    }

    public function test_home_phone_is_optional(): void
    {
        $responsible = Responsible::factory()->create(['home_phone' => null]);

        $this->assertNull($responsible->fresh()->home_phone);
    }
}
