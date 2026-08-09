<?php

use App\Models\User;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @return Finder<SplFileInfo>
 */
function frontendFiles(): Finder
{
    return Finder::create()
        ->files()
        ->in([base_path('resources/js'), base_path('packages')])
        ->exclude('node_modules')
        ->name('*.tsx');
}

/**
 * Every `t('…')` key found in the frontend, from the app and from every package.
 *
 * Exhaustive because `t()` is only ever called with a literal — the companion
 * test below enforces that.
 *
 * @return list<string>
 */
function frontendTranslationKeys(): array
{
    $files = frontendFiles();

    $keys = [];

    foreach ($files as $file) {
        preg_match_all(
            '/\bt\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/s',
            $file->getContents(),
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $key = $match[1] !== '' ? $match[1] : ($match[2] ?? '');

            if ($key !== '') {
                $keys[] = str_replace(["\\'", '\\"'], ["'", '"'], $key);
            }
        }
    }

    return array_values(array_unique($keys));
}

test('the italian catalogue covers every frontend translation key', function () {
    $catalogue = Lang::getLoader()->load('it', '*', '*');

    $missing = array_values(array_diff(frontendTranslationKeys(), array_keys($catalogue)));

    expect($missing)->toBe([], 'Chiavi senza traduzione italiana: '.implode(' | ', $missing));
});

test('t() is never called with a variable', function () {
    $offenders = [];

    foreach (frontendFiles() as $file) {
        // `t(foo)` / `t(foo.bar)` — anything that is not a quoted literal.
        preg_match_all('/\bt\(\s*[A-Za-z_$][\w.?$\[\]]*\s*[,)]/', $file->getContents(), $matches);

        foreach ($matches[0] as $match) {
            $offenders[] = $file->getRelativePathname().': '.$match;
        }
    }

    expect($offenders)->toBe([], implode(PHP_EOL, [
        'Passing a variable to t() sends user data through the catalogue and hides',
        'the key from extraction. Translate where the literal is in scope instead.',
        ...$offenders,
    ]));
});

test('the catalogue merges the app and the ai package', function () {
    $catalogue = Lang::getLoader()->load('it', '*', '*');

    expect($catalogue)
        ->toHaveKey('Settings')          // lang/it.json
        ->toHaveKey('AI summaries');     // packages/ai/resources/lang/it.json
});

test('the shared translations prop follows the resolved locale', function () {
    $user = User::factory()->create(['preferred_locale' => 'it']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'it')
            ->where('translations.Settings', 'Impostazioni')
        );
});

test('the shared translations prop is empty for the source locale', function () {
    $user = User::factory()->create(['preferred_locale' => 'en']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('translations', [])
        );
});
