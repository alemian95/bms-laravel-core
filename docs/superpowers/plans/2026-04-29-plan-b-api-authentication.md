# Plan B — API Authentication & Personal Access Token Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementare il punto 4.1 del `todo.md`: autenticazione API tramite Sanctum, login API per app mobile, e UI in dashboard utente per emettere Personal Access Token (PAT) con `abilities` granulari basate su preset.

**Architecture:** Le rotte API vivono sotto `/api/v1/` con guard `auth:sanctum`. Il login API restituisce un PAT con abilities scelte (default: `*`). I token utente generati dalla dashboard hanno abilities determinate da preset (`browser-extension`, `mobile-app`, `full-access`) modellati come enum. Servizi single-responsibility per emissione/revoca token. La rotta singleton `/user` viene spostata sotto `/api/v1/user`. Dipendenza implicita: il Plan A (service layer) deve essere completato.

**Tech Stack:** Laravel 13, PHP 8.4, Sanctum 4, Pest 4, Inertia v3, React 19, Wayfinder, Tailwind v4.

---

## Setup di esecuzione

- [ ] **Step S1: Verifica di essere sul branch del Plan A**

```bash
git branch --show-current
```

Atteso: `feat/service-layer-and-api`. Se sei su `master`, fai `git checkout feat/service-layer-and-api`.

- [ ] **Step S2: Verifica che Plan A sia completato**

```bash
php artisan test --compact
```

Atteso: tutti i test PASS, inclusi i nuovi test dei service.

---

## File Structure

**Nuovi file:**
- `app/Enums/TokenAbility.php` — enum delle abilities atomiche
- `app/Enums/TokenPreset.php` — enum dei preset con metodo `abilities()`
- `app/Data/Auth/LoginData.php` — DTO login API
- `app/Data/Auth/IssueTokenData.php` — DTO emissione PAT
- `app/Exceptions/Auth/InvalidApiCredentialsException.php`
- `app/Services/Auth/ApiAuthenticator.php` — login API
- `app/Services/Auth/ApiTokenIssuer.php` — emissione PAT
- `app/Services/Auth/ApiTokenRevoker.php` — revoca PAT
- `app/Http/Requests/Api/V1/Auth/LoginRequest.php`
- `app/Http/Requests/Settings/StoreApiTokenRequest.php`
- `app/Http/Controllers/Api/V1/Auth/LoginController.php`
- `app/Http/Controllers/Api/V1/Auth/LogoutController.php`
- `app/Http/Controllers/Api/V1/Auth/AuthenticatedUserController.php`
- `app/Http/Controllers/Settings/ApiTokenController.php`
- `app/Http/Resources/Api/V1/AuthTokenResource.php`
- `resources/js/pages/settings/api-tokens.tsx`
- `tests/Feature/Api/V1/Auth/LoginTest.php`
- `tests/Feature/Api/V1/Auth/LogoutTest.php`
- `tests/Feature/Settings/ApiTokenManagementTest.php`
- `tests/Feature/Services/Auth/ApiAuthenticatorTest.php`
- `tests/Feature/Services/Auth/ApiTokenIssuerTest.php`

**Modificati:**
- `routes/api.php` — sposta `/user` sotto `/v1/`, aggiunge login/logout
- `routes/settings.php` — aggiunge route management PAT
- `resources/js/components/app-sidebar.tsx` (o equivalente) — voce di menu "API Tokens" (solo lettura per ora; verifica la struttura corrente prima di modificare)
- `todo.md` — flag attività 4.1 completate

---

## Task 1: Enum `TokenAbility`

**Files:**
- Create: `app/Enums/TokenAbility.php`

- [ ] **Step 1: Crea l'enum**

```bash
php artisan make:enum TokenAbility --string --no-interaction
```

Sostituisci il contenuto:

```php
<?php

namespace App\Enums;

enum TokenAbility: string
{
    case BookmarksRead = 'bookmarks:read';
    case BookmarksCreate = 'bookmarks:create';
    case BookmarksDelete = 'bookmarks:delete';
    case CategoriesRead = 'categories:read';
    case CategoriesWrite = 'categories:write';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
```

