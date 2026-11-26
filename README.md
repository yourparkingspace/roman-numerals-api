# Book Catalogue API

A backend coding exercise (about **60 minutes**). You will integrate with a public third-party API,
store the results in MySQL, and expose a small REST API on top of them.

Read this file fully first, then read [CANDIDATE_GUIDE.md](CANDIDATE_GUIDE.md) for tips, how we
evaluate, and how to submit.

---

## The scenario

We are building a small internal "book catalogue" service. Staff can **import** books from the
[Open Library](https://openlibrary.org) search API into our own database, and then **list, view,
create, edit and delete** books through our own API.

You do **not** need an API key for Open Library.

## What is already in the repo

| Path | What it is |
| --- | --- |
| `app/Contracts/BookProviderInterface.php` | The contract your provider **must** implement. **Do not change it.** |
| `app/DataTransferObjects/BookData.php` | Provider-agnostic book DTO returned by the provider. |
| `app/Exceptions/BookProviderException.php` | The only exception a provider may throw. |
| `app/Services/OpenLibraryClient.php` | Thin client that performs the search request and returns the raw `docs` array. **Happy path only** (see Part 1). |
| `config/services.php` | Holds the Open Library base URL (`OPEN_LIBRARY_BASE_URL`). |
| `app/Models/Book.php`, `database/factories/BookFactory.php` | Eloquent model and factory. |
| `database/migrations/*_create_books_table.php` | The `books` table. |
| `app/Console/Commands/ImportBooksCommand.php` | Command skeleton for Part 3. |
| `routes/api.php` | Empty. Routes are served under `/api`. |
| `tests/` | Acceptance tests (currently failing) and a fixture of a real Open Library response. |

Everything else (provider, import service, controllers, form requests, resources, routes, container
binding) is yours to write.

## Test

Run the tests (they use an in-memory SQLite database and fake all HTTP calls, so no MySQL or internet
is required):

```bash
php artisan test
```

Out of the box about 27 of the 28 tests fail. **Making them pass is the goal**, but passing tests alone
is not the whole assessment (see the guide).

---

## The task

### Part 1: Provider and import endpoint (core)

**1a. Implement `BookProviderInterface`** for Open Library, as `App\Services\OpenLibraryBookProvider`
(or similar), built on top of the provided `OpenLibraryClient`.

`OpenLibraryClient` already performs the request
(`GET /search.json?q=...&limit=...&fields=key,title,author_name,first_publish_year,cover_i`) and returns
the raw `docs` array. It is deliberately naive. You own everything after the HTTP call:

* Map each raw result in `docs[]` to a `BookData`:

  | Open Library field | `BookData` | Rule |
  | --- | --- | --- |
  | `key` (`/works/OL893415W`) | `externalId` | last path segment, e.g. `OL893415W` |
  | `title` | `title` | |
  | `author_name` (array) | `author` | joined with `", "`; `null` if missing |
  | `first_publish_year` | `firstPublishYear` | `null` if missing |
  | `cover_i` | `coverUrl` | `https://covers.openlibrary.org/b/id/{cover_i}-M.jpg`; `null` if missing |

* Make the integration **robust**. The provided client has no timeout and no error handling. Add a
  sensible timeout, and on a connection failure, a non-2xx response, or a malformed body throw
  `BookProviderException`. Do not let HTTP-client exceptions escape the provider. You may change the
  client or wrap it, your choice.
* Bind the interface to your implementation in the service container.

**1b. `POST /api/books/import`**

Request body:

```json
{ "query": "dune", "limit": 10 }
```

* `query`: required, string, 2-100 chars. `limit`: optional integer 1-25, default 10.
* Fetch books through the interface and store the ones we don't already have.
* **Idempotent**: a book is identified by `external_id`. Re-importing must not create duplicates and
  must **not overwrite** an existing row (it may have been edited locally); count it as skipped.
* Response `200`:

  ```json
  {
    "data": [ { "id": 1, "title": "Dune", "...": "only the NEWLY imported books" } ],
    "meta": { "imported": 3, "skipped": 1 }
  }
  ```
* If the provider fails, respond `502` with `{ "message": "..." }` and store nothing. Do not leak
  internal error details.

### Part 2: CRUD endpoints

| Method | Path | Behaviour |
| --- | --- | --- |
| `GET` | `/api/books` | Paginated list (default 15, `?per_page=` up to 50). Filters: `?author=` (partial, case-insensitive match) and `?year=` (exact match on first publish year). Filters combine. |
| `GET` | `/api/books/{id}` | Single book, `404` if missing. |
| `POST` | `/api/books` | Create a book. `201` on success. |
| `PUT` | `/api/books/{id}` | Update a book. `200`, `404` if missing. |
| `DELETE` | `/api/books/{id}` | Delete a book. `204`, `404` if missing. |

Validation for create and update:

* `title`: required, string, max 255
* `author`: optional, string, max 255
* `first_publish_year`: optional, integer, between 1000 and the current year
* `cover_url`: optional, valid URL, max 500
* `external_id` can **not** be set by API clients (ignore it if sent). It belongs to the importer.

Every book is returned in this shape (single responses are wrapped in `data`):

```json
{
  "id": 1,
  "external_id": "OL893415W",
  "title": "Dune",
  "author": "Frank Herbert",
  "first_publish_year": 1965,
  "cover_url": "https://covers.openlibrary.org/b/id/12345-M.jpg",
  "created_at": "2026-01-01T10:00:00+00:00"
}
```

Validation failures must return `422` with Laravel's standard JSON error body.

### Part 3: Artisan command (stretch)

Implement the provided `books:import {query} {--limit=10}` command. It must **reuse the same code path**
as the HTTP endpoint (don't copy and paste the import logic).

* On success print exactly: `Imported {n} book(s), skipped {m}.` and exit `0`.
* If `--limit` is outside 1-25, print an error and exit non-zero.
* If the provider fails, print a message starting with `Could not reach the book provider:` and
  exit non-zero.

---

## Constraints (non-negotiable)

1. **Implement `BookProviderInterface`** and don't modify it. Controllers, services and the command may
   only depend on the *interface*, never on `OpenLibraryBookProvider` directly. (A test swaps in a
   fake provider to check this.)
2. **Thin controllers.** Import logic lives in a service/action class, not in a controller.
3. Use **Form Requests** for validation and **API Resources** for responses.
4. Use **migrations/Eloquent** only. No raw SQL needed.
5. No new Composer packages unless you can justify them. Everything required ships with Laravel.
6. Do not edit the provided tests to make them pass. You **may add** your own.

## Out of scope

Authentication, caching, queues, rate limiting, API docs/OpenAPI, deployment, a frontend. Don't spend
time on these. If you would do them in real life, just mention it in your notes.
