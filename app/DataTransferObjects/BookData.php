<?php

namespace App\DataTransferObjects;

/**
 * Provider-agnostic representation of a book, as returned by a BookProviderInterface.
 */
final readonly class BookData
{
    public function __construct(
        public string $externalId,
        public string $title,
        public ?string $author = null,
        public ?int $firstPublishYear = null,
        public ?string $coverUrl = null,
    ) {}
}
