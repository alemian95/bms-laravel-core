import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { CategoryListItem } from '@/components/category-list-item';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import categories from '@/routes/categories';
import type { Category } from '@/types';

const createUrl = categories.store();

export default function Categories({
    categories: items,
}: {
    categories: Category[];
}) {
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [{ title: t('Categories'), href: categories.index() }],
    });

    return (
        <>
            <Head title={t('Categories')} />

            <div className={`mx-auto w-full max-w-3xl space-y-6`}>
                <Heading
                    title={t('Categories')}
                    description={t(
                        'Rename a category by clicking its name, or click the dot to change its color.',
                    )}
                />

                <Form
                    action={createUrl}
                    method={`post`}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className={`rounded-lg border p-4`}
                >
                    {({ processing, errors }) => (
                        <div className={`flex flex-wrap items-end gap-3`}>
                            <div className={`min-w-48 flex-1 space-y-2`}>
                                <Label htmlFor={`name`}>{t('Name')}</Label>
                                <Input
                                    id={`name`}
                                    type={`text`}
                                    name={`name`}
                                    required
                                    placeholder={t('e.g. Frontend')}
                                />
                            </div>
                            <div className={`space-y-2`}>
                                <Label htmlFor={`color`}>{t('Color')}</Label>
                                <Input
                                    id={`color`}
                                    className={`h-9 w-14 cursor-pointer p-1`}
                                    type={`color`}
                                    name={`color`}
                                    defaultValue={`#6366f1`}
                                />
                            </div>
                            <Button type={`submit`} disabled={processing}>
                                <PlusIcon className={`size-4`} />
                                {t('Create')}
                            </Button>
                            <div className={`w-full`}>
                                <InputError message={errors.name} />
                                <InputError message={errors.color} />
                            </div>
                        </div>
                    )}
                </Form>

                {items.length === 0 ? (
                    <div
                        className={`rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground`}
                    >
                        {t('No categories yet. Create your first one above.')}
                    </div>
                ) : (
                    <ul className={`divide-y rounded-lg border`}>
                        {items.map((category) => (
                            <CategoryListItem
                                category={category}
                                key={category.id}
                            />
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
