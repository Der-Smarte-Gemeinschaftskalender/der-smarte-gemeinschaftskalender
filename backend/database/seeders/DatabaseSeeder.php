<?php

namespace Database\Seeders;

use App\Models\UploadedEvent;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CreatedEvent;
use App\Models\ImportedEvent;
use App\Models\MobilizonLogins;
use App\Models\MobilizonTag;
use DB;
use GuzzleHttp\Promise\Create;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // WICHTIG: Folgende Daten sollten beim verschicken von emails angepasst werden
        // In der .env müssen die korrekten Daten für die Mobilizon Zugänge hinterlegt werden
        // Create Admin
        User::factory()->create([
            'email' => 'dsg-admin@example.com',
            'password' => bcrypt('Sicher1234!'),
            'type' => 'admin',
            'is_active' => 1,
            'email_verified_at' => now(),
            'mobilizon_email' => env('APP_MOBILIZON_EMAIL'),
            'mobilizon_password' => env('APP_MOBILIZON_PASSWORD'),
            'mobilizon_user_id' => env('APP_MOBILIZON_ADMIN_USER_ID'),
            'mobilizon_name' => 'Instanz Admin',
            'mobilizon_preferred_username' => 'instanzadmin',
        ]);
        // Create User
        User::factory()->create([
            'email' => 'dsg-user@example.com',
            'password' => bcrypt('Sicher1234!'),
            'type' => 'user',
            'is_active' => 1,
            'email_verified_at' => now(),
            'mobilizon_email' => env('LOCAL_DEV_MOBILIZON_USER_EMAIL'),
            'mobilizon_password' => env('LOCAL_DEV_MOBILIZON_USER_PASSWORD'),
            'mobilizon_user_id' => env('LOCAL_DEV_MOBILIZON_USER_ID'),
            'mobilizon_name' => 'Instanz Admin',
            'mobilizon_preferred_username' => 'instanzadmin',
        ]);
    }
}