- [ ] **Step 2: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/TokenAbility.php
git commit -m "feat(auth): add TokenAbility enum"
```

---

## Task 2: Enum `TokenPreset`

**Files:**
- Create: `app/Enums/TokenPreset.php`
- Test: `tests/Unit/Enums/TokenPresetTest.php`

- [ ] **Step 1: Crea il test**

```bash
mkdir -p tests/Unit/Enums
```

```php
<?php

use App\Enums\TokenAbility;
use App\Enums\TokenPreset;

it('browser extension preset has create+read abilities', function () {
    expect(TokenPreset::BrowserExtension->abilities())
        ->toBe([TokenAbility::BookmarksCreate->value, TokenAbility::CategoriesRead->value]);
});

it('mobile app preset has read+create+categories', function () {
    expect(TokenPreset::MobileApp->abilities())
        ->toBe([
            TokenAbility::BookmarksRead->value,
            TokenAbility::BookmarksCreate->value,
            TokenAbility::CategoriesRead->value,
        ]);
});

it('full access preset has wildcard ability', function () {
    expect(TokenPreset::FullAccess->abilities())->toBe(['*']);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=TokenPresetTest
```

- [ ] **Step 3: Implementa l'enum**

```php
<?php

namespace App\Enums;

enum TokenPreset: string
{
    case BrowserExtension = 'browser-extension';
    case MobileApp = 'mobile-app';
    case FullAccess = 'full-access';

    /**
     * @return array<int, string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::BrowserExtension => [
                TokenAbility::BookmarksCreate->value,
                TokenAbility::CategoriesRead->value,
            ],
            self::MobileApp => [
                TokenAbility::BookmarksRead->value,
                TokenAbility::BookmarksCreate->value,
                TokenAbility::CategoriesRead->value,
            ],
            self::FullAccess => ['*'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BrowserExtension => 'Browser Extension',
            self::MobileApp => 'Mobile App',
            self::FullAccess => 'Full Access',
        };
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=TokenPresetTest
```

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/TokenPreset.php tests/Unit/Enums/TokenPresetTest.php
git commit -m "feat(auth): add TokenPreset enum with abilities mapping"
```

---

## Task 3: DTO `LoginData` e `IssueTokenData`

**Files:**
- Create: `app/Data/Auth/LoginData.php`
- Create: `app/Data/Auth/IssueTokenData.php`

- [ ] **Step 1: Crea `LoginData`**

```php
<?php

namespace App\Data\Auth;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName,
    ) {}
}
```

- [ ] **Step 2: Crea `IssueTokenData`**

```php
<?php

namespace App\Data\Auth;

use App\Enums\TokenPreset;

final readonly class IssueTokenData
{
    public function __construct(
        public string $name,
        public TokenPreset $preset,
    ) {}
}
```

- [ ] **Step 3: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Data/Auth/
git commit -m "feat(auth): add auth DTOs"
```

---

## Task 4: `InvalidApiCredentialsException`

**Files:**
- Create: `app/Exceptions/Auth/InvalidApiCredentialsException.php`

- [ ] **Step 1: Crea l'eccezione**

```php
<?php

namespace App\Exceptions\Auth;

use DomainException;

class InvalidApiCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The provided credentials are incorrect.');
    }
}
```

- [ ] **Step 2: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exceptions/Auth/
git commit -m "feat(auth): add InvalidApiCredentialsException"
```

---

## Task 5: `ApiTokenIssuer` service

**Files:**
- Create: `app/Services/Auth/ApiTokenIssuer.php`
- Test: `tests/Feature/Services/Auth/ApiTokenIssuerTest.php`

- [ ] **Step 1: Crea il test**

```bash
mkdir -p tests/Feature/Services/Auth
```

```php
<?php

use App\Data\Auth\IssueTokenData;
use App\Enums\TokenPreset;
use App\Models\User;
use App\Services\Auth\ApiTokenIssuer;
use Laravel\Sanctum\NewAccessToken;

it('issues a sanctum token with abilities from the chosen preset', function () {
    $user = User::factory()->create();

    $token = app(ApiTokenIssuer::class)->issue(
        $user,
        new IssueTokenData(name: 'My Chrome', preset: TokenPreset::BrowserExtension),
    );

    expect($token)->toBeInstanceOf(NewAccessToken::class)
        ->and($token->accessToken->name)->toBe('My Chrome')
        ->and($token->accessToken->abilities)
        ->toBe(['bookmarks:create', 'categories:read'])
        ->and($token->plainTextToken)->toBeString();

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'My Chrome',
    ]);
});

it('issues a wildcard token for the full-access preset', function () {
    $user = User::factory()->create();

    $token = app(ApiTokenIssuer::class)->issue(
        $user,
        new IssueTokenData(name: 'Backup script', preset: TokenPreset::FullAccess),
    );

    expect($token->accessToken->abilities)->toBe(['*']);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=ApiTokenIssuerTest
```

- [ ] **Step 3: Implementa il service**

```php
<?php

namespace App\Services\Auth;

use App\Data\Auth\IssueTokenData;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class ApiTokenIssuer
{
    public function issue(User $user, IssueTokenData $data): NewAccessToken
    {
        return $user->createToken($data->name, $data->preset->abilities());
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=ApiTokenIssuerTest
```

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Auth/ApiTokenIssuer.php tests/Feature/Services/Auth/ApiTokenIssuerTest.php
git commit -m "feat(auth): add ApiTokenIssuer service"
```

---

## Task 6: `ApiTokenRevoker` service

**Files:**
- Create: `app/Services/Auth/ApiTokenRevoker.php`
- Test: `tests/Feature/Services/Auth/ApiTokenRevokerTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Models\User;
use App\Services\Auth\ApiTokenRevoker;

it('revokes a token by id for the owner', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Tmp', ['*'])->accessToken;

    app(ApiTokenRevoker::class)->revoke($user, $token->id);

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

it('does not revoke tokens belonging to other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreignToken = $other->createToken('Foreign', ['*'])->accessToken;

    app(ApiTokenRevoker::class)->revoke($user, $foreignToken->id);

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreignToken->id]);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=ApiTokenRevokerTest
```

- [ ] **Step 3: Implementa il service**

```php
<?php

namespace App\Services\Auth;

use App\Models\User;

class ApiTokenRevoker
{
    public function revoke(User $user, int $tokenId): void
    {
        $user->tokens()->whereKey($tokenId)->delete();
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=ApiTokenRevokerTest
```

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Auth/ApiTokenRevoker.php tests/Feature/Services/Auth/ApiTokenRevokerTest.php
git commit -m "feat(auth): add ApiTokenRevoker service"
```

---

## Task 7: `ApiAuthenticator` service

**Files:**
- Create: `app/Services/Auth/ApiAuthenticator.php`
- Test: `tests/Feature/Services/Auth/ApiAuthenticatorTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Data\Auth\LoginData;
use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Models\User;
use App\Services\Auth\ApiAuthenticator;
use Laravel\Sanctum\NewAccessToken;

it('issues a token when credentials are valid', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'secret-password-1',
    ]);

    $token = app(ApiAuthenticator::class)->login(new LoginData(
        email: 'jane@example.com',
        password: 'secret-password-1',
        deviceName: 'iPhone',
    ));

    expect($token)->toBeInstanceOf(NewAccessToken::class)
        ->and($token->accessToken->tokenable_id)->toBe($user->id)
        ->and($token->accessToken->name)->toBe('iPhone')
        ->and($token->accessToken->abilities)->toBe(['*']);
});

it('throws InvalidApiCredentialsException for wrong password', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'correct-password',
    ]);

    expect(fn () => app(ApiAuthenticator::class)->login(new LoginData(
        email: 'jane@example.com',
        password: 'wrong-password',
        deviceName: 'iPhone',
    )))->toThrow(InvalidApiCredentialsException::class);
});

it('throws InvalidApiCredentialsException for unknown email', function () {
    expect(fn () => app(ApiAuthenticator::class)->login(new LoginData(
        email: 'noone@example.com',
        password: 'whatever',
        deviceName: 'iPhone',
    )))->toThrow(InvalidApiCredentialsException::class);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=ApiAuthenticatorTest
```

- [ ] **Step 3: Implementa il service**

```php
<?php

namespace App\Services\Auth;

use App\Data\Auth\LoginData;
use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class ApiAuthenticator
{
    /**
     * @throws InvalidApiCredentialsException
     */
    public function login(LoginData $data): NewAccessToken
    {
        $user = User::where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw new InvalidApiCredentialsException;
        }

        return $user->createToken($data->deviceName, ['*']);
    }
}
```

- [ ] **Step 4: Verifica passaggio**

```bash
php artisan test --compact --filter=ApiAuthenticatorTest
```

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Auth/ApiAuthenticator.php tests/Feature/Services/Auth/ApiAuthenticatorTest.php
git commit -m "feat(auth): add ApiAuthenticator service"
```

---

## Task 8: `LoginRequest` Form Request API

**Files:**
- Create: `app/Http/Requests/Api/V1/Auth/LoginRequest.php`

- [ ] **Step 1: Crea il file**

```bash
php artisan make:request Api/V1/Auth/LoginRequest --no-interaction
```

```php
<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Data\Auth\LoginData;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }

    public function toData(): LoginData
    {
        return new LoginData(
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString(),
            deviceName: $this->string('device_name')->toString(),
        );
    }
}
```

- [ ] **Step 2: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/Api/V1/Auth/
git commit -m "feat(api): add LoginRequest"
```

---

## Task 9: `LoginController` API + rotta + throttle

**Files:**
- Create: `app/Http/Controllers/Api/V1/Auth/LoginController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V1/Auth/LoginTest.php`

- [ ] **Step 1: Crea il test**

```bash
mkdir -p tests/Feature/Api/V1/Auth
```

```php
<?php

use App\Models\User;

it('returns a token on valid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'good-password-1',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'jane@example.com',
        'password' => 'good-password-1',
        'device_name' => 'iPhone',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token']);

    expect($response->json('token'))->toBeString()->not->toBe('');
});

