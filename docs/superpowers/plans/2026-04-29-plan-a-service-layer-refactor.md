# Plan A — Refactor a Service Layer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Estrarre la logica di business dai controller `BookmarkController` e `CategoryController` in un service layer richiamabile in modo agnostico da rotte web, API e job. Il comportamento web pubblico rimane identico (test esistenti verdi).

**Architecture:** Service single-responsibility (granularità SOLID) con DTO `readonly` (PHP 8.4) come input, eccezioni di dominio per i fallimenti di business, Form Request condivise per validazione. I controller diventano traduttori I/O sottili. Nessuna feature nuova: solo refactor di non-regressione.

**Tech Stack:** Laravel 13, PHP 8.4, Pest 4, Inertia v3, Sanctum 4 (già installato), Scout 11.

---

## Setup di esecuzione (eseguire UNA volta prima del Task 1)

- [ ] **Step S1: Crea il branch dedicato**

```bash
git checkout -b feat/service-layer-and-api master
```

Tutti i commit dei task seguenti vanno su questo branch. Il merge su `master` avviene a fine Plan C (o a discrezione dell'utente).

---

## File Structure

**Nuovi file:**
- `app/Data/Bookmarks/CreateBookmarkData.php` — DTO input creazione bookmark
- `app/Data/Bookmarks/ListBookmarksFilters.php` — DTO filtri di listing
- `app/Data/Categories/CreateCategoryData.php` — DTO input creazione categoria
- `app/Data/Categories/UpdateCategoryData.php` — DTO input update categoria
- `app/Exceptions/Bookmarks/DuplicateBookmarkException.php`
- `app/Exceptions/Bookmarks/CategoryNotOwnedException.php`
- `app/Services/Bookmarks/BookmarkUrlNormalizer.php`
- `app/Services/Bookmarks/BookmarkCreator.php`
- `app/Services/Bookmarks/BookmarkProgressUpdater.php`
- `app/Services/Bookmarks/BookmarkRemover.php`
- `app/Services/Bookmarks/BookmarkLister.php`
- `app/Services/Categories/CategorySlugGenerator.php`
- `app/Services/Categories/CategoryCreator.php`
- `app/Services/Categories/CategoryUpdater.php`
- `app/Services/Categories/CategoryRemover.php`
- `app/Http/Requests/Bookmarks/StoreBookmarkRequest.php`
- `app/Http/Requests/Bookmarks/UpdateBookmarkProgressRequest.php`
- `app/Http/Requests/Bookmarks/IndexBookmarksRequest.php`
- `app/Http/Requests/Categories/StoreCategoryRequest.php`
- `app/Http/Requests/Categories/UpdateCategoryRequest.php`

**Modificati:**
- `app/Http/Controllers/BookmarkController.php` — refactor a controller sottile
- `app/Http/Controllers/CategoryController.php` — refactor a controller sottile

**Test esistenti** (devono restare verdi senza modifiche):
- `tests/Feature/BookmarkControllerTest.php`
- `tests/Feature/CategoryControllerTest.php`

**Nuovi test unit:**
- `tests/Unit/Services/Bookmarks/BookmarkUrlNormalizerTest.php`
- `tests/Unit/Services/Bookmarks/BookmarkCreatorTest.php`
- `tests/Unit/Services/Bookmarks/BookmarkProgressUpdaterTest.php`
- `tests/Unit/Services/Bookmarks/BookmarkRemoverTest.php`
- `tests/Unit/Services/Categories/CategorySlugGeneratorTest.php`
- `tests/Unit/Services/Categories/CategoryCreatorTest.php`
- `tests/Unit/Services/Categories/CategoryUpdaterTest.php`

---

## Task 1: `BookmarkUrlNormalizer` (utility, no DB)

**Files:**
- Create: `app/Services/Bookmarks/BookmarkUrlNormalizer.php`
- Test: `tests/Unit/Services/Bookmarks/BookmarkUrlNormalizerTest.php`

- [ ] **Step 1: Crea il test che fallisce**

```bash
php artisan make:test --pest --unit Services/Bookmarks/BookmarkUrlNormalizerTest --no-interaction
```

Sostituisci il contenuto con:

```php
<?php

use App\Services\Bookmarks\BookmarkUrlNormalizer;

it('strips fragments from urls', function () {
    $normalizer = new BookmarkUrlNormalizer;

    expect($normalizer->normalize('https://example.com/article#heading'))
        ->toBe('https://example.com/article');
});

it('trims surrounding whitespace', function () {
    $normalizer = new BookmarkUrlNormalizer;

    expect($normalizer->normalize('  https://example.com/article  '))
        ->toBe('https://example.com/article');
});

it('returns url unchanged when no fragment is present', function () {
    $normalizer = new BookmarkUrlNormalizer;

    expect($normalizer->normalize('https://example.com/article?ref=foo'))
        ->toBe('https://example.com/article?ref=foo');
});
```

- [ ] **Step 2: Esegui il test e verifica che fallisca**

```bash
php artisan test --compact --filter=BookmarkUrlNormalizerTest
```

Atteso: FAIL (classe non esiste).

- [ ] **Step 3: Implementa il service**

```php
<?php

namespace App\Services\Bookmarks;

class BookmarkUrlNormalizer
{
    public function normalize(string $url): string
    {
        $url = trim($url);
        $hashPos = strpos($url, '#');

        return $hashPos === false ? $url : substr($url, 0, $hashPos);
    }
}
```

- [ ] **Step 4: Esegui il test e verifica che passi**

```bash
php artisan test --compact --filter=BookmarkUrlNormalizerTest
```

Atteso: 3 PASS.

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Bookmarks/BookmarkUrlNormalizer.php tests/Unit/Services/Bookmarks/BookmarkUrlNormalizerTest.php
git commit -m "feat(services): add BookmarkUrlNormalizer service"
```

---

## Task 2: DTO `CreateBookmarkData`

**Files:**
- Create: `app/Data/Bookmarks/CreateBookmarkData.php`

- [ ] **Step 1: Crea il file DTO**

```php
<?php

namespace App\Data\Bookmarks;

final readonly class CreateBookmarkData
{
    public function __construct(
        public string $url,
        public ?int $categoryId = null,
    ) {}
}
```

- [ ] **Step 2: Verifica sintassi**

```bash
php -l app/Data/Bookmarks/CreateBookmarkData.php
```

Atteso: `No syntax errors detected`.

- [ ] **Step 3: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Data/Bookmarks/CreateBookmarkData.php
git commit -m "feat(data): add CreateBookmarkData DTO"
```

---

## Task 3: DTO `ListBookmarksFilters`

**Files:**
- Create: `app/Data/Bookmarks/ListBookmarksFilters.php`

- [ ] **Step 1: Crea il file DTO**

```php
<?php

namespace App\Data\Bookmarks;

final readonly class ListBookmarksFilters
{
    public function __construct(
        public ?string $query = null,
        public ?string $categorySlug = null,
        public int $page = 1,
        public int $perPage = 9,
    ) {}
}
```

- [ ] **Step 2: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Data/Bookmarks/ListBookmarksFilters.php
git commit -m "feat(data): add ListBookmarksFilters DTO"
```

---

## Task 4: Domain exceptions per bookmark

**Files:**
- Create: `app/Exceptions/Bookmarks/DuplicateBookmarkException.php`
- Create: `app/Exceptions/Bookmarks/CategoryNotOwnedException.php`

- [ ] **Step 1: Crea `DuplicateBookmarkException`**

```php
<?php

namespace App\Exceptions\Bookmarks;

use DomainException;

class DuplicateBookmarkException extends DomainException
{
    public function __construct(public readonly string $url)
    {
        parent::__construct("Bookmark with URL [{$url}] already exists for this user.");
    }
}
```

- [ ] **Step 2: Crea `CategoryNotOwnedException`**

```php
<?php

namespace App\Exceptions\Bookmarks;

use DomainException;

class CategoryNotOwnedException extends DomainException
{
    public function __construct(public readonly int $categoryId)
    {
        parent::__construct("Category [{$categoryId}] does not belong to the current user.");
    }
}
```

- [ ] **Step 3: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exceptions/Bookmarks/
git commit -m "feat(exceptions): add bookmark domain exceptions"
```

---

## Task 5: `BookmarkCreator` service (cuore del refactor)

**Files:**
- Create: `app/Services/Bookmarks/BookmarkCreator.php`
- Test: `tests/Unit/Services/Bookmarks/BookmarkCreatorTest.php`

Questo service riproduce esattamente la logica attuale di `BookmarkController::store`:
1. Normalizza URL
2. Se `categoryId` presente, verifica appartenenza all'utente → altrimenti `CategoryNotOwnedException`
3. Verifica unicità URL per utente → altrimenti `DuplicateBookmarkException`
4. Crea il bookmark con status `pending`
5. Dispatcha la chain `ExtractBookmarkMetadataJob` → `ParseArticleContentJob`
6. Restituisce il `Bookmark` creato

- [ ] **Step 1: Crea il test (Feature, perché tocca DB e Bus)**

```bash
mkdir -p tests/Unit/Services/Bookmarks
```

Crea `tests/Unit/Services/Bookmarks/BookmarkCreatorTest.php`:

```php
<?php

use App\Data\Bookmarks\CreateBookmarkData;
use App\Exceptions\Bookmarks\CategoryNotOwnedException;
use App\Exceptions\Bookmarks\DuplicateBookmarkException;
use App\Jobs\ExtractBookmarkMetadataJob;
use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use App\Services\Bookmarks\BookmarkCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('creates a bookmark with pending status and dispatches job chain', function () {
    Bus::fake();
    $user = User::factory()->create();

    $bookmark = app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/article'),
    );

    expect($bookmark)->toBeInstanceOf(Bookmark::class)
        ->and($bookmark->user_id)->toBe($user->id)
        ->and($bookmark->url)->toBe('https://example.com/article')
        ->and($bookmark->status)->toBe('pending')
        ->and($bookmark->category_id)->toBeNull();

    Bus::assertChained([
        fn (ExtractBookmarkMetadataJob $job) => $job->bookmark->is($bookmark),
        fn (ParseArticleContentJob $job) => $job->bookmark->is($bookmark),
    ]);
});

it('normalizes the url before persisting', function () {
    Bus::fake();
    $user = User::factory()->create();

    $bookmark = app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/article#heading'),
    );

    expect($bookmark->url)->toBe('https://example.com/article');
});

it('attaches the category when owned by the user', function () {
    Bus::fake();
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    $bookmark = app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/x', categoryId: $category->id),
    );

    expect($bookmark->category_id)->toBe($category->id);
});

it('throws CategoryNotOwnedException when the category belongs to another user', function () {
    Bus::fake();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $category = Category::factory()->for($other)->create();

    expect(fn () => app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/x', categoryId: $category->id),
    ))->toThrow(CategoryNotOwnedException::class);

    expect(Bookmark::count())->toBe(0);
    Bus::assertNothingDispatched();
});

it('throws DuplicateBookmarkException when the same user already saved the url', function () {
    Bus::fake();
    $user = User::factory()->create();
    Bookmark::factory()->for($user)->create(['url' => 'https://example.com/dup']);

    expect(fn () => app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/dup'),
    ))->toThrow(DuplicateBookmarkException::class);

    expect(Bookmark::where('user_id', $user->id)->count())->toBe(1);
    Bus::assertNothingDispatched();
});

it('treats fragmented duplicate as duplicate after normalization', function () {
    Bus::fake();
    $user = User::factory()->create();
    Bookmark::factory()->for($user)->create(['url' => 'https://example.com/dup']);

    expect(fn () => app(BookmarkCreator::class)->create(
        $user,
        new CreateBookmarkData(url: 'https://example.com/dup#section'),
    ))->toThrow(DuplicateBookmarkException::class);
});
```

Aggiungi `uses(RefreshDatabase::class)` non basta perché `tests/Pest.php` applica `RefreshDatabase` solo a `Feature/`. Sposta il file in `tests/Feature/Services/Bookmarks/BookmarkCreatorTest.php` invece — è un test funzionale che usa il DB.

```bash
mkdir -p tests/Feature/Services/Bookmarks
mv tests/Unit/Services/Bookmarks/BookmarkCreatorTest.php tests/Feature/Services/Bookmarks/BookmarkCreatorTest.php
```

Rimuovi la riga `uses(RefreshDatabase::class);` dal file (è automatica per `Feature/`).

- [ ] **Step 2: Verifica che il test fallisca**

```bash
php artisan test --compact --filter=BookmarkCreatorTest
```

Atteso: FAIL (classe service non esiste).

- [ ] **Step 3: Implementa il service**

```php
<?php

namespace App\Services\Bookmarks;

use App\Data\Bookmarks\CreateBookmarkData;
use App\Exceptions\Bookmarks\CategoryNotOwnedException;
use App\Exceptions\Bookmarks\DuplicateBookmarkException;
use App\Jobs\ExtractBookmarkMetadataJob;
use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

class BookmarkCreator
{
    public function __construct(
        private BookmarkUrlNormalizer $normalizer,
    ) {}

    /**
     * @throws CategoryNotOwnedException
     * @throws DuplicateBookmarkException
     */
    public function create(User $user, CreateBookmarkData $data): Bookmark
    {
        $url = $this->normalizer->normalize($data->url);

        if ($data->categoryId !== null) {
            $owns = $user->categories()->whereKey($data->categoryId)->exists();
            if (! $owns) {
                throw new CategoryNotOwnedException($data->categoryId);
            }
        }

        if ($user->bookmarks()->where('url', $url)->exists()) {
            throw new DuplicateBookmarkException($url);
        }

        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'category_id' => $data->categoryId,
            'url' => $url,
            'status' => 'pending',
        ]);

        Bus::chain([
            new ExtractBookmarkMetadataJob($bookmark),
            new ParseArticleContentJob($bookmark),
        ])->dispatch();

        return $bookmark;
    }
}
```

- [ ] **Step 4: Esegui i test e verifica che passino**

```bash
php artisan test --compact --filter=BookmarkCreatorTest
```

Atteso: 6 PASS.

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Bookmarks/BookmarkCreator.php tests/Feature/Services/Bookmarks/BookmarkCreatorTest.php
git commit -m "feat(services): add BookmarkCreator service"
```

---

## Task 6: `BookmarkProgressUpdater` service

**Files:**
- Create: `app/Services/Bookmarks/BookmarkProgressUpdater.php`
- Test: `tests/Feature/Services/Bookmarks/BookmarkProgressUpdaterTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Models\Bookmark;
use App\Models\User;
use App\Services\Bookmarks\BookmarkProgressUpdater;

it('updates scroll_position and bumps reading_progress to max seen', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create([
        'scroll_position' => 100,
        'reading_progress' => 30,
    ]);

    app(BookmarkProgressUpdater::class)->update($bookmark, 50);

    $bookmark->refresh();
    expect($bookmark->scroll_position)->toBe(50)
        ->and($bookmark->reading_progress)->toBe(50);
});

