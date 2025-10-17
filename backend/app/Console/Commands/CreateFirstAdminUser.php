<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CreateFirstAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-first-admin-user {--email= : Email of the admin user} {--password= : Password of the admin user} {--mobilizon_email= : Email of the mobilizon user} {--mobilizon_password= : Password of the mobilizon user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the first admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // check if first admin user exists
        if (User::where('type', 'admin')->exists()) {
            $this->info('First admin user already exists.');
            return;
        }

        // create first admin user and add mobilizon data
        $user = new User();
        $user->email = $this->option('email');
        $user->password = bcrypt($this->option('password'));
        $user->type = 'admin';
        $user->mobilizon_email = $this->option('mobilizon_email');
        $user->mobilizon_password = $this->option('mobilizon_password');
        $user->email_verified_at = now();
        $user->is_active = true;
        $user->mobilizon_user_id = 1;
        $user->save();
        $this->info('First admin user created successfully.');
    }
}