it('returns 401 on invalid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'good-password-1',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'jane@example.com',
        'password' => 'WRONG',
        'device_name' => 'iPhone',
    ]);

    $response->assertUnauthorized()
        ->assertJson(['message' => 'The provided credentials are incorrect.']);
});

it('returns 422 when fields are missing', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=LoginTest
```

- [ ] **Step 3: Crea il controller**

```php
<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\Auth\InvalidApiCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Services\Auth\ApiAuthenticator;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, ApiAuthenticator $authenticator): JsonResponse
    {
        try {
            $token = $authenticator->login($request->toData());
        } catch (InvalidApiCredentialsException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }

        return response()->json(['token' => $token->plainTextToken]);
    }
}
```

- [ ] **Step 4: Aggiorna `routes/api.php`**

Sostituisci interamente con:

```php
<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', LoginController::class)
        ->middleware('throttle:6,1')
        ->name('api.v1.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', function (Request $request) {
            return $request->user();
        })->name('api.v1.user');
    });
});
```

- [ ] **Step 5: Verifica passaggio**

```bash
php artisan test --compact --filter=LoginTest
```

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Api/V1/Auth/LoginController.php routes/api.php tests/Feature/Api/V1/Auth/LoginTest.php
git commit -m "feat(api): add v1 login endpoint with throttle"
```

