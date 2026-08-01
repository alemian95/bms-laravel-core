<?php

use Laravel\Ai\Enums\Lab;

return [

    /*
    |--------------------------------------------------------------------------
    | Chat con il singolo bookmark
    |--------------------------------------------------------------------------
    |
    | Parametri della conversazione con l'articolo. Il modello ricade su quello
    | dei riassunti: chi non vuole distinguerli configura solo AI_SUMMARY_MODEL.
    |
    */

    // `?:` e non il default di env(): una variabile dichiarata ma vuota vale
    // stringa vuota, non null, e il default non scatterebbe.
    'provider' => env('AI_CHAT_PROVIDER') ?: env('AI_SUMMARY_PROVIDER') ?: Lab::OpenAICompatible->value,

    'model' => env('AI_CHAT_MODEL') ?: env('AI_SUMMARY_MODEL'),

    'timeout' => (int) env('AI_CHAT_TIMEOUT', 300),

    /*
    | Caratteri di articolo iniettati nel prompt come contesto. Oltre questa
    | soglia il testo viene troncato: serve a non sfondare la context window di
    | un modello locale con un longform. Il taglio pulito arriva col RAG
    | (prototipo 6), che seleziona i chunk pertinenti invece di troncare.
    */
    'context_characters' => (int) env('AI_CHAT_CONTEXT_CHARACTERS', 24000),

    /*
    | Turni di storico riletti a ogni domanda.
    */
    'history_messages' => (int) env('AI_CHAT_HISTORY_MESSAGES', 20),

];
