<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Part 2: CRUD endpoints for the local catalogue.
 */
class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    private const BOOK_STRUCTURE = [
        'id', 'external_id', 'title', 'author', 'first_publish_year', 'cover_url', 'created_at',
    ];

    public function test_index_returns_a_paginated_list(): void
    {
        Book::factory()->count(20)->create();

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure(['data' => ['*' => self::BOOK_STRUCTURE], 'links', 'meta'])
            ->assertJsonPath('meta.total', 20);

        $this->getJson('/api/books?page=2')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_index_supports_per_page_with_an_upper_limit(): void
    {
        Book::factory()->count(60)->create();

        $this->getJson('/api/books?per_page=5')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson('/api/books?per_page=500')->assertStatus(422)->assertJsonValidationErrors('per_page');
    }

    public function test_index_filters_by_author_partial_match(): void
    {
        Book::factory()->create(['title' => 'Dune', 'author' => 'Frank Herbert']);
        Book::factory()->create(['title' => 'Children of Dune', 'author' => 'Frank Herbert']);
        Book::factory()->create(['title' => 'Emma', 'author' => 'Jane Austen']);

        $this->getJson('/api/books?author=herbert')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['title' => 'Emma']);
    }

    public function test_index_filters_by_year(): void
    {
        Book::factory()->create(['title' => 'Old', 'first_publish_year' => 1965]);
        Book::factory()->create(['title' => 'New', 'first_publish_year' => 2001]);

        $this->getJson('/api/books?year=1965')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Old');
    }

    public function test_index_filters_can_be_combined(): void
    {
        Book::factory()->create(['author' => 'Frank Herbert', 'first_publish_year' => 1965]);
        Book::factory()->create(['author' => 'Frank Herbert', 'first_publish_year' => 1969]);
        Book::factory()->create(['author' => 'Jane Austen', 'first_publish_year' => 1965]);

        $this->getJson('/api/books?author=herbert&year=1965')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_show_returns_a_single_book(): void
    {
        $book = Book::factory()->create(['title' => 'Dune']);

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => self::BOOK_STRUCTURE])
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'Dune');
    }

    public function test_show_returns_404_for_unknown_book(): void
    {
        $this->getJson('/api/books/999999')->assertNotFound();
    }

    public function test_store_creates_a_book(): void
    {
        $this->postJson('/api/books', [
            'title' => 'The Hobbit',
            'author' => 'J.R.R. Tolkien',
            'first_publish_year' => 1937,
            'cover_url' => 'https://example.com/hobbit.jpg',
        ])
            ->assertCreated()
            ->assertJsonStructure(['data' => self::BOOK_STRUCTURE])
            ->assertJsonPath('data.title', 'The Hobbit')
            ->assertJsonPath('data.external_id', null);

        $this->assertDatabaseHas('books', ['title' => 'The Hobbit', 'first_publish_year' => 1937]);
    }

    public function test_store_only_requires_a_title(): void
    {
        $this->postJson('/api/books', ['title' => 'Just A Title'])->assertCreated();
    }

    public function test_store_ignores_external_id_sent_by_clients(): void
    {
        $this->postJson('/api/books', ['title' => 'Sneaky', 'external_id' => 'OL1W'])
            ->assertCreated()
            ->assertJsonPath('data.external_id', null);
    }

    public function test_store_validates_input(): void
    {
        $this->postJson('/api/books', [])->assertStatus(422)->assertJsonValidationErrors('title');

        $this->postJson('/api/books', [
            'title' => str_repeat('x', 256),
            'first_publish_year' => 'abc',
            'cover_url' => 'not-a-url',
        ])->assertStatus(422)->assertJsonValidationErrors(['title', 'first_publish_year', 'cover_url']);

        $this->postJson('/api/books', ['title' => 'Future', 'first_publish_year' => (int) date('Y') + 5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('first_publish_year');
    }

    public function test_update_modifies_a_book(): void
    {
        $book = Book::factory()->create(['title' => 'Old title', 'author' => 'Old Author']);

        $this->putJson("/api/books/{$book->id}", ['title' => 'New title', 'author' => 'New Author'])
            ->assertOk()
            ->assertJsonPath('data.title', 'New title')
            ->assertJsonPath('data.author', 'New Author');

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'New title']);
    }

    public function test_update_validates_input_and_404s_for_unknown_book(): void
    {
        $book = Book::factory()->create();

        $this->putJson("/api/books/{$book->id}", ['title' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');

        $this->putJson('/api/books/999999', ['title' => 'Whatever'])->assertNotFound();
    }

    public function test_destroy_deletes_a_book(): void
    {
        $book = Book::factory()->create();

        $this->deleteJson("/api/books/{$book->id}")->assertNoContent();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->deleteJson("/api/books/{$book->id}")->assertNotFound();
    }
}
