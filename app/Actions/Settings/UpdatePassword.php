<?php

namespace App\Actions\Settings;

use App\Data\Settings\UpdatePasswordData;

final class UpdatePassword
{
    /**
     * L'hashing è delegato al cast 'hashed' del modello User.
     */
    public function handle(UpdatePasswordData $input): void
    {
        $input->user->update(['password' => $input->password]);
    }
}
