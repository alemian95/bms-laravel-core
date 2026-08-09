import { router, usePage } from '@inertiajs/react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { update as updateLocale } from '@/routes/locale';

// ponytail: hardcoded label map — move to a lang file when locales outgrow a handful.
const LABELS: Record<string, string> = {
    en: 'English',
    it: 'Italiano',
};

export default function LanguageSelect() {
    const { locale, supportedLocales } = usePage().props;

    return (
        <Select
            value={locale}
            onValueChange={(value) =>
                router.patch(
                    updateLocale().url,
                    { locale: value },
                    { preserveScroll: true },
                )
            }
        >
            <SelectTrigger className="w-48">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {supportedLocales.map((value) => (
                    <SelectItem key={value} value={value}>
                        {LABELS[value] ?? value}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
