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
            'name'           => $this->faker->name(),
            'cpf'            => $this->faker->unique()->numerify('###########'),
            'rg'             => $this->faker->optional(0.7)->numerify('#########'),
            'birth_date'     => $this->faker->dateTimeBetween('-16 years', '-9 years')->format('Y-m-d'),
            'modalidade'     => $this->faker->randomElement(['Jiu Jitsu', 'Muay Thai', 'Taekwondo', 'Boxe']),
        ];
    }
}
