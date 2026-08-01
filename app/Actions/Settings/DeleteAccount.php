<?php

namespace App\Actions\Settings;

use App\Models\User;

final class DeleteAccount
{
    public function handle(User $input): void
    {
        $input->delete();
    }
}
