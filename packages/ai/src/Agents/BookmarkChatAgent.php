<?php

namespace BmsCore\Packages\Ai\Agents;

use App\Models\Bookmark;
use BmsCore\Packages\Ai\Models\AiChatMessage;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

/**
 * Risponde a domande su un singolo bookmark, usando il testo dell'articolo
 * come unico contesto e i turni precedenti come storico.
 */
class BookmarkChatAgent implements Agent, Conversational
{
    use Promptable;

    public function __construct(public Bookmark $bookmark) {}

    public function instructions(): string
    {
        $context = Str::limit(
            trim((string) $this->bookmark->content_text),
            (int) config('ai-chat.context_characters'),
            preserveWords: true,
        );

        return <<<PROMPT
        Sei un assistente che aiuta l'utente a capire un articolo che ha salvato.

        Rispondi **solo** in base all'ARTICOLO qui sotto. Se la risposta non è
        contenuta nell'articolo, dillo esplicitamente invece di inventare.
        Rispondi nella lingua della domanda, in modo conciso.

        # ARTICOLO
        Titolo: {$this->bookmark->title}
        URL: {$this->bookmark->url}

        {$context}
        PROMPT;
    }

    /**
     * @return Message[]
     */
    public function messages(): iterable
    {
        return AiChatMessage::query()
            ->forBookmark($this->bookmark)
            ->latest('id')
            ->limit((int) config('ai-chat.history_messages'))
            ->get()
            ->reverse()
            ->map(fn (AiChatMessage $message): Message => $message->toAiMessage())
            ->all();
    }
}