---

## Task 10: `LogoutController` API + rotta

**Files:**
- Create: `app/Http/Controllers/Api/V1/Auth/LogoutController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Api/V1/Auth/LogoutTest.php`

- [ ] **Step 1: Crea il test**

```php
<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('revokes the current access token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $token = $user->createToken('Active', ['*']);

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/logout');

    $response->assertNoContent();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});

it('rejects unauthenticated requests', function () {
    $this->postJson('/api/v1/logout')->assertUnauthorized();
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=LogoutTest
```

- [ ] **Step 3: Crea il controller**

```php
<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->noContent();
    }
}
```

- [ ] **Step 4: Aggiungi la rotta in `routes/api.php`**

Dentro il gruppo `auth:sanctum`, aggiungi:

```php
Route::post('logout', LogoutController::class)->name('api.v1.logout');
```

E aggiungi l'import in cima:

```php
use App\Http\Controllers\Api\V1\Auth\LogoutController;
```

- [ ] **Step 5: Verifica passaggio**

```bash
php artisan test --compact --filter=LogoutTest
```

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Api/V1/Auth/LogoutController.php routes/api.php tests/Feature/Api/V1/Auth/LogoutTest.php
git commit -m "feat(api): add v1 logout endpoint"
```

---

## Task 11: `StoreApiTokenRequest` Form Request

**Files:**
- Create: `app/Http/Requests/Settings/StoreApiTokenRequest.php`

- [ ] **Step 1: Crea il file**

```bash
php artisan make:request Settings/StoreApiTokenRequest --no-interaction
```

```php
<?php

