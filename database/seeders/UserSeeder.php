<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'a@pos.com',
                'password' => bcrypt('1'),
                'role' => User::ROLE_ADMIN,
            ],
            // [
            //     'name' => 'Manager',
            //     'email' => 'm@pos.com',
            //     'password' => bcrypt('password'),
            //     'role' => User::ROLE_MANAGER,
            // ],
            // [
            //     'name' => 'Cashier',
            //     'email' => 'c@pos.com',
            //     'password' => bcrypt('password'),
            //     'role' => User::ROLE_CASHIER,
            // ],
            // [
            //     'name' => 'Front Door',
            //     'email' => 'f@pos.com',
            //     'password' => bcrypt('password'),
            //     'role' => User::ROLE_FRONTDOOR,
            // ],
            // [
            //     'name' => 'Purchaser',
            //     'email' => 'p@pos.com',
            //     'password' => bcrypt('password'),
            //     'role' => User::ROLE_PURCHASER,
            // ],
            // [
            //     'name' => 'Kitchen',
            //     'email' => 'k@pos.com',
            //     'password' => bcrypt('password'),
            //     'role' => User::ROLE_KITCHEN,
            // ],
            // [
            //     'name' => 'Finance',
            //     'email' => 'fi@pos.com',
            //     'password' => bcrypt('password'),
            //     'role' => User::ROLE_FINANCE,
            // ],
            // [
            //     'name' => 'HR',
            //     'email' => 'hr@pos.com',
            //     'password' => bcrypt('password'),
            //     'role' => User::ROLE_HR,
            // ],
            // [
            //     'name' => 'Employee',
            //     'email' => 'er@pos.com',
            //     'password' => bcrypt('password'),
            //     'role' => User::ROLE_EMPLOYEE,
            // ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
