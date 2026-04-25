import { Form, Head } from '@inertiajs/react';
import { CategoryListItem } from '@/components/category-list-item';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import categories from '@/routes/categories';
import type { Category } from '@/types';

const createUrl = categories.store();

export default function Categories({ categories }: { categories: Category[] }) {
    return (
        <>
            <Head title="Categories" />

            <Form action={createUrl} method={`post`} className={`flex gap-2`}>
                <Input
                    type={`text`}
                    name={`name`}
                    placeholder={`Create new category`}
                />
                <Input className={`w-12 p-0`} type={`color`} name={`color`} />
                <Button type={`submit`}>Create</Button>
            </Form>
            <ul className={`mt-4 flex flex-col gap-3`}>
                {categories.map((category) => (
                    <CategoryListItem category={category} key={category.id} />
                ))}
            </ul>
        </>
    );
}

Categories.layout = {
    breadcrumbs: [
        {
            title: 'Categories',
            href: categories.index(),
        },
    ],
};
