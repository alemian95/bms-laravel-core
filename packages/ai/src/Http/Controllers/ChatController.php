<?php

namespace BmsCore\Packages\Ai\Http\Controllers;

use App\Models\Bookmark;
use BmsCore\Packages\Ai\Agents\BookmarkChatAgent;
use BmsCore\Packages\Ai\Models\AiChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChatController
{
    /**
     * Storico della conversazione, caricato all'apertura del pannello.
     */
    public function index(Bookmark $bookmark): JsonResponse
    {
        Gate::authorize('view', $bookmark);

        return response()->json([
            'messages' => AiChatMessage::query()
                ->forBookmark($bookmark)
                ->oldest('id')
                ->get()
                ->map(fn (AiChatMessage $message): array => [
                    'id' => (string) $message->id,
                    'role' => $message->role->value,
                    'content' => $message->content,
                ]),
        ]);
    }

    /**
     * Nuova domanda: risponde in SSE e persiste il turno a stream concluso.
     *
     * ponytail: la sessione resta lockata per tutta la durata dello stream, quindi
     * altre richieste dello stesso utente aspettano. Con un driver di sessione
     * senza lock (o rotta fuori dal gruppo `web`, con auth via token) sparisce.
     */
    public function store(Request $request, Bookmark $bookmark): StreamableAgentResponse
    {
        Gate::authorize('update', $bookmark);

        $question = (string) $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ])['message'];

        $this->ensureAnswerable($bookmark);

        return BookmarkChatAgent::make(bookmark: $bookmark)
            ->stream(
                $question,
                provider: config('ai-chat.provider'),
                model: (string) config('ai-chat.model'),
                timeout: (int) config('ai-chat.timeout'),
            )
            ->then(fn (StreamedAgentResponse $response) => $this->persistTurn($bookmark, $question, (string) $response->text));
    }

    /**
     * Svuota la conversazione di un bookmark.
     */
    public function destroy(Bookmark $bookmark): JsonResponse
    {
        Gate::authorize('update', $bookmark);

        AiChatMessage::query()->forBookmark($bookmark)->delete();

        return response()->json(['messages' => []]);
    }

    /**
     * @throws HttpException se manca il contenuto da interrogare o la configurazione del plugin
     */
    private function ensureAnswerable(Bookmark $bookmark): void
    {
        abort_if(
            trim((string) $bookmark->content_text) === '',
            422,
            "L'articolo non ha ancora un contenuto da interrogare.",
        );

        abort_if(
            (string) config('ai-chat.model') === '',
            503,
            'La chat non è configurata: manca AI_CHAT_MODEL (o AI_SUMMARY_MODEL).',
        );
    }

    /**
     * Domanda e risposta si salvano insieme a stream concluso: prima del `then`
     * la domanda finirebbe nella history che l'agent rilegge, duplicandola nel
     * prompt. Il prezzo è che uno stream interrotto non lascia traccia.
     */
    private function persistTurn(Bookmark $bookmark, string $question, string $answer): void
    {
        if (trim($answer) === '') {
            return;
        }

        DB::transaction(function () use ($bookmark, $question, $answer): void {
            AiChatMessage::create([
                'bookmark_id' => $bookmark->id,
                'role' => MessageRole::User,
                'content' => $question,
            ]);

            AiChatMessage::create([
                'bookmark_id' => $bookmark->id,
                'role' => MessageRole::Assistant,
                'content' => $answer,
            ]);
        });
    }
}
