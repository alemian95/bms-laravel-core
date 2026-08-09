import { Link, usePage } from '@inertiajs/react';
import { SparklesIcon } from 'lucide-react';

import { lazy, Suspense } from 'react';

import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/hooks/use-translation';
import ai from '@/routes/ai';
import type { Bookmark } from '@/types';

/**
 * Slot modules are glob-imported eagerly on every page, so the charting
 * dependency this widget pulls in must not ride along with them.
 */
const AiDashboardWidget = lazy(
    () => import('./components/ai-dashboard-widget'),
);

/** Stesso motivo: il pannello di chat porta con sé i componenti AI Elements. */
const BookmarkChat = lazy(() => import('./components/bookmark-chat'));

function AiDashboardWidgetSlot() {
    return (
        <Suspense fallback={<Skeleton className={`h-64 w-full rounded-xl`} />}>
            <AiDashboardWidget />
        </Suspense>
    );
}

function BookmarkChatSlot({ bookmark }: { bookmark: Bookmark }) {
    if (bookmark.status === 'pending') {
        return null;
    }

    return (
        <Suspense fallback={null}>
            <BookmarkChat bookmark={bookmark} />
        </Suspense>
    );
}

function SummaryButton({ bookmark }: { bookmark: Bookmark }) {
    const { t } = useTranslation();

    if (bookmark.status === 'pending') {
        return null;
    }

    return (
        <Link
            href={ai.summary(bookmark.id).url}
            className={`inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground`}
        >
            <SparklesIcon className={`size-3`} /> {t('Summary')}
        </Link>
    );
}

function TldrAccordion() {
    const summary = usePage().props.aiSummary as string | null | undefined;

    if (!summary) {
        return null;
    }

    return (
        <Accordion
            type={`single`}
            collapsible
            className={`mb-8 rounded-lg border px-4`}
        >
            <AccordionItem value={`tldr`}>
                <AccordionTrigger className={`text-sm font-medium`}>
                    <span className={`flex items-center gap-2`}>
                        <SparklesIcon className={`size-4`} /> TLDR
                    </span>
                </AccordionTrigger>
                <AccordionContent
                    className={`text-sm whitespace-pre-line text-muted-foreground`}
                >
                    {summary}
                </AccordionContent>
            </AccordionItem>
        </Accordion>
    );
}

export default {
    'bookmark-card-actions': SummaryButton,
    'bookmark-read-before-content': TldrAccordion,
    'bookmark-read-aside': BookmarkChatSlot,
    'dashboard-widgets': AiDashboardWidgetSlot,
};
