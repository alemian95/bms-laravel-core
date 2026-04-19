import { useForm } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import bookmarks from '@/routes/bookmarks';
import type { Category } from '@/types';

export function NewBookmarkDialog({ categories }: { categories: Category[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<{
        url: string;
        category_id: string;
    }>({
        url: '',
        category_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(bookmarks.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button className={`cursor-pointer`}>
                    <PlusIcon className={`size-4`} /> New Bookmark
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Save a new bookmark</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className={`flex flex-col gap-4`}>
                    <div className={`flex flex-col gap-2`}>
                        <Label htmlFor={`url`}>URL</Label>
                        <Input
                            id={`url`}
                            type={`url`}
                            value={data.url}
                            onChange={(e) => setData('url', e.target.value)}
                            placeholder={`https://example.com/article`}
                            autoFocus
                            required
                        />
                        {errors.url && <p className={`text-sm text-destructive`}>{errors.url}</p>}
                    </div>

                    <div className={`flex flex-col gap-2`}>
                        <Label htmlFor={`category`}>Category (optional)</Label>
                        <Select
                            value={data.category_id || 'none'}
                            onValueChange={(value) => setData('category_id', value === 'none' ? '' : value)}
                        >
                            <SelectTrigger id={`category`} className={`w-full`}>
                                <SelectValue placeholder={`No category`} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={`none`}>No category</SelectItem>
                                {categories.map((category) => (
                                    <SelectItem key={category.id} value={String(category.id)}>
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.category_id && <p className={`text-sm text-destructive`}>{errors.category_id}</p>}
                    </div>

                    <DialogFooter>
                        <Button type={`submit`} disabled={processing} className={`cursor-pointer`}>
                            {processing ? 'Saving…' : 'Save bookmark'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
