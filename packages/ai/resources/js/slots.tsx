import { Link } from '@inertiajs/react';
import { SparklesIcon } from 'lucide-react';

import ai from '@/routes/ai';
import type { Bookmark } from '@/types';

function SummaryButton({ bookmark }: { bookmark: Bookmark }) {
    if (bookmark.status === 'pending') {
        return null;
    }

    return (
        <Link
            href={ai.summary(bookmark.id).url}
            className={`inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground`}
        >
            <SparklesIcon className={`size-3`} /> Summary
        </Link>
    );
}

export default {
    'bookmark-card-actions': SummaryButton,
};
