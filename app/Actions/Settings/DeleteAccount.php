<?php

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Models\User;

/**
 * @implements Action<User, void>
 */
final class DeleteAccount implements Action
{
    /**
     * @param  User  $input
     */
    public function handle(mixed $input): void
    {
        $input->delete();
    }
}
