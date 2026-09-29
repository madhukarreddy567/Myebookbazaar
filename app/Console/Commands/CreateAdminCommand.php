<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Fresh-install helper: creates the first Super Admin from ADMIN_EMAIL and
 * ADMIN_PASSWORD in .env. Refuses to run if any admin already exists.
 *
 * The password is hashed with bcrypt before storage and is never printed.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create the Super Admin from ADMIN_EMAIL / ADMIN_PASSWORD in .env (fresh installs).';

    public function handle(): int
    {
        if (Admin::query()->exists()) {
            $this->error('An admin already exists. Aborting.');

            return self::FAILURE;
        }

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (empty($email) || empty($password)) {
            $this->error('ADMIN_EMAIL and ADMIN_PASSWORD must be set in your .env file before running this command.');

            return self::FAILURE;
        }

        Admin::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->info("Super Admin created: {$email}");

        return self::SUCCESS;
    }
}
