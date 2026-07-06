<?php

use App\Actions\Settings\DeleteAccount;
use App\Models\User;

it('deletes the given user', function () {
    $user = User::factory()->create();

    app(DeleteAccount::class)->handle($user);

    $this->assertModelMissing($user);
});
