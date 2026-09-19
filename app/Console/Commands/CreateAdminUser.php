<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {email} {password} {name=Admin}';

    protected $description = 'Sisteme giriş yapabilecek bir admin kullanıcısı oluşturur veya günceller.';

    public function handle(): int
    {
        $validator = Validator::make($this->arguments(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $this->argument('email')],
            [
                'name' => $this->argument('name'),
                'password' => Hash::make($this->argument('password')),
                'role' => 'admin',
                'depot_id' => null,
                'email_verified_at' => now(),
            ],
        );

        $this->info("Admin kullanıcısı hazır: {$user->email}");

        return self::SUCCESS;
    }
}
