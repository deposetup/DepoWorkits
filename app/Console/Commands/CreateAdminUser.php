<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    // "name" değişken sayıda kelime alır (ör. "Halil Güler"); bazı arayüzler
    // (ör. Plesk'in Artisan kutusu) tırnaklı argümanları doğru ayrıştırmıyor,
    // bu yüzden isim tırnaksız, boşlukla ayrılmış kelimeler olarak da girilebilir.
    protected $signature = 'admin:create {email} {password} {name?*}';

    protected $description = 'Sisteme giriş yapabilecek bir admin kullanıcısı oluşturur veya günceller.';

    public function handle(): int
    {
        $name = implode(' ', $this->argument('name')) ?: 'Admin';

        $validator = Validator::make([
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
            'name' => $name,
        ], [
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
                'name' => $name,
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
