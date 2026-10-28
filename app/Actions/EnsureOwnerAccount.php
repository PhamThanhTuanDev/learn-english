<?php

namespace App\Actions;

use App\Models\User;

class EnsureOwnerAccount
{
    /**
     * Create the owner account unless a user with this email already exists.
     *
     * An existing account is returned untouched, so this is safe to run on
     * every deploy without resetting the password.
     */
    public function handle(string $name, string $email, string $password): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->forceFill([
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
            ])->save();
        }

        return $user;
    }
}
