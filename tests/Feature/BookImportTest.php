<?php

namespace Tests\Feature;

use App\Contracts\BookProviderInterface;
use App\DataTransferObjects\BookData;
use App\Exceptions\BookProviderException;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Part 1: provider + import endpoint.
 */
class BookImportTest extends TestCase
{
    use RefreshDatabase;

    private function fakeOpenLibrary(): void
    {
        Http::fake([
            'openlibrary.org/*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/open_library_search.json')),
                200,
                ['Content-Type' => 'application/json'],
            ),
        ]);
    }

    public function test_interface_is_bound_in_the_container(): void
    {
        $provider = $this->app->make(BookProviderInterface::class);

        $this->assertInstanceOf(BookProviderInterface::class, $provider);
    }

    public function test_provider_maps_open_library_response_to_book_data(): void
    {
        $this->fakeOpenLibrary();

        $books = $this->app->make(BookProviderInterface::class)->search('dune', 10);

        $this->assertCount(4, $books);
        $this->assertContainsOnlyInstancesOf(BookData::class, $books);

        // Full record
        $this->assertSame('OL893415W', $books[0]->externalId);
        $this->assertSame('Dune', $books[0]->title);
        $this->assertSame('Frank Herbert', $books[0]->author);
        $this->assertSame(1965, $books[0]->firstPublishYear);
        $this->assertSame('https://covers.openlibrary.org/b/id/12345-M.jpg', $books[0]->coverUrl);

        // Multiple authors are joined with ", "
        $this->assertSame('Brian Herbert, Kevin J. Anderson', $books[2]->author);

        // Optional fields are null when upstream omits them
        $this->assertNull($books[3]->author);
        $this->assertNull($books[3]->firstPublishYear);
        $this->assertNull($books[3]->coverUrl);
    }

    public function test_provider_sends_query_and_limit_to_open_library(): void
    {
        $this->fakeOpenLibrary();

        $this->app->make(BookProviderInterface::class)->search('the hobbit', 5);

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://openlibrary.org/search.json')
                && $request['q'] === 'the hobbit'
                && (int) $request['limit'] === 5;
        });
    }

    public function test_provider_wraps_upstream_errors_in_book_provider_exception(): void
    {
        Http::fake(['openlibrary.org/*' => Http::response('boom', 500)]);

        $this->expectException(BookProviderException::class);

        $this->app->make(BookProviderInterface::class)->search('dune');
    }

    public function test_provider_wraps_connection_failures_in_book_provider_exception(): void
    {
        Http::fake(['openlibrary.org/*' => fn () => throw new ConnectionException('timed out')]);

        $this->expectException(BookProviderException::class);

        $this->app->make(BookProviderInterface::class)->search('dune');
    }

    public function test_import_stores_books_from_the_provider(): void
    {
        $this->fakeOpenLibrary();

        $response = $this->postJson('/api/books/import', ['query' => 'dune']);

        $response->assertOk()
            ->assertJsonPath('meta.imported', 4)
            ->assertJsonPath('meta.skipped', 0)
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.title', 'Dune');

        $this->assertDatabaseCount('books', 4);
        $this->assertDatabaseHas('books', [
            'external_id' => 'OL893415W',
            'title' => 'Dune',
            'author' => 'Frank Herbert',
            'first_publish_year' => 1965,
            'cover_url' => 'https://covers.openlibrary.org/b/id/12345-M.jpg',
        ]);
    }

    public function test_import_is_idempotent_and_does_not_overwrite_existing_books(): void
    {
        $this->fakeOpenLibrary();
        Book::factory()->create(['external_id' => 'OL893415W', 'title' => 'My Edited Title']);

        $first = $this->postJson('/api/books/import', ['query' => 'dune']);
        $second = $this->postJson('/api/books/import', ['query' => 'dune']);

        $first->assertOk()->assertJsonPath('meta.imported', 3)->assertJsonPath('meta.skipped', 1);
        $second->assertOk()->assertJsonPath('meta.imported', 0)->assertJsonPath('meta.skipped', 4);

        $this->assertDatabaseCount('books', 4);
        $this->assertDatabaseHas('books', ['external_id' => 'OL893415W', 'title' => 'My Edited Title']);
    }

    public function test_import_depends_on_the_interface_not_a_concrete_provider(): void
    {
        $this->app->bind(BookProviderInterface::class, fn () => new class implements BookProviderInterface
        {
            public function search(string $query, int $limit = 10): array
            {
                return [new BookData(externalId: 'FAKE1', title: 'From A Fake Provider', author: 'Tester')];
            }
        });

        $this->postJson('/api/books/import', ['query' => 'anything'])
            ->assertOk()
            ->assertJsonPath('meta.imported', 1);

        $this->assertDatabaseHas('books', ['external_id' => 'FAKE1', 'title' => 'From A Fake Provider']);
    }

    public function test_import_returns_502_when_the_provider_fails(): void
    {
        Http::fake(['openlibrary.org/*' => Http::response('boom', 503)]);

        $this->postJson('/api/books/import', ['query' => 'dune'])
            ->assertStatus(502)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_import_returns_502_when_the_provider_times_out(): void
    {
        Http::fake(['openlibrary.org/*' => fn () => throw new ConnectionException('timed out')]);

        $this->postJson('/api/books/import', ['query' => 'dune'])
            ->assertStatus(502)
            ->assertJsonStructure(['message']);
    }

    public function test_import_validates_the_request(): void
    {
        Http::fake();

        $this->postJson('/api/books/import', [])->assertStatus(422)->assertJsonValidationErrors('query');
        $this->postJson('/api/books/import', ['query' => 'a'])->assertStatus(422)->assertJsonValidationErrors('query');
        $this->postJson('/api/books/import', ['query' => 'dune', 'limit' => 100])->assertStatus(422)->assertJsonValidationErrors('limit');

        Http::assertNothingSent();
    }
}
