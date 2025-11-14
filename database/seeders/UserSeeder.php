<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Contoh user utama (username HARUS sama dengan username SAP)
        User::updateOrCreate(
            ['username' => 'basis'],     // username SAP
            [
                'name'      => 'SAP Test User',
                'email'     => 'test01@example.com',
                'password'  => Hash::make('123456'),   // Password Laravel
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        $this->command->info('-----------------------------------------');
        $this->command->info('  User Seeder Executed Successfully');
        $this->command->info('-----------------------------------------');
    }
}
