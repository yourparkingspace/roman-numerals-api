<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Part 3 (stretch): the books:import artisan command.
 */
class ImportBooksCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_imports_books_and_reports_a_summary(): void
    {
        Http::fake([
            'openlibrary.org/*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/open_library_search.json')),
                200,
            ),
        ]);

        $this->artisan('books:import', ['query' => 'dune'])
            ->expectsOutputToContain('Imported 4 book(s), skipped 0')
            ->assertSuccessful();

        $this->assertDatabaseCount('books', 4);

        $this->artisan('books:import', ['query' => 'dune'])
            ->expectsOutputToContain('Imported 0 book(s), skipped 4')
            ->assertSuccessful();

        $this->assertDatabaseCount('books', 4);
    }

    public function test_command_passes_the_limit_option_to_the_provider(): void
    {
        Http::fake(['openlibrary.org/*' => Http::response(['docs' => []], 200)]);

        $this->artisan('books:import', ['query' => 'dune', '--limit' => 3])->assertSuccessful();

        Http::assertSent(fn ($request) => (int) $request['limit'] === 3);
    }

    public function test_command_fails_gracefully_when_the_provider_is_down(): void
    {
        Http::fake(['openlibrary.org/*' => fn () => throw new ConnectionException('timed out')]);

        $this->artisan('books:import', ['query' => 'dune'])
            ->expectsOutputToContain('Could not reach')
            ->assertFailed();

        $this->assertDatabaseCount('books', 0);
    }
}
