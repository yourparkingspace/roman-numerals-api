<?php

namespace App\Contracts;

use App\DataTransferObjects\BookData;
use App\Exceptions\BookProviderException;

/**
 * A source of book data (e.g. an external HTTP API).
 *
 * DO NOT CHANGE THIS INTERFACE. Your implementation must satisfy it and the rest of
 * the application must only ever depend on this interface, never on a concrete class.
 */
interface BookProviderInterface
{
    /**
     * Search the provider for books matching the given free-text query.
     *
     * @param  int  $limit  Maximum number of books to return.
     * @return list<BookData> An empty list when nothing matches.
     *
     * @throws BookProviderException When the provider is unreachable, times out, or answers
     *                               with an unusable response. Implementations must NOT leak
     *                               HTTP-client exceptions to callers.
     */
    public function search(string $query, int $limit = 10): array;
}
