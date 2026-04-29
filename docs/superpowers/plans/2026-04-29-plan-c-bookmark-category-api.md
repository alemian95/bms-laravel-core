# Plan C — Bookmark & Category API Endpoints Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementare il punto 4.2 del `todo.md`: endpoint API per gestione remota dei bookmark (`POST /api/v1/bookmarks`) e listing delle categorie (`GET /api/v1/categories`), riusando il service layer del Plan A. Gli endpoint applicano abilities granulari Sanctum del Plan B.

**Architecture:** Controller API sottili che chiamano gli stessi service del Plan A. Form Request specifiche `Api/V1/` per validazione (rules identiche al web ma `expectsJson()` come default). API Resources per shape JSON stabile. Middleware `ability:...` sulle rotte per applicare il principio del minimo privilegio basato sui token preset. Le eccezioni di dominio (`DuplicateBookmarkException`, `CategoryNotOwnedException`) vengono mappate a status code HTTP precisi.

**Tech Stack:** Laravel 13, PHP 8.4, Sanctum 4, Pest 4.

---

## Setup di esecuzione

- [ ] **Step S1: Verifica branch corrente**

```bash
git branch --show-current
```

Atteso: `feat/service-layer-and-api`.

- [ ] **Step S2: Verifica che Plan A e B siano completati**

```bash
php artisan test --compact
```

Atteso: tutti i test PASS, inclusi i test login/logout/api-tokens.

---

## File Structure

**Nuovi file:**
- `app/Http/Resources/Api/V1/BookmarkResource.php`
- `app/Http/Resources/Api/V1/CategoryResource.php`
- `app/Http/Requests/Api/V1/StoreBookmarkRequest.php`
- `app/Http/Controllers/Api/V1/BookmarkController.php`
- `app/Http/Controllers/Api/V1/CategoryController.php`
- `tests/Feature/Api/V1/StoreBookmarkTest.php`
- `tests/Feature/Api/V1/IndexCategoriesTest.php`

**Modificati:**
- `routes/api.php` — aggiunte rotte v1 bookmarks e categories con middleware ability
- `todo.md` — flag attività 4.2 completate

---

## Task 1: `CategoryResource`

**Files:**
- Create: `app/Http/Resources/Api/V1/CategoryResource.php`

- [ ] **Step 1: Crea il resource**

```bash
php artisan make:resource Api/V1/CategoryResource --no-interaction
```

Sostituisci con:

```php
<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 2: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Resources/Api/V1/CategoryResource.php
git commit -m "feat(api): add CategoryResource"
```

---

## Task 2: `BookmarkResource`

**Files:**
- Create: `app/Http/Resources/Api/V1/BookmarkResource.php`

- [ ] **Step 1: Crea il resource**

```bash
php artisan make:resource Api/V1/BookmarkResource --no-interaction
```

```php
<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Bookmark
 */
class BookmarkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'title' => $this->title,
            'domain' => $this->domain,
            'author' => $this->author,
            'thumbnail_url' => $this->thumbnail_url,
            'status' => $this->status,
            'reading_progress' => $this->reading_progress,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 2: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Resources/Api/V1/BookmarkResource.php
git commit -m "feat(api): add BookmarkResource"
```

---

## Task 3: `Api/V1/CategoryController` + rotta

**Files:**
- Create: `app/Http/Controllers/Api/V1/CategoryController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V1/IndexCategoriesTest.php`

- [ ] **Step 1: Crea il test**

```bash
mkdir -p tests/Feature/Api/V1
```

```php
<?php

use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns the authenticated user categories ordered by name', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Zebra']);
    Category::factory()->for($user)->create(['name' => 'Alpha']);
    Category::factory()->for(User::factory())->create(['name' => 'Foreign']);

    Sanctum::actingAs($user, ['categories:read']);

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Alpha')
        ->assertJsonPath('data.1.name', 'Zebra');
});

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/v1/categories')->assertUnauthorized();
});

it('rejects tokens without categories:read ability', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $this->getJson('/api/v1/categories')->assertForbidden();
});

it('accepts wildcard ability', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create();
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/categories')->assertOk();
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=IndexCategoriesTest
```

- [ ] **Step 3: Crea il controller**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }
}
```

- [ ] **Step 4: Aggiungi la rotta in `routes/api.php`**

Dentro il gruppo `auth:sanctum` esistente (creato dal Plan B), aggiungi:

```php
use App\Http\Controllers\Api\V1\CategoryController;

