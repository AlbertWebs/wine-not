<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Wine Not POS – default users.
 *
 * Default credentials (change in production):
 *   Admin:  username `admin`  PIN `1234`
 *   Cashier: username `cashier` PIN `5678`
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Wine Not Admin (full access)
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Wine Not Admin',
                'pin' => Hash::make('2624'),
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
                'pin' => Hash::make('5678'),
                'role' => 'cashier',
                'status' => 'active',
            ]
        );
        $cashier->assignRole('cashier');
    }
}
