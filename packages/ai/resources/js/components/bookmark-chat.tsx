import { MessageSquareIcon, SparklesIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';

import {
    Conversation,
    ConversationContent,
    ConversationEmptyState,
    ConversationScrollButton,
} from '@/components/ai-elements/conversation';
import {
    PromptInput,
    PromptInputBody,
    PromptInputFooter,
    PromptInputSubmit,
    PromptInputTextarea,
    PromptInputTools,
} from '@/components/ai-elements/prompt-input';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import type { Bookmark } from '@/types';

import { useBookmarkChat } from '../hooks/use-bookmark-chat';
import type { ChatMessage } from '../hooks/use-bookmark-chat';

function ChatBubble({ message }: { message: ChatMessage }) {
    const isUser = message.role === 'user';

    return (
        <div
            className={cn(
                'flex w-full max-w-[90%] flex-col gap-1',
                isUser ? 'ml-auto items-end' : 'items-start',
            )}
        >
            <div
                className={cn(
                    'rounded-lg px-3 py-2 text-sm whitespace-pre-wrap',
                    isUser
                        ? 'bg-secondary text-secondary-foreground'
                        : 'text-foreground',
                )}
            >
                {message.content === '' ? (
                    <span
                        className={`inline-flex gap-1 text-muted-foreground`}
                        aria-label={`L'assistente sta rispondendo`}
                    >
                        <span
                            className={`size-1.5 animate-pulse rounded-full bg-current`}
                        />
                        <span
                            className={`size-1.5 animate-pulse rounded-full bg-current [animation-delay:150ms]`}
                        />
                        <span
                            className={`size-1.5 animate-pulse rounded-full bg-current [animation-delay:300ms]`}
                        />
                    </span>
                ) : (
                    message.content
                )}
            </div>
        </div>
    );
}

/**
 * Pannello di conversazione sul bookmark aperto nel Reader. Monta la chat solo
 * quando lo sheet si apre: finché resta chiuso non parte nessuna richiesta.
 */
export default function BookmarkChat({ bookmark }: { bookmark: Bookmark }) {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <Sheet open={isOpen} onOpenChange={setIsOpen}>
            <SheetTrigger asChild>
                <Button
                    variant={`outline`}
                    size={`sm`}
                    className={`fixed right-6 bottom-6 z-40 shadow-lg`}
                >
                    <MessageSquareIcon className={`size-4`} /> Chiedi
                    all'articolo
                </Button>
            </SheetTrigger>

            <SheetContent
                side={`right`}
                className={`w-full gap-0 sm:max-w-md`}
                onOpenAutoFocus={(event) => event.preventDefault()}
            >
                {isOpen && <ChatPanel bookmark={bookmark} />}
            </SheetContent>
        </Sheet>
    );
}

function ChatPanel({ bookmark }: { bookmark: Bookmark }) {
    const { messages, status, error, send, stop, clear } = useBookmarkChat(
        bookmark.id,
    );

    // Textarea controllato: l'invio non dipende dal FormData interno del
    // componente, che legge il campo per `name` e si rompe in silenzio.
    const [draft, setDraft] = useState('');

    const handleSubmit = () => {
        if (draft.trim() === '') {
            return;
        }

        setDraft('');
        void send(draft);
    };

    return (
        <>
            <SheetHeader className={`gap-1 border-b`}>
                <SheetTitle
                    className={`flex items-center gap-2 pr-8 text-base`}
                >
                    <SparklesIcon className={`size-4 shrink-0`} />
                    <span className={`truncate`}>
                        {bookmark.title ?? 'Chat'}
                    </span>
                </SheetTitle>
                <SheetDescription
                    className={`flex items-center justify-between gap-2`}
                >
                    <span>
                        Le risposte usano solo il testo di questo articolo.
                    </span>
                    {messages.length > 0 && (
                        <Button
                            variant={`ghost`}
                            size={`icon`}
                            onClick={() => void clear()}
                            aria-label={`Svuota la conversazione`}
                        >
                            <Trash2Icon className={`size-4`} />
                        </Button>
                    )}
                </SheetDescription>
            </SheetHeader>

            <Conversation className={`min-h-0`}>
                <ConversationContent className={`gap-4`}>
                    {messages.length === 0 ? (
                        <ConversationEmptyState
                            icon={<MessageSquareIcon className={`size-6`} />}
                            title={`Nessuna domanda ancora`}
                            description={`Chiedi un chiarimento, un confronto o un dettaglio dell'articolo.`}
                        />
                    ) : (
                        messages.map((message) => (
                            <ChatBubble key={message.id} message={message} />
                        ))
                    )}
                </ConversationContent>
                <ConversationScrollButton />
            </Conversation>

            {error !== null && (
                <p
                    role={`alert`}
                    className={`px-4 pb-2 text-sm text-destructive`}
                >
                    {error}
                </p>
            )}

            <div className={`border-t p-4`}>
                <PromptInput onSubmit={handleSubmit}>
                    <PromptInputBody>
                        <PromptInputTextarea
                            placeholder={`Fai una domanda sull'articolo…`}
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                        />
                    </PromptInputBody>
                    {/* Figlio diretto di PromptInput: è l'addon `block-end` che
                        fa passare InputGroup a colonna e altezza automatica. */}
                    <PromptInputFooter>
                        <PromptInputTools />
                        <PromptInputSubmit
                            status={status}
                            onStop={stop}
                            disabled={draft.trim() === '' && status === 'ready'}
                        />
                    </PromptInputFooter>
                </PromptInput>
            </div>
        </>
    );
}
