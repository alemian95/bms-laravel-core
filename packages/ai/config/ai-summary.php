<?php

use Laravel\Ai\Enums\Lab;

return [

    /*
    |--------------------------------------------------------------------------
    | Generazione dei riassunti
    |--------------------------------------------------------------------------
    |
    | Fonte autorevole dei parametri usati per generare un riassunto: la
    | chiamata all'AI SDK (provider, modello, lunghezza, timeout) e la politica
    | di retry del listener che la esegue in coda.
    |
    | Il file non si chiama 'ai' per non collidere con il config dell'SDK.
    |
    */

    'provider' => env('AI_SUMMARY_PROVIDER', Lab::OpenAICompatible->value),

    'model' => env('AI_SUMMARY_MODEL'),

    'sentences' => (int) env('AI_SUMMARY_SENTENCES', 4),

    'timeout' => (int) env('AI_SUMMARY_TIMEOUT', 120),

    'tries' => (int) env('AI_SUMMARY_TRIES', 3),

    'backoff' => [30, 120, 300],

];
