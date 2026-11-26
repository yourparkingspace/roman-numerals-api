<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Open Library search endpoint.
 *
 * HAPPY PATH ONLY: it assumes the API is up, fast and well-behaved. Making the integration
 * robust, and turning the raw result into BookData, is part of the exercise.
 */
class OpenLibraryClient
{
    /**
     * @return array<int, array<string, mixed>>|null The raw `docs` array from the response.
     */
    public function search(string $query, int $limit = 10): ?array
    {
        return Http::baseUrl(config('services.open_library.base_url'))
            ->acceptJson()
            ->get('/search.json', [
                'q' => $query,
                'limit' => $limit,
                'fields' => 'key,title,author_name,first_publish_year,cover_i',
            ])
            ->json('docs');
    }
}
