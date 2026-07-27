import { SparklesIcon } from 'lucide-react';

import type { Bookmark } from '@/types';

function SummaryButton({ bookmark }: { bookmark: Bookmark }) {
    if (bookmark.status === 'pending') {
        return null;
    }

    // ponytail: nessuna azione finché non esiste la pagina/dialog del summary
    return (
        <button
            type={`button`}
            className={`inline-flex cursor-pointer items-center gap-1 text-xs text-muted-foreground hover:text-foreground`}
        >
            <SparklesIcon className={`size-3`} /> Summary
        </button>
    );
}

export default {
    'bookmark-card-actions': SummaryButton,
};
