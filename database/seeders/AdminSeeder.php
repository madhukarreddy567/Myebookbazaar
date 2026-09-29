<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (empty($email) || empty($password)) {
            $this->command->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set — skipping admin seeder.');
            return;
        }

        // Upsert: create the Super Admin, or refresh the password hash if it exists.
        // The 'hashed' cast on the model hashes the plain password automatically.
        $admin = Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'role' => 'super_admin',
            ]
        );

        // Remove any older default admin accounts.
        $removed = Admin::where('id', '!=', $admin->id)->delete();

        $this->command->info("Super Admin {$email} ready. Older accounts removed: {$removed}.");
        $this->command->warn('IMPORTANT: change this password after first login (Reddy > Change Password) and enable 2FA when available.');
    }
}
