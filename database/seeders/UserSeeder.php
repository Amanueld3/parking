<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'super_admin']);
        $superadmin = User::where('email', 'superadmin@admin.com')->first();
        if (! $superadmin) {
            $superadmin = User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'superadmin@admin.com',
                'password' => bcrypt('password'),
            ]);
        }
        $superadmin->assignRole('super_admin');
    }
}