it('keeps reading_progress when new value is lower', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create([
        'scroll_position' => 100,
        'reading_progress' => 80,
    ]);

    app(BookmarkProgressUpdater::class)->update($bookmark, 40);

    $bookmark->refresh();
    expect($bookmark->scroll_position)->toBe(40)
        ->and($bookmark->reading_progress)->toBe(80);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=BookmarkProgressUpdaterTest
```

Atteso: FAIL.

- [ ] **Step 3: Implementa il service**

```php
<?php

namespace App\Services\Bookmarks;

use App\Models\Bookmark;

class BookmarkProgressUpdater
{
    public function update(Bookmark $bookmark, int $progress): void
    {
        $bookmark->update([
            'scroll_position' => $progress,
            'reading_progress' => max($progress, $bookmark->reading_progress),
        ]);
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=BookmarkProgressUpdaterTest
```

Atteso: 2 PASS.

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Bookmarks/BookmarkProgressUpdater.php tests/Feature/Services/Bookmarks/BookmarkProgressUpdaterTest.php
git commit -m "feat(services): add BookmarkProgressUpdater service"
```

---

## Task 7: `BookmarkRemover` service

**Files:**
- Create: `app/Services/Bookmarks/BookmarkRemover.php`
- Test: `tests/Feature/Services/Bookmarks/BookmarkRemoverTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Models\Bookmark;
use App\Models\User;
use App\Services\Bookmarks\BookmarkRemover;

it('deletes the given bookmark', function () {
    $user = User::factory()->create();
    $bookmark = Bookmark::factory()->for($user)->create();

    app(BookmarkRemover::class)->delete($bookmark);

    $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=BookmarkRemoverTest
```

- [ ] **Step 3: Implementa**

```php
<?php

namespace App\Services\Bookmarks;

use App\Models\Bookmark;

class BookmarkRemover
{
    public function delete(Bookmark $bookmark): void
    {
        $bookmark->delete();
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=BookmarkRemoverTest
```

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Bookmarks/BookmarkRemover.php tests/Feature/Services/Bookmarks/BookmarkRemoverTest.php
git commit -m "feat(services): add BookmarkRemover service"
```

---

## Task 8: `BookmarkLister` service

Estrae sia il ramo Eloquent (lista paginata con filtro categoria opzionale) sia il ramo Scout (search). Restituisce un array `['paginator' => ..., 'highlights' => ..., 'activeCategory' => ?Category]` per essere agnostico rispetto al frontend.

**Files:**
- Create: `app/Services/Bookmarks/BookmarkLister.php`
- Test: `tests/Feature/Services/Bookmarks/BookmarkListerTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Data\Bookmarks\ListBookmarksFilters;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use App\Services\Bookmarks\BookmarkLister;

it('lists current user bookmarks paginated', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Bookmark::factory()->for($user)->count(3)->create();
    Bookmark::factory()->for($other)->count(5)->create();

    $result = app(BookmarkLister::class)->list($user, new ListBookmarksFilters);

    expect($result['paginator']->total())->toBe(3)
        ->and($result['highlights'])->toBe([])
        ->and($result['activeCategory'])->toBeNull();
});

it('filters by active category slug', function () {
    $user = User::factory()->create();
    $tech = Category::factory()->for($user)->create(['slug' => 'tech']);
    $news = Category::factory()->for($user)->create(['slug' => 'news']);

    Bookmark::factory()->for($user)->for($tech)->count(2)->create();
    Bookmark::factory()->for($user)->for($news)->count(4)->create();

    $result = app(BookmarkLister::class)->list(
        $user,
        new ListBookmarksFilters(categorySlug: 'tech'),
    );

    expect($result['paginator']->total())->toBe(2)
        ->and($result['activeCategory']?->slug)->toBe('tech');
});

it('returns null active category when slug is unknown', function () {
    $user = User::factory()->create();

    $result = app(BookmarkLister::class)->list(
        $user,
        new ListBookmarksFilters(categorySlug: 'does-not-exist'),
    );

    expect($result['activeCategory'])->toBeNull()
        ->and($result['paginator']->total())->toBe(0);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=BookmarkListerTest
```

- [ ] **Step 3: Implementa il service**

Nota: il service riusa l'esistente `BookmarkSearchService`. La firma di `search()` richiede `path` e `queryParams` per costruire i link di paginazione — il service li accetta come parametri opzionali nel filtro per i casi web; per uso API il chiamante passa stringhe vuote/array vuoti.

Estendi prima il DTO `ListBookmarksFilters` aggiungendo i due campi:

```php
<?php

namespace App\Data\Bookmarks;

final readonly class ListBookmarksFilters
{
    /**
     * @param  array<string, mixed>  $queryParams
     */
    public function __construct(
        public ?string $query = null,
        public ?string $categorySlug = null,
        public int $page = 1,
        public int $perPage = 9,
        public string $path = '',
        public array $queryParams = [],
    ) {}
}
```

Poi crea `app/Services/Bookmarks/BookmarkLister.php`:

```php
<?php

namespace App\Services\Bookmarks;

use App\Data\Bookmarks\ListBookmarksFilters;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use App\Services\Search\BookmarkSearchService;
use Illuminate\Pagination\LengthAwarePaginator;

class BookmarkLister
{
    public function __construct(
        private BookmarkSearchService $search,
    ) {}

    /**
     * @return array{paginator: LengthAwarePaginator, highlights: array<int, array{title?: string, content_text?: string}>, activeCategory: ?Category}
     */
    public function list(User $user, ListBookmarksFilters $filters): array
    {
        $activeCategory = $filters->categorySlug
            ? Category::where('user_id', $user->id)->where('slug', $filters->categorySlug)->first()
            : null;

        $query = trim((string) ($filters->query ?? ''));

        if ($query !== '') {
            $result = $this->search->search(
                query: $query,
                userId: $user->id,
                categoryId: $activeCategory?->id,
                perPage: $filters->perPage,
                page: $filters->page,
                path: $filters->path,
                queryParams: $filters->queryParams,
            );

            return [
                'paginator' => $result['paginator'],
                'highlights' => $result['highlights'],
                'activeCategory' => $activeCategory,
            ];
        }

        $paginator = Bookmark::query()
            ->where('user_id', $user->id)
            ->with('category:id,name,slug,color')
            ->when($activeCategory, fn ($q) => $q->where('category_id', $activeCategory->id))
            ->orderByDesc('created_at')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page)
            ->withQueryString();

        return [
            'paginator' => $paginator,
            'highlights' => [],
            'activeCategory' => $activeCategory,
        ];
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=BookmarkListerTest
```

Atteso: 3 PASS.

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Data/Bookmarks/ListBookmarksFilters.php app/Services/Bookmarks/BookmarkLister.php tests/Feature/Services/Bookmarks/BookmarkListerTest.php
git commit -m "feat(services): add BookmarkLister service"
```

---

## Task 9: `CategorySlugGenerator`

**Files:**
- Create: `app/Services/Categories/CategorySlugGenerator.php`
- Test: `tests/Feature/Services/Categories/CategorySlugGeneratorTest.php`

- [ ] **Step 1: Crea il test**

```bash
mkdir -p tests/Feature/Services/Categories
```

```php
<?php

use App\Models\Category;
use App\Models\User;
use App\Services\Categories\CategorySlugGenerator;

it('generates a slug from name when no conflicts exist', function () {
    $user = User::factory()->create();

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'My Cool Category');

    expect($slug)->toBe('my-cool-category');
});

it('appends an incrementing suffix on conflict', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['slug' => 'tech']);

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'Tech');

    expect($slug)->toBe('tech-2');
});

it('skips taken suffixes', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['slug' => 'tech']);
    Category::factory()->for($user)->create(['slug' => 'tech-2']);

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'Tech');

    expect($slug)->toBe('tech-3');
});

it('ignores the excluded category id when checking conflicts', function () {
    $user = User::factory()->create();
    $existing = Category::factory()->for($user)->create(['slug' => 'tech']);

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'Tech', exceptId: $existing->id);

    expect($slug)->toBe('tech');
});

it('scopes uniqueness per user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Category::factory()->for($other)->create(['slug' => 'tech']);

    $slug = app(CategorySlugGenerator::class)->uniqueFor($user, 'Tech');

    expect($slug)->toBe('tech');
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=CategorySlugGeneratorTest
```

- [ ] **Step 3: Implementa il service**

```php
<?php

namespace App\Services\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class CategorySlugGenerator
{
    public function uniqueFor(User $user, string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name);
        $candidate = $base;
        $count = 2;

        while ($this->slugExists($user, $candidate, $exceptId)) {
            $candidate = "{$base}-{$count}";
            $count++;
        }

        return $candidate;
    }

    private function slugExists(User $user, string $slug, ?int $exceptId): bool
    {
        $query = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', $slug);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=CategorySlugGeneratorTest
```

Atteso: 5 PASS.

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Categories/CategorySlugGenerator.php tests/Feature/Services/Categories/CategorySlugGeneratorTest.php
git commit -m "feat(services): add CategorySlugGenerator service"
```

---

## Task 10: DTO categorie

**Files:**
- Create: `app/Data/Categories/CreateCategoryData.php`
- Create: `app/Data/Categories/UpdateCategoryData.php`

- [ ] **Step 1: Crea `CreateCategoryData`**

```php
<?php

namespace App\Data\Categories;

final readonly class CreateCategoryData
{
    public function __construct(
        public string $name,
        public ?string $color = null,
    ) {}
}
```

- [ ] **Step 2: Crea `UpdateCategoryData`**

```php
<?php

namespace App\Data\Categories;

final readonly class UpdateCategoryData
{
    public function __construct(
        public ?string $name = null,
        public ?string $color = null,
    ) {}

    public function hasName(): bool
    {
        return $this->name !== null;
    }
}
```

- [ ] **Step 3: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Data/Categories/
git commit -m "feat(data): add Category DTOs"
```

---

## Task 11: `CategoryCreator` service

**Files:**
- Create: `app/Services/Categories/CategoryCreator.php`
- Test: `tests/Feature/Services/Categories/CategoryCreatorTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Data\Categories\CreateCategoryData;
use App\Models\Category;
use App\Models\User;
use App\Services\Categories\CategoryCreator;

it('creates a category with auto-generated slug for the user', function () {
    $user = User::factory()->create();

    $category = app(CategoryCreator::class)->create(
        $user,
        new CreateCategoryData(name: 'My Reads', color: '#FF0000'),
    );

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->user_id)->toBe($user->id)
        ->and($category->name)->toBe('My Reads')
        ->and($category->slug)->toBe('my-reads')
        ->and($category->color)->toBe('#FF0000');
});

it('disambiguates slug on conflict for the same user', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['slug' => 'tech']);

    $category = app(CategoryCreator::class)->create(
        $user,
        new CreateCategoryData(name: 'Tech'),
    );

    expect($category->slug)->toBe('tech-2');
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=CategoryCreatorTest
```

- [ ] **Step 3: Implementa il service**

```php
<?php

namespace App\Services\Categories;

use App\Data\Categories\CreateCategoryData;
use App\Models\Category;
use App\Models\User;

class CategoryCreator
{
    public function __construct(
        private CategorySlugGenerator $slugger,
    ) {}

    public function create(User $user, CreateCategoryData $data): Category
    {
        return Category::create([
            'user_id' => $user->id,
            'name' => $data->name,
            'color' => $data->color,
            'slug' => $this->slugger->uniqueFor($user, $data->name),
        ]);
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=CategoryCreatorTest
```

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Categories/CategoryCreator.php tests/Feature/Services/Categories/CategoryCreatorTest.php
git commit -m "feat(services): add CategoryCreator service"
```

---

## Task 12: `CategoryUpdater` service

**Files:**
- Create: `app/Services/Categories/CategoryUpdater.php`
- Test: `tests/Feature/Services/Categories/CategoryUpdaterTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Data\Categories\UpdateCategoryData;
use App\Models\Category;
use App\Models\User;
use App\Services\Categories\CategoryUpdater;

it('updates only color when name is not provided', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create([
        'name' => 'Original',
        'slug' => 'original',
        'color' => '#000000',
    ]);

    app(CategoryUpdater::class)->update($category, new UpdateCategoryData(color: '#FFFFFF'));

    $category->refresh();
    expect($category->name)->toBe('Original')
        ->and($category->slug)->toBe('original')
        ->and($category->color)->toBe('#FFFFFF');
});

it('regenerates slug when name changes', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create([
        'name' => 'Original',
        'slug' => 'original',
    ]);

    app(CategoryUpdater::class)->update($category, new UpdateCategoryData(name: 'Renamed'));

    $category->refresh();
    expect($category->name)->toBe('Renamed')
        ->and($category->slug)->toBe('renamed');
});

it('disambiguates slug when target name conflicts with sibling', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'First', 'slug' => 'first']);
    $second = Category::factory()->for($user)->create(['name' => 'Second', 'slug' => 'second']);

    app(CategoryUpdater::class)->update($second, new UpdateCategoryData(name: 'First'));

    $second->refresh();
    expect($second->slug)->toBe('first-2');
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=CategoryUpdaterTest
```

- [ ] **Step 3: Implementa il service**

```php
<?php

namespace App\Services\Categories;

use App\Data\Categories\UpdateCategoryData;
use App\Models\Category;

class CategoryUpdater
{
    public function __construct(
        private CategorySlugGenerator $slugger,
    ) {}

    public function update(Category $category, UpdateCategoryData $data): Category
    {
        $attributes = [];

        if ($data->hasName()) {
            $attributes['name'] = $data->name;
            $attributes['slug'] = $this->slugger->uniqueFor(
                $category->user,
                $data->name,
                exceptId: $category->id,
            );
        }

        if ($data->color !== null) {
            $attributes['color'] = $data->color;
        }

        if ($attributes !== []) {
            $category->update($attributes);
        }

        return $category;
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=CategoryUpdaterTest
```

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Categories/CategoryUpdater.php tests/Feature/Services/Categories/CategoryUpdaterTest.php
git commit -m "feat(services): add CategoryUpdater service"
```

---

## Task 13: `CategoryRemover` service

**Files:**
- Create: `app/Services/Categories/CategoryRemover.php`
- Test: `tests/Feature/Services/Categories/CategoryRemoverTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Models\Category;
use App\Models\User;
use App\Services\Categories\CategoryRemover;

it('deletes the given category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();

    app(CategoryRemover::class)->delete($category);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=CategoryRemoverTest
```

- [ ] **Step 3: Implementa**

```php
<?php

namespace App\Services\Categories;

use App\Models\Category;

class CategoryRemover
{
    public function delete(Category $category): void
    {
        $category->delete();
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=CategoryRemoverTest
```

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Categories/CategoryRemover.php tests/Feature/Services/Categories/CategoryRemoverTest.php
git commit -m "feat(services): add CategoryRemover service"
```

---

## Task 14: Form Request per bookmark

**Files:**
- Create: `app/Http/Requests/Bookmarks/StoreBookmarkRequest.php`
- Create: `app/Http/Requests/Bookmarks/UpdateBookmarkProgressRequest.php`
- Create: `app/Http/Requests/Bookmarks/IndexBookmarksRequest.php`

- [ ] **Step 1: Crea `StoreBookmarkRequest`**

```bash
php artisan make:request Bookmarks/StoreBookmarkRequest --no-interaction
```

Sostituisci il contenuto:

```php
<?php

namespace App\Http\Requests\Bookmarks;

use App\Data\Bookmarks\CreateBookmarkData;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url:http,https', 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    public function toData(): CreateBookmarkData
    {
        return new CreateBookmarkData(
            url: $this->string('url')->toString(),
            categoryId: $this->integer('category_id') ?: null,
        );
    }
}
```

- [ ] **Step 2: Crea `UpdateBookmarkProgressRequest`**

```bash
php artisan make:request Bookmarks/UpdateBookmarkProgressRequest --no-interaction
```

```php
<?php

namespace App\Http\Requests\Bookmarks;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookmarkProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array
    {
        return [
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function progress(): int
    {
        return $this->integer('progress');
    }
}
```

- [ ] **Step 3: Crea `IndexBookmarksRequest`**

```bash
php artisan make:request Bookmarks/IndexBookmarksRequest --no-interaction
```

```php
<?php

namespace App\Http\Requests\Bookmarks;

use App\Data\Bookmarks\ListBookmarksFilters;
use Illuminate\Foundation\Http\FormRequest;

class IndexBookmarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toFilters(int $perPage = 9): ListBookmarksFilters
    {
        return new ListBookmarksFilters(
            query: $this->filled('q') ? trim($this->string('q')->toString()) : null,
            categorySlug: $this->filled('category') ? $this->string('category')->toString() : null,
            page: max($this->integer('page', 1), 1),
            perPage: $perPage,
            path: $this->url(),
            queryParams: $this->query(),
        );
    }
}
```

- [ ] **Step 4: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/Bookmarks/
git commit -m "feat(http): add bookmark form requests"
```

---

## Task 15: Form Request per categorie

**Files:**
- Create: `app/Http/Requests/Categories/StoreCategoryRequest.php`
- Create: `app/Http/Requests/Categories/UpdateCategoryRequest.php`

- [ ] **Step 1: Crea `StoreCategoryRequest`**

```bash
php artisan make:request Categories/StoreCategoryRequest --no-interaction
```

```php
<?php

namespace App\Http\Requests\Categories;

use App\Data\Categories\CreateCategoryData;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:7'],
        ];
    }

    public function toData(): CreateCategoryData
    {
        return new CreateCategoryData(
            name: $this->string('name')->toString(),
            color: $this->filled('color') ? $this->string('color')->toString() : null,
        );
    }
}
```

- [ ] **Step 2: Crea `UpdateCategoryRequest`**

```bash
php artisan make:request Categories/UpdateCategoryRequest --no-interaction
```

```php
<?php

namespace App\Http\Requests\Categories;

use App\Data\Categories\UpdateCategoryData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['sometimes', 'nullable', 'string', 'max:7'],
        ];
    }

    public function toData(): UpdateCategoryData
    {
        return new UpdateCategoryData(
            name: $this->has('name') ? $this->string('name')->toString() : null,
            color: $this->has('color')
                ? ($this->filled('color') ? $this->string('color')->toString() : null)
                : null,
        );
    }
}
```

- [ ] **Step 3: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/Categories/
git commit -m "feat(http): add category form requests"
```

---

## Task 16: Refactor `BookmarkController` per usare i service

**Files:**
- Modify: `app/Http/Controllers/BookmarkController.php`

Il controller diventa traduttore I/O. Mantieni invariato il contratto dei test esistenti:
- `store` redirige a `bookmarks.index`, con `withErrors(['url' => ...])` per duplicate, con flash toast `error: 'Invalid category'` per categoria non posseduta.
- `index` ritorna Inertia con stessi prop key.
- `read`, `updateProgress`, `destroy` invariati.

- [ ] **Step 1: Sostituisci il controller**

```php
<?php

namespace App\Http\Controllers;

use App\Exceptions\Bookmarks\CategoryNotOwnedException;
use App\Exceptions\Bookmarks\DuplicateBookmarkException;
use App\Http\Requests\Bookmarks\IndexBookmarksRequest;
use App\Http\Requests\Bookmarks\StoreBookmarkRequest;
use App\Http\Requests\Bookmarks\UpdateBookmarkProgressRequest;
use App\Models\Bookmark;
use App\Models\Category;
use App\Services\Bookmarks\BookmarkCreator;
use App\Services\Bookmarks\BookmarkLister;
use App\Services\Bookmarks\BookmarkProgressUpdater;
use App\Services\Bookmarks\BookmarkRemover;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BookmarkController extends Controller
{
    public function index(IndexBookmarksRequest $request, BookmarkLister $lister)
    {
        $user = $request->user();
        $result = $lister->list($user, $request->toFilters());

        return Inertia::render('bookmarks/index', [
            'bookmarks' => $result['paginator'],
            'categories' => Category::where('user_id', $user->id)->orderBy('name')->get(),
            'activeCategory' => $result['activeCategory']?->slug,
            'q' => $request->filled('q') ? trim($request->string('q')->toString()) : null,
            'highlights' => $result['highlights'],
        ]);
    }

    public function store(StoreBookmarkRequest $request, BookmarkCreator $creator)
    {
        try {
            $creator->create($request->user(), $request->toData());
        } catch (CategoryNotOwnedException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Invalid category']);

            return redirect()->route('bookmarks.index');
        } catch (DuplicateBookmarkException) {
            return redirect()->route('bookmarks.index')
                ->withErrors(['url' => 'You have already saved this URL.']);
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('bookmarks.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bookmark saved, extracting metadata...']);

        return redirect()->route('bookmarks.index');
    }

    public function read(Request $request, Bookmark $bookmark)
    {
        Gate::authorize('view', $bookmark);

        return Inertia::render('bookmarks/read', [
            'bookmark' => $bookmark->load('category:id,name,slug,color'),
        ]);
    }

    public function updateProgress(
        UpdateBookmarkProgressRequest $request,
        Bookmark $bookmark,
        BookmarkProgressUpdater $updater,
    ) {
        Gate::authorize('update', $bookmark);

        $updater->update($bookmark, $request->progress());

        return response()->noContent();
    }

    public function destroy(Request $request, Bookmark $bookmark, BookmarkRemover $remover)
    {
        Gate::authorize('delete', $bookmark);

        try {
            $remover->delete($bookmark);
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('bookmarks.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bookmark deleted successfully']);

        return redirect()->route('bookmarks.index');
    }
}
```

- [ ] **Step 2: Esegui i test esistenti del bookmark controller**

```bash
php artisan test --compact --filter=BookmarkControllerTest
```

Atteso: tutti i test esistenti PASS senza modifiche al test file.

- [ ] **Step 3: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/BookmarkController.php
git commit -m "refactor(controllers): thin BookmarkController via service layer"
```

---

## Task 17: Refactor `CategoryController` per usare i service

**Files:**
- Modify: `app/Http/Controllers/CategoryController.php`

- [ ] **Step 1: Sostituisci il controller**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Categories\CategoryCreator;
use App\Services\Categories\CategoryRemover;
use App\Services\Categories\CategoryUpdater;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('categories/index', [
            'categories' => Category::withCount('bookmarks')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request, CategoryCreator $creator)
    {
        try {
            $creator->create($request->user(), $request->toData());
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('categories.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category created successfully']);

        return redirect()->route('categories.index');
    }

    public function update(UpdateCategoryRequest $request, Category $category, CategoryUpdater $updater)
    {
        try {
            $updater->update($category, $request->toData());
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated successfully']);

        return redirect()->route('categories.index');
    }

    public function destroy(Category $category, CategoryRemover $remover)
    {
        try {
            $remover->delete($category);
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return redirect()->route('categories.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted successfully']);

        return redirect()->route('categories.index');
    }
}
```

- [ ] **Step 2: Esegui i test esistenti del category controller**

```bash
php artisan test --compact --filter=CategoryControllerTest
```

Atteso: tutti i test esistenti PASS senza modifiche al test file.

- [ ] **Step 3: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/CategoryController.php
git commit -m "refactor(controllers): thin CategoryController via service layer"
```

---

## Task 18: Verifica completa di non-regressione

- [ ] **Step 1: Esegui l'intera suite di test**

```bash
php artisan test --compact
```

Atteso: tutti i test PASS.

- [ ] **Step 2: Pint check finale**

```bash
vendor/bin/pint --format agent
```

Atteso: nessun file modificato (tutto già formattato).

- [ ] **Step 3: Commit finale (se Pint ha modificato qualcosa)**

```bash
git status
# Se ci sono modifiche pendenti:
git add -A
git commit -m "chore: pint format pass"
```

---

## Note finali Plan A

Al termine del Plan A:
- I controller `BookmarkController` e `CategoryController` sono sottili (~30-50 LOC ciascuno).
- Tutta la logica di business è nei service `app/Services/{Bookmarks,Categories}/`.
- I service sono richiamabili da qualsiasi entrypoint (web, API, console, job, test).
- Le eccezioni di dominio rendono espliciti i fallimenti business.
- I test web esistenti restano verdi (proof di non-regressione).
- I service hanno ciascuno una test feature dedicata (proof di correttezza unitaria).

Questo piano è **prerequisito** per Plan B (auth API) e Plan C (endpoint API).