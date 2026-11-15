<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Yalnızca geliştirme: php artisan db:seed --class=DemoSeeder (üretimde çalıştırma!) */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@example.test'], [
            'name' => 'Süper Admin', 'password' => 'degistir-beni', 'rol' => 'super_admin',
        ]);
        $t = Tenant::firstOrCreate(['gln' => '8680001000044'], [
            'unvan' => 'Demo Eczanesi', 'tur' => 'eczane', 'il' => 'Ankara',
            'lisans_baslangic' => now()->toDateString(), 'lisans_bitis' => now()->addDays(30)->toDateString(),
        ]);
        User::updateOrCreate(['email' => 'eczane@example.test'], [
            'name' => 'Demo Kullanıcı', 'password' => 'degistir-beni', 'rol' => 'tenant_admin', 'tenant_id' => $t->id,
        ]);
    }
}
