import { Link } from '@inertiajs/react';
import { SparklesIcon } from 'lucide-react';

import { lazy, Suspense } from 'react';

import { Skeleton } from '@/components/ui/skeleton';
import ai from '@/routes/ai';
import type { Bookmark } from '@/types';

/**
 * Slot modules are glob-imported eagerly on every page, so the charting
 * dependency this widget pulls in must not ride along with them.
 */
const AiDashboardWidget = lazy(
    () => import('./components/ai-dashboard-widget'),
);

function AiDashboardWidgetSlot() {
    return (
        <Suspense fallback={<Skeleton className={`h-64 w-full rounded-xl`} />}>
            <AiDashboardWidget />
        </Suspense>
    );
}

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
    'dashboard-widgets': AiDashboardWidgetSlot,
};
