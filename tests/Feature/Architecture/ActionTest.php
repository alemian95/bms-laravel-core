<?php

arch('application actions expose a public handle entrypoint')
    ->expect('App\Actions')
    ->toHaveMethod('handle')
    ->ignoring('App\Actions\Fortify');

arch('application actions are final')
    ->expect('App\Actions')
    ->toBeFinal()
    ->ignoring('App\Actions\Fortify');
