<?php

namespace Codinglabs\NotificationSubscriptions\Tests\Stubs;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestSubjectFactory extends Factory
{
    protected $model = TestSubject::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
