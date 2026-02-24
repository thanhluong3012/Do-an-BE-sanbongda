<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Chạy RoleSeeder trước, sau đó AdminSeeder, OwnerSeeder, StaffSeeder
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
            OwnerSeeder::class,
            StaffSeeder::class,
            FieldClusterSeeder::class,
            FieldSeeder::class,
            FieldHourlyPriceSeeder::class,
        ]);

        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
