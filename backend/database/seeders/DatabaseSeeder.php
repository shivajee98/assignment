<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_active' => true
        ]);

        $admin = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'parent_id' => $superAdmin->id,
            'is_active' => true
        ]);

        $manager = User::create([
            'name' => 'Manager One',
            'email' => 'manager1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'parent_id' => $admin->id,
            'is_active' => true
        ]);

        $incharge = User::create([
            'name' => 'Incharge One',
            'email' => 'incharge1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'incharge',
            'parent_id' => $manager->id,
            'is_active' => true
        ]);

        $teamLeader = User::create([
            'name' => 'Team Leader One',
            'email' => 'tl1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'team_leader',
            'parent_id' => $incharge->id,
            'is_active' => true
        ]);

        User::create([
            'name' => 'Employee One',
            'email' => 'employee1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'parent_id' => $teamLeader->id,
            'is_active' => true
        ]);

        User::create([
            'name' => 'Employee Two',
            'email' => 'employee2@example.com',
            'password' => Hash::make('password123'),
            'role' => 'employee',
            'parent_id' => $teamLeader->id,
            'is_active' => true
        ]);
    }
}
