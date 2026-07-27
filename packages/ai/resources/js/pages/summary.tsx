import { Form, Head, Link, usePoll } from '@inertiajs/react';
import { ArrowLeftIcon, LoaderCircleIcon, SparklesIcon } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import ai from '@/routes/ai';
import bookmarks from '@/routes/bookmarks';

type SummaryBookmark = {
    id: number;
    title: string | null;
    url: string;
    domain: string | null;
};

/** Il job gira in coda: dopo l'avvio manuale si ricarica solo `summary`. */
const POLL_INTERVAL = 3000;

/**
 * ponytail: nessuno stato "in generazione" lato server, quindi si smette di
 * aspettare dopo due minuti invece di ciclare all'infinito su un job fallito.
 * Con una colonna `status` su ai_summaries il polling saprebbe quando fermarsi.
 */
const POLL_TIMEOUT = 120_000;

export default function AiSummary({
    bookmark,
    summary,
}: {
    bookmark: SummaryBookmark;
    summary: string | null;
}) {
    const [generating, setGenerating] = useState(false);

    const poll = usePoll(
        POLL_INTERVAL,
        () => ({
            only: ['summary'],
            onSuccess: (page) => {
                if ((page.props.summary as string | null) !== summary) {
                    stopWaiting();
                }
            },
        }),
        { autoStart: false },
    );

    function stopWaiting() {
        setGenerating(false);
        poll.stop();
    }

    const onGenerationStarted = () => {
        setGenerating(true);
        poll.start();
        setTimeout(stopWaiting, POLL_TIMEOUT);
    };

    return (
        <div className={`mx-auto flex w-full max-w-3xl flex-col gap-6 p-6`}>
            <Head title={`Summary`} />

            <Link
                href={bookmarks.index().url}
                className={`inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground`}
            >
                <ArrowLeftIcon className={`size-3`} /> Back
            </Link>

            <div className={`flex items-start justify-between gap-4`}>
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

                <Form
                    action={ai.generateSummary(bookmark.id).url}
                    method={`post`}
                    onSuccess={onGenerationStarted}
                >
                    {({ processing }) => (
                        <Button
                            type={`submit`}
                            variant={`outline`}
                            size={`sm`}
                            disabled={processing || generating}
                        >
                            {generating ? (
                                <LoaderCircleIcon
                                    className={`size-4 animate-spin`}
                                />
                            ) : (
                                <SparklesIcon className={`size-4`} />
                            )}
                            {summary ? 'Regenerate' : 'Generate'}
                        </Button>
                    )}
                </Form>
            </div>

            {summary ? (
                <p className={`text-sm whitespace-pre-line`}>{summary}</p>
            ) : (
                <div
                    className={`flex items-center gap-2 rounded-lg border border-dashed p-6 text-sm text-muted-foreground`}
                >
                    <SparklesIcon className={`size-4`} />
                    {generating
                        ? 'Generating the summary...'
                        : 'No summary yet.'}
                </div>
            )}
        </div>
    );
}
