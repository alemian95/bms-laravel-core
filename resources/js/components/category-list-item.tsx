import { router } from '@inertiajs/react';
import { CheckIcon, PaintBucketIcon, TextCursorInputIcon, Trash2Icon, XIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Input } from '@/components/ui/input';
import categories from '@/routes/categories';
import type { Category } from '@/types';

export function CategoryListItem({ category }: { category: Category }) {
    const [isEditingName, setIsEditingName] = useState(false);
    const [newName, setNewName] = useState(category.name);
    const [color, setColor] = useState(category.color ?? '#000000');
    const [prevCategoryColor, setPrevCategoryColor] = useState(category.color);

    if (category.color !== prevCategoryColor) {
        setPrevCategoryColor(category.color);
        setColor(category.color ?? '#000000');
    }

    useEffect(() => {
        if (color === (category.color ?? '#000000')) {
            return;
        }

        const timeout = setTimeout(() => {
            router.put(categories.update(category.id), { color }, {
                preserveScroll: true,
            });
        }, 500);

        return () => clearTimeout(timeout);
    }, [color, category.id, category.color]);

    const updateName = () => {
        if (newName.trim() === '' || newName === category.name) {
            setIsEditingName(false);
            setNewName(category.name);

            return;
        }

        router.put(categories.update(category.id), { name: newName }, {
            onSuccess: () => setIsEditingName(false),
        });
    };

    return (
        <li
            className={`flex items-center justify-between rounded border px-4 py-2 shadow-sm`}
            key={category.id}
        >
            <div className={`flex flex-1 items-center gap-2`}>
                {isEditingName ? (
                    <div className={`flex flex-1 items-center gap-2`}>
                        <Input
                            className={`w-fit py-0`}
                            value={newName}
                            onChange={(e) => setNewName(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    updateName();
                                }

                                if (e.key === 'Escape') {
                                    setIsEditingName(false);
                                    setNewName(category.name);
                                }
                            }}
                            autoFocus
                        />
                        <button onClick={updateName}>
                            <CheckIcon className={`size-4 text-green-600`} />
                        </button>
                        <button
                            onClick={() => {
                                setIsEditingName(false);
                                setNewName(category.name);
                            }}
                        >
                            <XIcon className={`size-4 text-destructive`} />
                        </button>
                    </div>
                ) : (
                    <div
                        className={`grid grid-cols-[32px_1fr_1fr] grid-rows-2`}
                    >
                        <div
                            className={`row-span-2 flex items-center justify-start`}
                        >
                            <div
                                className={`size-4 rounded-full`}
                                style={{ backgroundColor: color }}
                            />
                        </div>
                        <span className={`col-span-2 font-bold`}>
                            {category.name}
                        </span>{' '}
                        <span className={`text-sm text-gray-600`}>
                            code: {category.slug}
                        </span>
                    </div>
                )}
            </div>
            <div className={`flex items-center gap-2`}>
                <div className={`mr-6`}>
                    <b>{category.bookmarks_count}</b> bookmarks
                </div>
                {!isEditingName && (
                    <button onClick={() => setIsEditingName(true)}>
                        <TextCursorInputIcon className={`size-5`} />
                    </button>
                )}
                <div className={`relative flex items-center`}>
                    <PaintBucketIcon
                        className={`pointer-events-none absolute left-0 size-5`}
                    />
                    <input
                        type="color"
                        className={`size-5 cursor-pointer opacity-0`}
                        value={color}
                        onChange={(e) => setColor(e.target.value)}
                    />
                </div>
                <button
                    onClick={() => {
                        if (
                            confirm(
                                'Are you sure you want to delete this category?',
                            )
                        ) {
                            router.delete(categories.destroy(category.id));
                        }
                    }}
                >
                    <Trash2Icon className={`size-5 text-destructive`} />
                </button>
            </div>
        </li>
    );
}
