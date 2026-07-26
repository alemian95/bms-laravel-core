<?php

use App\Actions\Action;

arch('application actions implement the Action contract')
    ->expect('App\Actions')
    ->toImplement(Action::class)
    ->ignoring([Action::class, 'App\Actions\Fortify']);

arch('application actions expose a public handle entrypoint')
    ->expect('App\Actions')
    ->toHaveMethod('handle')
    ->ignoring([Action::class, 'App\Actions\Fortify']);
