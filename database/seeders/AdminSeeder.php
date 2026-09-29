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

        if (Admin::where('email', $email)->exists()) {
            $this->command->info("Admin {$email} already exists — skipping.");
            return;
        }

        Admin::create([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $password, // hashed via model cast
        ]);

        $this->command->info("Admin {$email} created. IMPORTANT: change this password after first login (Reddy > Change Password).");
    }
}
