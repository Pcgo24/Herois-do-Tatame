<?php

namespace Database\Factories;

use App\Models\Responsible;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'responsible_id' => Responsible::factory(),
            'name' => $this->faker->name(),
            'cpf' => $this->faker->unique()->numerify('###########'),
            'rg' => $this->faker->numerify('#########'),
            'birth_date' => $this->faker->dateTimeBetween('-16 years', '-9 years')->format('Y-m-d'),
            'school' => 'Colégio Estadual de Prudentópolis',
            'grade' => $this->faker->numberBetween(1, 9).'º ano',
            'father_name' => $this->faker->name('male'),
            'mother_name' => $this->faker->name('female'),
            'no_father' => false,
            'no_mother' => false,
            'phone' => $this->faker->optional(0.4)->numerify('###########'),
            'email' => $this->faker->optional(0.4)->safeEmail(),
            'modalidade' => $this->faker->randomElement(['Jiu Jitsu', 'Muay Thai', 'Taekwondo', 'Boxe']),
            'termo_status' => 'pendente',
        ];
    }
}
