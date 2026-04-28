<?php

namespace Database\Factories;

use App\Models\Responsible;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResponsibleFactory extends Factory
{
    protected $model = Responsible::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->name(),
            'phone_number' => $this->faker->numerify('###########'),
            'cpf'          => $this->faker->unique()->numerify('###########'),
            'email'        => $this->faker->unique()->safeEmail(),
            'birth_date'   => $this->faker->dateTimeBetween('-60 years', '-19 years')->format('Y-m-d'),
            'address'      => substr($this->faker->streetAddress() . ', ' . $this->faker->city(), 0, 150),
        ];
    }
}
