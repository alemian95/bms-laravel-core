import { router } from '@inertiajs/react';
import { CheckIcon, PencilIcon, Trash2Icon, XIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useDebounce } from '@/hooks/use-debounce';
import { useTranslation } from '@/hooks/use-translation';
import categories from '@/routes/categories';
import type { Category } from '@/types';

export function CategoryListItem({ category }: { category: Category }) {
    const { t } = useTranslation();
    const [isEditingName, setIsEditingName] = useState(false);
    const [newName, setNewName] = useState(category.name);
    const [color, setColor] = useState(category.color ?? '#000000');
    const [prevCategoryColor, setPrevCategoryColor] = useState(category.color);
    const debouncedColor = useDebounce(color, 500);

    if (category.color !== prevCategoryColor) {
        setPrevCategoryColor(category.color);
        setColor(category.color ?? '#000000');
    }

    useEffect(() => {
        if (debouncedColor === (category.color ?? '#000000')) {
            return;
        }

        router.put(
            categories.update(category.id),
            { color: debouncedColor },
            { preserveScroll: true },
        );
    }, [debouncedColor, category.id, category.color]);

    const cancelEditing = () => {
        setIsEditingName(false);
        setNewName(category.name);
    };

    const updateName = () => {
        if (newName.trim() === '' || newName === category.name) {
            cancelEditing();

            return;
        }

        router.put(
            categories.update(category.id),
            { name: newName },
            {
                preserveScroll: true,
                onSuccess: () => setIsEditingName(false),
            },
        );
    };

    const count = category.bookmarks_count ?? 0;

    const deleteCategory = () => {
        const warning = count
            ? '\n' +
              (count === 1
                  ? t(':count bookmark will be left without a category.', {
                        count,
                    })
                  : t(':count bookmarks will be left without a category.', {
                        count,
                    }))
            : '';

        if (confirm(t('Delete ":name"?', { name: category.name }) + warning)) {
            router.delete(categories.destroy(category.id), {
                preserveScroll: true,
            });
        }
    };

    return (
        <li
            className={`group flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-muted/40`}
        >
            <label
                className={`relative shrink-0 cursor-pointer`}
                title={t('Change color')}
            >
                <span
                    className={`block size-5 rounded-full ring-1 ring-border ring-offset-2 ring-offset-background`}
                    style={{ backgroundColor: color }}
                />
                <input
                    type={`color`}
                    value={color}
                    onChange={(e) => setColor(e.target.value)}
                    aria-label={t('Color of :name', { name: category.name })}
                    className={`absolute inset-0 size-full cursor-pointer opacity-0`}
                />
            </label>

            <div className={`min-w-0 flex-1`}>
                {isEditingName ? (
                    <div className={`flex items-center gap-1`}>
                        <Input
                            className={`h-8 max-w-xs`}
                            value={newName}
                            onChange={(e) => setNewName(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    updateName();
                                }

                                if (e.key === 'Escape') {
                                    cancelEditing();
                                }
                            }}
                            autoFocus
                        />
                        <Button
                            variant={`ghost`}
                            size={`icon`}
                            className={`size-8 text-green-600`}
                            onClick={updateName}
                            aria-label={t('Save name')}
                        >
                            <CheckIcon className={`size-4`} />
                        </Button>
                        <Button
                            variant={`ghost`}
                            size={`icon`}
                            className={`size-8`}
                            onClick={cancelEditing}
                            aria-label={t('Cancel renaming')}
                        >
                            <XIcon className={`size-4`} />
                        </Button>
                    </div>
                ) : (
                    <button
                        type={`button`}
                        onClick={() => setIsEditingName(true)}
                        title={t('Rename')}
                        className={`flex max-w-full min-w-0 items-center gap-2 text-left`}
                    >
                        <span className={`truncate font-medium`}>
                            {category.name}
                        </span>
                        <PencilIcon
                            className={`size-3.5 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100`}
                        />
                    </button>
                )}
                <p
                    className={`truncate font-mono text-xs text-muted-foreground`}
                >
                    {category.slug}
                </p>
            </div>

            <Badge variant={`secondary`} className={`shrink-0 tabular-nums`}>
                {count === 1
                    ? t(':count bookmark', { count })
                    : t(':count bookmarks', { count })}
            </Badge>
            <Button
                variant={`ghost`}
                size={`icon`}
                className={`size-8 text-muted-foreground hover:text-destructive`}
                onClick={deleteCategory}
                aria-label={t('Delete :name', { name: category.name })}
            >
                <Trash2Icon className={`size-4`} />
            </Button>
        </li>
    );
}
