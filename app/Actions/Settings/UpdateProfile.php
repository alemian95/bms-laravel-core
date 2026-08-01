<?php

namespace App\Actions\Settings;

use App\Data\Settings\UpdateProfileData;

final class UpdateProfile
{
    public function handle(UpdateProfileData $input): void
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
