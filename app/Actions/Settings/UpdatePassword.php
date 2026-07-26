<?php

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Data\Settings\UpdatePasswordData;

/**
 * @implements Action<UpdatePasswordData, void>
 */
final class UpdatePassword implements Action
{
    /**
     * L'hashing è delegato al cast 'hashed' del modello User.
     *
     * @param  UpdatePasswordData  $input
     */
    public function handle(mixed $input): void
    {
        $input->user->update(['password' => $input->password]);
    }
}
