import { Head, setLayoutProps } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import LanguageSelect from '@/components/language-select';
import { useTranslation } from '@/hooks/use-translation';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            { title: t('Appearance settings'), href: editAppearance() },
        ],
    });

    return (
        <>
            <Head title={t('Appearance settings')} />

            <h1 className="sr-only">{t('Appearance settings')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('Appearance settings')}
                    description={t("Update your account's appearance settings")}
                />
                <AppearanceTabs />

                <Heading
                    variant="small"
                    title={t('Language')}
                    description={t(
                        'Choose the language used across the interface',
                    )}
                />
                <LanguageSelect />
            </div>
        </>
    );
}
