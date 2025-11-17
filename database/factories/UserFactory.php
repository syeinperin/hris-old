<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        // ensure at least one role exists
        $role = Role::inRandomOrder()->first()
            ?? Role::create(['name' => 'admin']);

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'status' => 'active',
            'last_login' => null,
            'remember_token' => Str::random(10),
        ];
    }
}
