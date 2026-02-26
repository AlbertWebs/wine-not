<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Wine Not POS – default users.
 *
 * Default credentials (change in production):
 *   Admin:  username `admin`  PIN `2624`
 *   Cashier: username `cashier` PIN `5678`
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Wine Not Admin (full access)
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Wine Not Admin',
                'pin' => '2624', // Model Hashed cast hashes it
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );
        $admin->assignRole('super_admin');

        // Wine Not Cashier (till, sales, customers, inventory view/edit)
        $cashier = User::firstOrCreate(
            ['username' => 'cashier'],
            [
                'name' => 'Wine Not Cashier',
                'pin' => '5678', // Model Hashed cast hashes it
                'role' => 'cashier',
                'status' => 'active',
            ]
        );
        $cashier->assignRole('cashier');
    }
}
