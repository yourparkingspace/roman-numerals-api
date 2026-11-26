<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'external_id' => 'OL'.fake()->unique()->numerify('######').'W',
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'first_publish_year' => fake()->numberBetween(1900, 2020),
            'cover_url' => null,
        ];
    }
}
