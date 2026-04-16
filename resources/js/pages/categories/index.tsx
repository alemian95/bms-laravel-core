import { Form, Head } from '@inertiajs/react';
import { TextCursorInput, Trash2Icon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import categories from '@/routes/categories';
import type { Category } from '@/types';

const createUrl = categories.store()

export default function Categories({ categories, flash }: {
    categories: Category[],
    flash: {
        success?: string|null,
        error?: string|null,
    }
}) {

    return (
        <>
            <Head title="Categories" />

            {flash.success && (
                <div className={`text-green-500`}>{flash.success}</div>
            )}
            {flash.error && (
                <div className={`text-red-500`}>{flash.error}</div>
            )}

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
                    <li
                        className={`flex items-center justify-between rounded px-4 py-2`}
                        style={{
                            backgroundColor: category.color + '42',
                        }}
                        key={category.id}
                    >
                        <div>
                            <span className={`font-bold`}>{category.name}</span> <span className={`ml-2 text-sm`}>({category.slug})</span>
                        </div>
                        <div className={`flex items-center gap-2`}>
                            <div className={`mr-6`}>
                                <b>{category.bookmarks_count}</b> bookmarks
                            </div>
                            <button>
                                <TextCursorInput className={`size-5`} />
                            </button>
                            <button>
                                <Trash2Icon className={`size-5`} />
                            </button>
                        </div>
                    </li>
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
