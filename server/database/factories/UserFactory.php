<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'firstname' => $this->faker->firstName,
            'insertion' => $this->faker->randomElement([null, null, null, 'van', 'de', 'van der']),
            'lastname' => $this->faker->lastName,
            'gender' => $this->faker->randomElement([User::GENDER_MALE, User::GENDER_FEMALE, User::GENDER_OTHER]),
            'birthday' => $this->faker->date(),
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->streetAddress,
            'postcode' => $this->faker->postcode,
            'city' => $this->faker->city,
            'password' => Hash::make($this->faker->password),
            'language' => $this->faker->randomElement([User::LANGUAGE_ENGLISH, User::LANGUAGE_DUTCH]),
            'theme' => $this->faker->randomElement([User::THEME_LIGHT, User::THEME_SYSTEM]),
            'receive_news' => $this->faker->boolean,
        ];
    }

    public function password($password)
    {
        return $this->state(fn (array $attributes) => [
            'password' => Hash::make($password),
        ]);
    }

    public function manager()
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_MANAGER,
        ]);
    }

    public function admin()
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
