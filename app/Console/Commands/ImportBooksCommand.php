<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportBooksCommand extends Command
{
    protected $signature = 'books:import
                            {query : Search term, e.g. "dune"}
                            {--limit=10 : Maximum number of books to fetch (1-25)}';

    protected $description = 'Import books matching a search term from the external provider into the local database';

    public function handle(): int
    {
        // TODO (candidate, Part 3): see README.md. Reuse the same code the HTTP endpoint uses.
        $this->error('Not implemented yet.');

        return self::FAILURE;
    }
}