namespace App\Http\Requests\Settings;

use App\Data\Auth\IssueTokenData;
use App\Enums\TokenPreset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64'],
            'preset' => ['required', 'string', Rule::enum(TokenPreset::class)],
        ];
    }

    public function toData(): IssueTokenData
    {
        return new IssueTokenData(
            name: $this->string('name')->toString(),
            preset: TokenPreset::from($this->string('preset')->toString()),
        );
    }
}
```

- [ ] **Step 2: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/Settings/
git commit -m "feat(settings): add StoreApiTokenRequest"
```

---

## Task 12: `Settings/ApiTokenController` web

**Files:**
- Create: `app/Http/Controllers/Settings/ApiTokenController.php`
- Modify: `routes/settings.php`
- Test: `tests/Feature/Settings/ApiTokenManagementTest.php`

- [ ] **Step 1: Crea il test**

```bash
mkdir -p tests/Feature/Settings
```

```php
<?php

use App\Enums\TokenPreset;
use App\Models\User;

it('redirects guests to login', function () {
    $this->get(route('api-tokens.index'))->assertRedirect();
});

it('renders index with the user tokens list', function () {
    $user = User::factory()->create();
    $user->createToken('Existing', ['*']);

    $response = $this->actingAs($user)->get(route('api-tokens.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/api-tokens')
            ->has('tokens', 1)
            ->where('tokens.0.name', 'Existing')
        );
});

it('does not show tokens belonging to other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $other->createToken('Foreign', ['*']);

    $response = $this->actingAs($user)->get(route('api-tokens.index'));

    $response->assertInertia(fn ($page) => $page->has('tokens', 0));
});

it('issues a token via store and flashes the plain text token once', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('api-tokens.store'), [
        'name' => 'My Chrome',
        'preset' => TokenPreset::BrowserExtension->value,
    ]);

    $response->assertRedirect(route('api-tokens.index'));
    $response->assertSessionHas('inertia.flash_data.newToken');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'My Chrome',
    ]);
});

it('rejects unknown preset', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('api-tokens.store'), [
        'name' => 'X',
        'preset' => 'super-admin',
    ]);

    $response->assertSessionHasErrors('preset');
});

it('revokes a token owned by the user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Tmp', ['*'])->accessToken;

    $response = $this->actingAs($user)->delete(route('api-tokens.destroy', $token->id));

    $response->assertRedirect(route('api-tokens.index'));
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

it('does not revoke a token belonging to another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $foreign = $other->createToken('Foreign', ['*'])->accessToken;

    $this->actingAs($user)->delete(route('api-tokens.destroy', $foreign->id));

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreign->id]);
});
```

- [ ] **Step 2: Verifica fallimento**

```bash
php artisan test --compact --filter=ApiTokenManagementTest
```

- [ ] **Step 3: Crea il controller**

```php
<?php

namespace App\Http\Controllers\Settings;

use App\Enums\TokenPreset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreApiTokenRequest;
use App\Services\Auth\ApiTokenIssuer;
use App\Services\Auth\ApiTokenRevoker;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'created_at']);

        return Inertia::render('settings/api-tokens', [
            'tokens' => $tokens,
            'presets' => collect(TokenPreset::cases())->map(fn (TokenPreset $p) => [
                'value' => $p->value,
                'label' => $p->label(),
                'abilities' => $p->abilities(),
            ])->values(),
        ]);
    }

    public function store(StoreApiTokenRequest $request, ApiTokenIssuer $issuer)
    {
        $token = $issuer->issue($request->user(), $request->toData());

        Inertia::flash('newToken', [
            'name' => $token->accessToken->name,
            'plainTextToken' => $token->plainTextToken,
        ]);

        return redirect()->route('api-tokens.index');
    }

    public function destroy(Request $request, int $tokenId, ApiTokenRevoker $revoker)
    {
        $revoker->revoke($request->user(), $tokenId);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Token revoked.']);

        return redirect()->route('api-tokens.index');
    }
}
```

