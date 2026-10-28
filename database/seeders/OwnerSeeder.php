<?php

namespace Database\Seeders;

use App\Actions\EnsureOwnerAccount;
use Illuminate\Database\Seeder;

class OwnerSeeder extends Seeder
{
    /**
     * Create the owner account from the OWNER_* env values, when they are set.
     *
     * Used on hosting without shell access. Safe to run on every deploy.
     */
    public function run(EnsureOwnerAccount $ensureOwner): void
    {
        $email = config()->string('app.owner.email');
        $password = config()->string('app.owner.password');

        if ($email === '' || $password === '') {
            $this->command?->warn('OWNER_EMAIL/OWNER_PASSWORD chưa được đặt, bỏ qua tạo tài khoản chủ.');

            return;
        }

        $ensureOwner->handle(config()->string('app.owner.name') ?: 'Owner', $email, $password);
    }
}
