# Candidate Guide

Welcome, and thanks for your time. This guide explains how the session works and what we look for.
The task itself is described in [README.md](README.md).

## How the session works

* **Duration:** about 60 minutes of working time, plus a short discussion at the end.
* **Format:** you code, we watch and are happy to answer questions. Think out loud, since we care about *how* you
  approach the problem as much as the result.
* **Resources:** you can use the Laravel docs, the Open Library docs and a search engine. 
* **Questions are welcome.** Asking for clarification on an ambiguous requirement is a good sign, not
  a weakness.

## Suggested time plan

You don't have to follow this, but it is a realistic split:

| Time | Focus |
| --- | --- |
| 0-5 min | Read the README, run the setup, run the tests, skim the provided code |
| 5-22 min | **Part 1**: mapping, hardening the client, container binding, import service and endpoint |
| 22-50 min | **Part 2**: CRUD endpoints, form requests, resources |
| 50-60 min | Part 3 (stretch), cleanup, run the whole suite, write your notes |

Part 3 is a stretch goal. A clean, finished Parts 1 and 2 beats a rushed Part 3.

## What we evaluate

1. **Correctness.** Do the endpoints behave as specified, including the edge cases (idempotent import,
   failure handling, validation, 404s)?
2. **Following the constraints.** Interface usage, thin controllers, Form Requests, Resources.
3. **Code quality.** Readable, consistently named, no dead code, sensible structure. Idiomatic Laravel
   rather than clever.
4. **Error handling.** What happens when the external API is slow, down or returns garbage?
5. **Testing.** Do you run the tests regularly? Do you add a test for something the provided ones
   don't cover?
6. **Communication.** Can you explain your decisions and trade-offs?

We are **not** looking for perfection or extra features. Don't gold-plate.

## Tips

* Run `php artisan test` early and often. Pick one failing test and make it pass.
* `tests/Fixtures/open_library_search.json` is a real-shaped response. Use it to understand the data
  before you write the mapping code.
* You can hit the real API in your browser:
  `https://openlibrary.org/search.json?q=dune&limit=3&fields=key,title,author_name,first_publish_year,cover_i`
* `OpenLibraryClient` is provided but naive. Read it critically: what happens when the API is slow, down or returns garbage?
* In tests, HTTP is faked with `Http::fake()`. You will never need the real API to be up for the tests to pass.
* Register `POST /api/books/import` **before** `Route::apiResource('books', ...)`, otherwise `import` may
  be matched as a book id.
* If you get stuck for more than ~5 minutes, say so and we will nudge you. It won't count against you.
* If something is ambiguous and you make an assumption, write it down.

## When you're done

1. Run `php artisan test` and make sure everything you implemented passes.
2. Create a short `NOTES.md` (a few bullet points is enough) covering:
   * Assumptions you made
   * What you would do next with more time (retries, caching, queueing imports, etc.)
   * Anything you are unhappy with
3. Tell your interviewer you're finished. We will walk through the code together and ask a few
   questions about your choices.

Good luck!