- [ ] **Step 4: Registra le rotte in `routes/settings.php`**

Aggiungi le rotte (dentro il gruppo middleware esistente per le settings):

```php
use App\Http\Controllers\Settings\ApiTokenController;

Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
Route::delete('settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])
    ->whereNumber('token')
    ->name('api-tokens.destroy');
```

> **Nota:** verifica la struttura attuale di `routes/settings.php` (gruppo middleware, prefix) e adatta se necessario. Le settings esistenti probabilmente sono già wrapped con `middleware(['auth', 'verified'])`.

- [ ] **Step 5: Verifica passaggio**

```bash
php artisan test --compact --filter=ApiTokenManagementTest
```

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Settings/ApiTokenController.php routes/settings.php tests/Feature/Settings/ApiTokenManagementTest.php
git commit -m "feat(settings): add API token management web routes"
```

---

## Task 13: Pagina React `settings/api-tokens.tsx`

**Files:**
- Create: `resources/js/pages/settings/api-tokens.tsx`

> **Nota:** la pagina riusa il layout settings esistente (controlla `resources/js/pages/settings/` per il pattern). Prima di scrivere il file, esamina una pagina settings esistente (es. `profile.tsx`) per copiarne struttura, layout, breadcrumb e componenti UI.

- [ ] **Step 1: Esamina struttura settings esistente**

```bash
ls resources/js/pages/settings/
```

E leggi una pagina esistente:

```bash
cat resources/js/pages/settings/profile.tsx | head -80
```

- [ ] **Step 2: Crea la pagina**

Adatta il template seguente alla struttura del progetto (componenti UI già presenti, layout settings):

```tsx
import { Head, useForm, usePage, Link, router } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';

type Token = {
    id: number;
    name: string;
    abilities: string[] | null;
    last_used_at: string | null;
    created_at: string;
};

type Preset = {
    value: string;
    label: string;
    abilities: string[];
};

type PageProps = {
    tokens: Token[];
    presets: Preset[];
    flash: { newToken?: { name: string; plainTextToken: string } };
};

