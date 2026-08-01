import { useCallback, useEffect, useRef, useState } from 'react';

import ai from '@/routes/ai';

export type ChatRole = 'user' | 'assistant';

export interface ChatMessage {
    id: string;
    role: ChatRole;
    content: string;
}

/** Allineato a `ChatStatus` dell'AI SDK, che `PromptInputSubmit` sa disegnare. */
export type ChatStatus = 'ready' | 'submitted' | 'streaming' | 'error';

/** Laravel accetta il token del cookie sull'header `X-XSRF-TOKEN`. */
function xsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((entry) => entry.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}

/**
 * Estrae i `data:` completi dal buffer SSE, lasciandoci dentro l'ultimo evento
 * troncato a metà chunk.
 */
function drainSseBuffer(buffer: string): { events: string[]; rest: string } {
    const parts = buffer.split('\n\n');
    const rest = parts.pop() ?? '';

    return {
        events: parts
            .map((part) => part.trim())
            .filter((part) => part.startsWith('data: '))
            .map((part) => part.slice('data: '.length)),
        rest,
    };
}

/**
 * Conversazione con un singolo bookmark: storico persistito lato server e
 * risposta letta in streaming dall'endpoint SSE del plugin.
 */
export function useBookmarkChat(bookmarkId: number) {
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [status, setStatus] = useState<ChatStatus>('ready');
    const [error, setError] = useState<string | null>(null);

    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        const controller = new AbortController();

        fetch(ai.chat.index(bookmarkId).url, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then((response) =>
                response.ok ? response.json() : { messages: [] },
            )
            .then((payload: { messages: ChatMessage[] }) =>
                setMessages(payload.messages),
            )
            .catch(() => undefined);

        return () => controller.abort();
    }, [bookmarkId]);

    const stop = useCallback(() => {
        abortRef.current?.abort();
        abortRef.current = null;
        setStatus('ready');
    }, []);

    const send = useCallback(
        async (text: string) => {
            const question = text.trim();

            if (question === '' || abortRef.current) {
                return;
            }

            const turnId = `${Date.now()}`;
            const answerId = `${turnId}-answer`;

            setError(null);
            setStatus('submitted');
            setMessages((current) => [
                ...current,
                { id: turnId, role: 'user', content: question },
                { id: answerId, role: 'assistant', content: '' },
            ]);

            const controller = new AbortController();
            abortRef.current = controller;

            try {
                const response = await fetch(ai.chat.store(bookmarkId).url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'text/event-stream',
                        'X-XSRF-TOKEN': xsrfToken(),
                    },
                    body: JSON.stringify({ message: question }),
                    signal: controller.signal,
                });

                if (!response.ok || !response.body) {
                    const payload = await response.json().catch(() => null);

                    throw new Error(
                        payload?.message ??
                            'La chat non ha risposto. Riprova tra poco.',
                    );
                }

                setStatus('streaming');

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                for (;;) {
                    const { done, value } = await reader.read();

                    if (done) {
                        break;
                    }

                    buffer += decoder.decode(value, { stream: true });

                    const { events, rest } = drainSseBuffer(buffer);
                    buffer = rest;

                    for (const event of events) {
                        if (event === '[DONE]') {
                            continue;
                        }

                        const delta = parseTextDelta(event);

                        if (delta !== '') {
                            setMessages((current) =>
                                current.map((message) =>
                                    message.id === answerId
                                        ? {
                                              ...message,
                                              content: message.content + delta,
                                          }
                                        : message,
                                ),
                            );
                        }
                    }
                }

                setStatus('ready');
            } catch (exception) {
                if ((exception as Error).name === 'AbortError') {
                    return;
                }

                setError((exception as Error).message);
                setStatus('error');
                setMessages((current) =>
                    current.filter((message) => message.id !== answerId),
                );
            } finally {
                abortRef.current = null;
            }
        },
        [bookmarkId],
    );

    const clear = useCallback(async () => {
        stop();

        await fetch(ai.chat.destroy(bookmarkId).url, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
        });

        setMessages([]);
        setError(null);
    }, [bookmarkId, stop]);

    return { messages, status, error, send, stop, clear };
}

/** Gli eventi diversi da `text_delta` (start, end, usage) non servono alla UI. */
function parseTextDelta(payload: string): string {
    try {
        const event = JSON.parse(payload) as { type?: string; delta?: string };

        return event.type === 'text_delta' ? (event.delta ?? '') : '';
    } catch {
        return '';
    }
}
