<?php

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Data\Settings\UpdateProfileData;

/**
 * @implements Action<UpdateProfileData, void>
 */
final class UpdateProfile implements Action
{
    /**
     * @param  UpdateProfileData  $input
     */
    public function handle(mixed $input): void
    {
        $user = $input->user;

        $user->fill([
            'name' => $input->name,
            'email' => $input->email,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
