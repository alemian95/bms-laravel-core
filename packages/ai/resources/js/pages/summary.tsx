import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon, SparklesIcon } from 'lucide-react';

import bookmarks from '@/routes/bookmarks';

type SummaryBookmark = {
    id: number;
    title: string | null;
    url: string;
    domain: string | null;
};

export default function AiSummary({
    bookmark,
    summary,
}: {
    bookmark: SummaryBookmark;
    summary: string | null;
}) {
    return (
        <div className={`mx-auto flex w-full max-w-3xl flex-col gap-6 p-6`}>
            <Head title={`Summary`} />

            <Link
                href={bookmarks.index().url}
                className={`inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground`}
            >
                <ArrowLeftIcon className={`size-3`} /> Back
            </Link>

            <div>
                <h1 className={`text-xl leading-tight font-semibold`}>
                    {bookmark.title ?? bookmark.url}
                </h1>
                {bookmark.domain && (
                    <div className={`text-xs text-muted-foreground`}>
                        {bookmark.domain}
                    </div>
                )}
            </div>

            {summary ? (
                <p className={`text-sm whitespace-pre-line`}>{summary}</p>
            ) : (
                <div
                    className={`flex items-center gap-2 rounded-lg border border-dashed p-6 text-sm text-muted-foreground`}
                >
                    <SparklesIcon className={`size-4`} /> No summary yet.
                </div>
            )}
        </div>
    );
}
