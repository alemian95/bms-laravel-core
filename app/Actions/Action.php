<?php

namespace App\Actions;

/**
 * Contratto delle Action applicative: una classe = un'operazione, con un unico
 * entrypoint handle() che riceve un input tipizzato e restituisce un output.
 *
 * Il contratto è generico: ogni Action lega i propri tipi tramite
 * `@implements Action<TInput, TOutput>`, ottenendo type-safety statica su input
 * e output pur condividendo la stessa firma runtime.
 *
 * Il return type di handle() è volutamente omesso a livello di interfaccia:
 * PHP non consente di dichiararlo `mixed` e poi implementarlo come `void`, quindi
 * l'assenza del tipo permette alle Action di ritornare sia un valore sia `void`.
 *
 * @template TInput
 * @template TOutput
 */
interface Action
{
    /**
     * @param  TInput  $input
     * @return TOutput
     */
    public function handle(mixed $input);
}
