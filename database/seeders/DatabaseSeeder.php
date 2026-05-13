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
        // Chạy RoleSeeder trước để đảm bảo có các role cần thiết
        $this->call([
            RoleSeeder::class,
        ]);

        // Chạy ComprehensiveSeeder - Seed toàn bộ dữ liệu demo
        $this->call([
            ComprehensiveSeeder::class,
        ]);

        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
