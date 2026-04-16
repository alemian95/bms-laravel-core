import { router } from '@inertiajs/react';
import { CheckIcon, PaintBucketIcon, TextCursorInputIcon, Trash2Icon, XIcon } from 'lucide-react';
import { useState } from 'react';

import { Input } from '@/components/ui/input';
import categories from '@/routes/categories';
import type { Category } from '@/types';

export function CategoryListItem({ category }: { category: Category }) {
    const [isEditingName, setIsEditingName] = useState(false);
    const [newName, setNewName] = useState(category.name);

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

    const updateColor = (color: string) => {
        router.put(categories.update(category.id), { color });
    };

    return (
        <li
            className={`flex items-center justify-between rounded px-4 py-2`}
            style={{
                backgroundColor: category.color + '42',
            }}
            key={category.id}
        >
            <div className={`flex-1 flex items-center gap-2`}>
                {isEditingName ? (
                    <div className={`flex items-center gap-2 flex-1`}>
                        <Input
                            className={`h-8 py-0`}
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
                        <button onClick={() => {
                            setIsEditingName(false);
                            setNewName(category.name);
                        }}>
                            <XIcon className={`size-4 text-red-600`} />
                        </button>
                    </div>
                ) : (
                    <>
                        <span className={`font-bold`}>{category.name}</span>{' '}
                        <span className={`ml-2 text-sm text-gray-600`}>({category.slug})</span>
                    </>
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
                    <PaintBucketIcon className={`size-5 pointer-events-none absolute left-0`} />
                    <input
                        type="color"
                        className={`size-5 opacity-0 cursor-pointer`}
                        value={category.color ?? '#000000'}
                        onChange={(e) => updateColor(e.target.value)}
                    />
                </div>
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
