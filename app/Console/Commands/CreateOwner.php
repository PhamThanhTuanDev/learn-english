<?php

namespace App\Console\Commands;

use App\Actions\EnsureOwnerAccount;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-owner
    {--name= : Display name}
    {--email= : Login email}
    {--password= : Password (omit it to be prompted without echo)}')]
#[Description('Create the owner account (public registration is disabled)')]
class CreateOwner extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(EnsureOwnerAccount $ensureOwner): int
    {
        $data = [
            'name' => $this->stringOption('name') ?? text('Tên hiển thị', required: true),
            'email' => $this->stringOption('email') ?? text('Email đăng nhập', required: true),
            'password' => $this->stringOption('password') ?? password('Mật khẩu', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = $ensureOwner->handle($data['name'], $data['email'], $data['password']);

        $this->info("Đã tạo tài khoản chủ: {$user->email}");

        return self::SUCCESS;
    }

    private function stringOption(string $key): ?string
    {
        $value = $this->option($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