export default function ApiTokens() {
    const { props } = usePage<PageProps>();
    const newToken = props.flash?.newToken;

    const form = useForm({ name: '', preset: props.presets[0]?.value ?? '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('api-tokens.store'), { onSuccess: () => form.reset('name') });
    };

    const revoke = (id: number) => {
        if (confirm('Revoke this token?')) {
            router.delete(route('api-tokens.destroy', id));
        }
    };

    return (
        <AppLayout>
            <Head title="API Tokens" />
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="API Tokens" description="Issue tokens for the browser extension or mobile app." />

                    {newToken && (
                        <div className="rounded border border-green-500 bg-green-50 p-4">
                            <p className="font-semibold">Token created: {newToken.name}</p>
                            <p className="mt-1 text-sm">Copy this token now — it will not be shown again.</p>
                            <code className="mt-2 block break-all bg-white p-2 text-xs">{newToken.plainTextToken}</code>
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Token name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="My Chrome on MacBook"
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div>
                            <Label htmlFor="preset">Preset</Label>
                            <select
                                id="preset"
                                className="w-full rounded border p-2"
                                value={form.data.preset}
                                onChange={(e) => form.setData('preset', e.target.value)}
                            >
                                {props.presets.map((p) => (
                                    <option key={p.value} value={p.value}>
                                        {p.label} ({p.abilities.join(', ')})
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.preset} />
                        </div>

                        <Button type="submit" disabled={form.processing}>
                            Create token
                        </Button>
                    </form>

                    <div>
                        <h3 className="mb-3 text-lg font-medium">Existing tokens</h3>
                        {props.tokens.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No tokens yet.</p>
                        ) : (
                            <ul className="divide-y rounded border">
                                {props.tokens.map((t) => (
                                    <li key={t.id} className="flex items-center justify-between p-3">
                                        <div>
                                            <p className="font-medium">{t.name}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {t.last_used_at ? `Last used ${t.last_used_at}` : 'Never used'}
                                                {' · '}
                                                Abilities: {t.abilities?.join(', ') ?? 'none'}
                                            </p>
                                        </div>
                                        <Button variant="destructive" onClick={() => revoke(t.id)}>
                                            Revoke
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
```

> **Nota:** se il progetto usa Wayfinder per le route, sostituisci `route('api-tokens.store')` con l'import generato (`@/routes/...`). Verifica `resources/js/actions/` o `resources/js/routes/` per il pattern in uso, ed esegui `php artisan wayfinder:generate` dopo aver registrato le rotte (Task 12).

- [ ] **Step 3: Genera tipi Wayfinder se in uso**

```bash
php artisan wayfinder:generate 2>&1 || echo "wayfinder:generate not available, skipping"
```

- [ ] **Step 4: Build/dev frontend e verifica manualmente**

```bash
npm run build
```

Atteso: build OK senza errori TypeScript.

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/settings/api-tokens.tsx
git diff --cached --stat
git commit -m "feat(settings): add API tokens management page"
```

---

## Task 14: Voce di menu nella sidebar settings

**Files:**
- Modify: `resources/js/layouts/settings/layout.tsx` (o il file equivalente; verifica)

> **Nota:** prima ispeziona la struttura attuale dei link delle settings e aggiungi il link "API Tokens" seguendo lo stesso pattern.

- [ ] **Step 1: Trova il file con la lista di link settings**

```bash
grep -r "profile.edit\|settings.profile" resources/js/layouts/ resources/js/pages/settings/ 2>/dev/null | head -20
```

- [ ] **Step 2: Aggiungi voce "API Tokens"**

Aggiungi un'entry nella lista che punta a `route('api-tokens.index')` con label "API Tokens".

- [ ] **Step 3: Verifica con build**

```bash
npm run build
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/layouts/settings/
git commit -m "feat(settings): add API tokens nav link"
```

---

## Task 15: Verifica finale Plan B

- [ ] **Step 1: Suite completa**

```bash
php artisan test --compact
```

Atteso: tutti i test PASS.

- [ ] **Step 2: Pint check**

```bash
vendor/bin/pint --format agent
```

- [ ] **Step 3: Aggiorna `todo.md` (4.1)**

Apri `todo.md` e flagga le tre voci di **4.1**:

```diff
- * **4.1 Autenticazione API (Laravel Sanctum)**
-     * [ ] Installare/Configurare Laravel Sanctum.
-     * [ ] Creare sistema per la generazione di Personal Access Token (PAT) dalla dashboard utente (es. "Crea token per Estensione").
-     * [ ] Creare endpoint di login via API per l'app mobile (`POST /api/login`).
+ * **4.1 Autenticazione API (Laravel Sanctum)**
+     * [x] Installare/Configurare Laravel Sanctum.
+     * [x] Creare sistema per la generazione di Personal Access Token (PAT) dalla dashboard utente (es. "Crea token per Estensione").
+     * [x] Creare endpoint di login via API per l'app mobile (`POST /api/login`).
```

> **Nota:** la nostra rotta è `POST /api/v1/login` (non `/api/login`); il nome simbolico in `todo.md` è equivalente.

- [ ] **Step 4: Commit finale**

```bash
git add -A
git commit -m "chore: mark todo 4.1 as completed"
```

---

## Note finali Plan B

Al termine del Plan B:
- `/api/v1/login`, `/api/v1/logout`, `/api/v1/user` sono operativi.
- Gli utenti possono creare e revocare PAT dalla dashboard settings con preset di abilities.
- I token applicano abilities granulari (sfruttate dal Plan C nei middleware `ability:...`).
- Il punto 4.1 del `todo.md` è marcato come completato.
- Il branch è `feat/service-layer-and-api`, pronto a proseguire con Plan C.