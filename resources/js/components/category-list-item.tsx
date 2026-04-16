import { router } from '@inertiajs/react';
import { TextCursorInputIcon, Trash2Icon } from 'lucide-react';
import categories from '@/routes/categories';
import type { Category } from '@/types';

export function CategoryListItem({ category }: { category: Category }) {
    return (
        <li
            className={`flex items-center justify-between rounded px-4 py-2`}
            style={{
                backgroundColor: category.color + '42',
            }}
            key={category.id}
        >
            <div>
                <span className={`font-bold`}>{category.name}</span>{' '}
                <span className={`ml-2 text-sm`}>({category.slug})</span>
            </div>
            <div className={`flex items-center gap-2`}>
                <div className={`mr-6`}>
                    <b>{category.bookmarks_count}</b> bookmarks
                </div>
                <button>
                    <TextCursorInputIcon className={`size-5`} />
                </button>
                <button onClick={() => {
                    if (confirm('Are you sure you want to delete this category?')) {
                        router.delete(categories.destroy(category.id))
                    }
                }}>
                    <Trash2Icon className={`size-5`} />
                </button>
            </div>
        </li>
    );
}