Route::get('categories', [CategoryController::class, 'index'])
    ->middleware('ability:categories:read')
    ->name('api.v1.categories.index');
```

- [ ] **Step 5: Verifica passaggio**

```bash
php artisan test --compact --filter=IndexCategoriesTest
```

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Api/V1/CategoryController.php routes/api.php tests/Feature/Api/V1/IndexCategoriesTest.php
git commit -m "feat(api): add v1 categories index endpoint"
```

---

## Task 4: `Api/V1/StoreBookmarkRequest`

**Files:**
- Create: `app/Http/Requests/Api/V1/StoreBookmarkRequest.php`

> **Nota progettuale:** la Form Request creata nel Plan A (`App\Http\Requests\Bookmarks\StoreBookmarkRequest`) è già compatibile per uso API: ritorna automaticamente JSON per richieste con `Accept: application/json`. Tuttavia per chiarezza di namespace e per consentire eventuale evoluzione divergente, ne creiamo una v1 dedicata.

- [ ] **Step 1: Crea il file**

```bash
php artisan make:request Api/V1/StoreBookmarkRequest --no-interaction
```

```php
<?php

namespace App\Http\Requests\Api\V1;

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

- [ ] **Step 2: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/Api/V1/
git commit -m "feat(api): add v1 StoreBookmarkRequest"
```

---

## Task 5: `Api/V1/BookmarkController` + rotta

**Files:**
- Create: `app/Http/Controllers/Api/V1/BookmarkController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V1/StoreBookmarkTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Jobs\ExtractBookmarkMetadataJob;
use App\Jobs\ParseArticleContentJob;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

it('stores a bookmark and dispatches the metadata + parse chain', function () {
    Bus::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/article',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'url', 'status']])
        ->assertJsonPath('data.url', 'https://example.com/article')
        ->assertJsonPath('data.status', 'pending');

    Bus::assertChained([
        fn (ExtractBookmarkMetadataJob $job) => $job->bookmark->user_id === $user->id,
        fn (ParseArticleContentJob $job) => $job->bookmark->user_id === $user->id,
    ]);
});

it('stores a bookmark with a category id', function () {
    Bus::fake();
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/x',
        'category_id' => $category->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.category_id', $category->id);
});

it('returns 422 when url is missing', function () {
    Bus::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $response = $this->postJson('/api/v1/bookmarks', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['url']);
});

it('returns 422 when url is not a valid http(s) url', function () {
    Bus::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:create']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'ftp://example.com/file',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['url']);
});

it('returns 409 when the user already saved the same url', function () {
    Bus::fake();
    $user = User::factory()->create();
    Bookmark::factory()->for($user)->create(['url' => 'https://example.com/dup']);
    Sanctum::actingAs($user, ['bookmarks:create']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/dup',
    ]);

    $response->assertStatus(409)
        ->assertJson(['message' => 'Bookmark already exists.']);

    expect(Bookmark::where('user_id', $user->id)->count())->toBe(1);
});

it('returns 422 when category does not belong to user', function () {
    Bus::fake();
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreignCategory = Category::factory()->for($other)->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/x',
        'category_id' => $foreignCategory->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category_id']);

    expect(Bookmark::count())->toBe(0);
});

it('rejects unauthenticated requests', function () {
    $this->postJson('/api/v1/bookmarks', ['url' => 'https://example.com'])
        ->assertUnauthorized();
});

it('rejects tokens without bookmarks:create ability', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['bookmarks:read']);

    $this->postJson('/api/v1/bookmarks', ['url' => 'https://example.com'])
        ->assertForbidden();
});

it('normalizes the url before persisting', function () {
    Bus::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/bookmarks', [
        'url' => 'https://example.com/article#section',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.url', 'https://example.com/article');
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=StoreBookmarkTest
```

- [ ] **Step 3: Crea il controller**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Bookmarks\CategoryNotOwnedException;
use App\Exceptions\Bookmarks\DuplicateBookmarkException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBookmarkRequest;
use App\Http\Resources\Api\V1\BookmarkResource;
use App\Services\Bookmarks\BookmarkCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BookmarkController extends Controller
{
    public function store(StoreBookmarkRequest $request, BookmarkCreator $creator): BookmarkResource|JsonResponse
    {
        try {
            $bookmark = $creator->create($request->user(), $request->toData());
        } catch (DuplicateBookmarkException) {
            return response()->json(['message' => 'Bookmark already exists.'], 409);
        } catch (CategoryNotOwnedException) {
            throw ValidationException::withMessages([
                'category_id' => 'The selected category is invalid.',
            ]);
        }

        return BookmarkResource::make($bookmark)
            ->response()
            ->setStatusCode(201);
    }
}
```

- [ ] **Step 4: Aggiungi la rotta in `routes/api.php`**

Dentro il gruppo `auth:sanctum`, aggiungi:

```php
use App\Http\Controllers\Api\V1\BookmarkController;

