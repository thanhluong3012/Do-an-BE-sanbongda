<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\OwnerProfile;
use App\Models\FieldCluster;
use App\Models\Field;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentSessionPrice;
use App\Models\Book;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo Roles nếu chưa có
        $roles = ['admin', 'owner', 'staff', 'member'];
        foreach ($roles as $index => $roleName) {
            Role::firstOrCreate(
                ['name_role' => $roleName],
                ['id' => $index + 1, 'description' => ucfirst($roleName)]
            );
        }

        // 2. Tạo Admin & Owner
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'role_id' => 1,
                'name' => 'Admin Test',
                'phone' => '0900000001',
                'status' => true,
                'password' => Hash::make('123456')
            ]
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@test.com'],
            [
                'role_id' => 2,
                'name' => 'Chủ Sân Test',
                'phone' => '0900000002',
                'status' => true,
                'password' => Hash::make('123456')
            ]
        );

        // 2.1. Tạo Owner Profile
        $ownerProfile = OwnerProfile::firstOrCreate(
            ['user_id' => $owner->id],
            [
                'business_name' => 'Cụm Sân Test',
                'address' => '123 Đường Test, Quận Test',
                'code_id' => '001234567890',
            ]
        );

        // 3. Tạo Field Cluster và Fields
        $fieldCluster = FieldCluster::firstOrCreate(
            ['name' => 'Cụm Sân Test'],
            [
                'owner_id' => $ownerProfile->id,
                'address' => '123 Đường Test, Quận Test',
                'phone' => '0900000003',
                'description' => 'Cụm sân test cho giải đấu',
                'open_time' => '06:00:00',
                'close_time' => '23:00:00',
            ]
        );

        // Xóa các fields cũ của cụm sân này để tạo lại
        Field::where('field_cluster_id', $fieldCluster->id)->delete();

        // Tạo nhiều loại sân: 2 sân 5v5, 1 sân 7v7, 1 sân 11v11
        $fieldConfigs = [
            ['type' => '5v5', 'count' => 2],  // 2 sân 5v5
            ['type' => '7v7', 'count' => 1],  // 1 sân 7v7
            ['type' => '11v11', 'count' => 1], // 1 sân 11v11
        ];

        $fields = [];
        foreach ($fieldConfigs as $config) {
            $type = $config['type'];
            $typeNumber = explode('v', $type)[0]; // Lấy số trước chữ 'v': 5, 7, 11

            for ($i = 1; $i <= $config['count']; $i++) {
                $fields[] = Field::create([
                    'field_cluster_id' => $fieldCluster->id,
                    'name' => "Sân {$typeNumber} ({$i})",
                    'field_type' => $type,
                    'status' => 'available',
                ]);
            }
        }

        // 4. Tạo 4 đội bóng với đội trưởng
        $teams = [];
        for ($i = 1; $i <= 4; $i++) {
            // Tạo captain
            $captain = User::firstOrCreate(
                ['email' => "captain{$i}@test.com"],
                [
                    'role_id' => 4, // member
                    'name' => "Đội Trưởng {$i}",
                    'phone' => "090000010{$i}",
                    'status' => true,
                    'password' => Hash::make('123456')
                ]
            );

            // Tạo đội
            $team = Team::firstOrCreate(
                ['name' => "Đội Test {$i}"],
                [
                    'captain_user_id' => $captain->id,
                    'short_name' => "T{$i}",
                    'contact_phone' => $captain->phone,
                    'contact_email' => $captain->email
                ]
            );

            $teams[] = $team;

            // Tạo 5 thành viên cho mỗi đội (không tính captain = 6 người)
            for ($j = 1; $j <= 5; $j++) {
                TeamMember::firstOrCreate(
                    [
                        'team_id' => $team->id,
                        'jersey_number' => $j
                    ],
                    [
                        'player_name' => "Cầu Thủ {$j} - Đội {$i}",
                        'code_id' => "00123456{$i}{$j}",
                        'position' => $this->getPosition($j),
                        'total_goals' => 0,
                        'yellow_cards' => 0,
                        'red_cards' => 0
                    ]
                );
            }
        }

        // 5. Tạo giải đấu 5v5
        $tournament = Tournament::firstOrCreate(
            ['name' => 'Giải Test 5v5 Mùa Xuân 2026'],
            [
                'description' => 'Giải đấu test cho hệ thống',
                'format' => 'round_robin',
                'field_type' => '5v5',
                'max_teams' => 4,
                'min_teams' => 4,
                'registration_start' => now()->subDays(10),
                'registration_end' => now()->subDays(5),
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(10),
                'status' => 'open',
                'field_cluster_id' => $fieldCluster->id,
                'organizer_id' => $admin->id
            ]
        );

        // 6. Đăng ký các đội vào giải
        foreach ($teams as $team) {
            TournamentRegistration::firstOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'team_id' => $team->id
                ],
                [
                    'registration_date' => now()->subDays(7),
                    'registration_status' => 'approved',
                    'payment_status' => 'paid',
                    'payment_date' => now()->subDays(6)
                ]
            );
        }

        // 7. Tạo Giá thuê sân giải đấu
        $sessionTypes = ['morning', 'afternoon', 'evening', 'full_day'];
        $prices = [
            'weekday' => [
                'morning' => 500000,
                'afternoon' => 600000,
                'evening' => 800000,
                'full_day' => 1800000,
            ],
            'weekend' => [
                'morning' => 700000,
                'afternoon' => 800000,
                'evening' => 1000000,
                'full_day' => 2300000,
            ],
        ];

        foreach (['weekday', 'weekend'] as $dayType) {
            foreach ($sessionTypes as $sessionType) {
                TournamentSessionPrice::firstOrCreate(
                    [
                        'field_cluster_id' => $fieldCluster->id,
                        'field_type' => '5v5',
                        'day_type' => $dayType,
                        'session_type' => $sessionType,
                    ],
                    [
                        'price_per_session' => $prices[$dayType][$sessionType],
                    ]
                );
            }
        }

        // 8. Book sân cho giải test (2 ngày, mỗi ngày 2 sân, mỗi sân 2 buổi)
        $bookingDate1 = now()->addDays(5); // Ngày bắt đầu giải
        $bookingDate2 = now()->addDays(6);

        $sessionRanges = [
            'morning' => ['06:00', '12:00'],
            'afternoon' => ['12:00', '18:00'],
        ];

        // Chỉ book 2 sân 5v5 (vì giải đấu là 5v5)
        $bookableFields = array_filter($fields, function($field) {
            return $field->field_type === '5v5';
        });

        foreach ([$bookingDate1, $bookingDate2] as $date) {
            foreach ($bookableFields as $field) {
                foreach (['morning', 'afternoon'] as $session) {
                    Book::firstOrCreate(
                        [
                            'tournament_id' => $tournament->id,
                            'field_id' => $field->id,
                            'day_book' => $date->toDateString(),
                            'start_time' => $sessionRanges[$session][0],
                        ],
                        [
                            'user_id' => $admin->id,
                            'booking_type' => 'tournament',
                            'end_time' => $sessionRanges[$session][1],
                            'total_price' => 500000, // Giá weekday morning/afternoon
                        ]
                    );
                }
            }
        }

        $this->command->info('✅ Đã tạo xong dữ liệu test!');
        $this->command->info('');
        $this->command->info('📋 Tài khoản test:');
        $this->command->info('Admin: admin@test.com / 123456');
        $this->command->info('Owner: owner@test.com / 123456');
        $this->command->info('Captain 1: captain1@test.com / 123456');
        $this->command->info('Captain 2: captain2@test.com / 123456');
        $this->command->info('Captain 3: captain3@test.com / 123456');
        $this->command->info('Captain 4: captain4@test.com / 123456');
        $this->command->info('');
        $this->command->info('🏆 Giải đấu: "Giải Test 5v5 Mùa Xuân 2026"');
        $this->command->info('👥 Có 4 đội, mỗi đội 6 người (1 captain + 5 members)');
        $this->command->info('✅ Tất cả đội đã được approve');
    }

    /**
     * Lấy vị trí theo số thứ tự
     */
    private function getPosition($number)
    {
        $positions = [
            1 => 'Thủ môn',
            2 => 'Hậu vệ',
            3 => 'Hậu vệ',
            4 => 'Tiền vệ',
            5 => 'Tiền đạo'
        ];

        return $positions[$number] ?? 'Tiền vệ';
    }
}
