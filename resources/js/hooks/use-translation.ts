import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

type Replacements = Record<string, string | number>;

/**
 * Translate against the catalogue shared by `HandleInertiaRequests`.
 *
 * Keys are the English source strings, exactly like Laravel's JSON translation
 * files, so an untranslated key renders as itself. `:name` placeholders follow
 * the same convention as `__()`.
 *
 * ponytail: no plural rules — `trans_choice`-style `one|many` handled by :count
 * branches where needed. Add a real plural engine if a locale needs more forms.
 */
export function useTranslation() {
    const { translations, locale } = usePage().props;

    const t = useCallback(
        (key: string, replace: Replacements = {}) =>
            Object.entries(replace).reduce(
                (line, [token, value]) =>
                    line.replaceAll(`:${token}`, String(value)),
                translations[key] ?? key,
            ),
        [translations],
    );

    /** `locale` rides along for `Intl` formatting of dates and numbers. */
    return { t, locale };
}