Route::post('bookmarks', [BookmarkController::class, 'store'])
    ->middleware('ability:bookmarks:create')
    ->name('api.v1.bookmarks.store');
```

- [ ] **Step 5: Verifica passaggio**

```bash
php artisan test --compact --filter=StoreBookmarkTest
```

Atteso: 9 PASS.

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Api/V1/BookmarkController.php routes/api.php tests/Feature/Api/V1/StoreBookmarkTest.php
git commit -m "feat(api): add v1 bookmarks store endpoint"
```

---

## Task 6: Verifica route list e suite completa

- [ ] **Step 1: Ispeziona route list per la sezione api**

```bash
php artisan route:list --path=api
```

Atteso (almeno):
```
POST     api/v1/login        api.v1.login
POST     api/v1/logout       api.v1.logout
GET      api/v1/user         api.v1.user
GET      api/v1/categories   api.v1.categories.index
POST     api/v1/bookmarks    api.v1.bookmarks.store
```

- [ ] **Step 2: Suite completa**

```bash
php artisan test --compact
```

Atteso: tutti i test PASS.

- [ ] **Step 3: Pint check finale**

```bash
vendor/bin/pint --format agent
```

---

## Task 7: Aggiorna `todo.md` (4.2)

**Files:**
- Modify: `todo.md`

- [ ] **Step 1: Flagga le tre voci di 4.2**

```diff
- * **4.2 Endpoints Gestione Bookmark**
-     * [ ] Creare `POST /api/bookmarks` per il salvataggio remoto. Deve accettare `url` e `category_id` (opzionale).
-     * [ ] Assicurarsi che questo endpoint agganci automaticamente i Job creati nel Prototipo 1 (estrazione metadati e parsing).
-     * [ ] Creare `GET /api/categories` per permettere ai client esterni di mostrare la lista delle categorie salvate.
+ * **4.2 Endpoints Gestione Bookmark**
+     * [x] Creare `POST /api/bookmarks` per il salvataggio remoto. Deve accettare `url` e `category_id` (opzionale).
+     * [x] Assicurarsi che questo endpoint agganci automaticamente i Job creati nel Prototipo 1 (estrazione metadati e parsing).
+     * [x] Creare `GET /api/categories` per permettere ai client esterni di mostrare la lista delle categorie salvate.
```

> **Nota:** le rotte effettive sono `POST /api/v1/bookmarks` e `GET /api/v1/categories`; il riferimento simbolico in `todo.md` è equivalente.

- [ ] **Step 2: Commit**

```bash
git add todo.md
git commit -m "chore: mark todo 4.2 as completed"
```

---

## Task 8: Finalizzazione branch (handoff)

- [ ] **Step 1: Riepilogo commit**

```bash
git log --oneline master..HEAD
```

Mostra all'utente l'elenco dei commit del branch.

- [ ] **Step 2: Suite finale + lint**

```bash
composer ci:check
```

Atteso: tutti i check PASS (pint, prettier, eslint, types, test).

- [ ] **Step 3: Lascia all'utente la decisione di merge/PR**

A questo punto il branch `feat/service-layer-and-api` contiene:
- Plan A: refactor service layer
- Plan B: 4.1 auth API + PAT
- Plan C: 4.2 endpoint bookmark/categorie

L'utente deciderà se fare merge diretto su `master` o aprire una PR. **Non eseguire `git push`, `git merge`, o `gh pr create` senza richiesta esplicita dell'utente.**

---

## Note finali Plan C

Al termine del Plan C:
- `POST /api/v1/bookmarks` operativo con dispatch automatico della chain di job (riusa `BookmarkCreator` del Plan A).
- `GET /api/v1/categories` operativo.
- Entrambi protetti da `auth:sanctum` + abilities granulari (`bookmarks:create`, `categories:read`).
- I punti 4.1 e 4.2 del `todo.md` sono completati.
- L'estensione browser e l'app mobile (4.3 e 4.4) ora hanno tutti gli endpoint necessari per il prossimo prototipo.