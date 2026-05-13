<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Field;
use App\Models\FieldCluster;
use App\Models\FieldHourlyPrice;
use App\Models\OwnerProfile;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ComprehensiveSeeder extends Seeder
{
    private $fieldTypes = ['5v5', '7v7', '11v11'];
    private $tournamentFormats = [
        ['format' => 'knockout', 'max_teams' => 4, 'field_type' => '5v5'],
        ['format' => 'round_robin', 'max_teams' => 5, 'field_type' => '5v5'],
        ['format' => 'knockout', 'max_teams' => 8, 'field_type' => '7v7'],
        ['format' => 'round_robin', 'max_teams' => 10, 'field_type' => '7v7'],
        ['format' => 'knockout', 'max_teams' => 12, 'field_type' => '11v11'],
        ['format' => 'knockout', 'max_teams' => 16, 'field_type' => '11v11'],
        ['format' => 'knockout', 'max_teams' => 32, 'field_type' => '11v11'],
        ['format' => 'group_knockout', 'max_teams' => 16, 'field_type' => '7v7'],
        ['format' => 'knockout', 'max_teams' => 64, 'field_type' => '11v11'],
        ['format' => 'group_knockout', 'max_teams' => 32, 'field_type' => '11v11'],
    ];

    private $positions = ['Thủ môn', 'Hậu vệ', 'Tiền vệ', 'Tiền đạo'];
    private $firstNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng'];
    private $middleNames = ['Văn', 'Thị', 'Minh', 'Quốc', 'Hữu', 'Đức', 'Anh', 'Thành', 'Như', 'Xuân'];
    private $lastNames = ['An', 'Bình', 'Cường', 'Dũng', 'Hùng', 'Khang', 'Long', 'Minh', 'Nam', 'Phong', 'Quân', 'Sơn', 'Tài', 'Tuấn', 'Vinh'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🌱 Bắt đầu seed dữ liệu toàn diện...\n\n";

        // 1. Tạo 10 tài khoản đặt sân cá nhân
        echo "📝 Tạo 10 tài khoản đặt sân cá nhân...\n";
        $individualUsers = $this->createIndividualUsers(10);

        // 2. Tạo 30 tài khoản có đội bóng (mỗi đội 10 thành viên)
        echo "⚽ Tạo 30 tài khoản với đội bóng (mỗi đội 10 thành viên)...\n";
        $teamCaptains = $this->createTeamCaptainsWithTeams(30);

        // 3. Tạo 20 tài khoản chủ sân với cụm sân
        echo "🏟️  Tạo 20 tài khoản chủ sân với cụm sân...\n";
        $owners = $this->createOwnersWithFieldClusters(20);

        // 4. Tạo 10 tài khoản tổ chức giải đấu
        echo "🏆 Tạo 10 giải đấu với các thể thức khác nhau...\n";
        $tournaments = $this->createTournamentsWithRegistrations($owners);

        echo "\n✅ Hoàn thành seed dữ liệu!\n";
        echo "📊 Tổng kết:\n";
        echo "   - " . count($individualUsers) . " tài khoản đặt sân cá nhân\n";
        echo "   - " . count($teamCaptains) . " tài khoản đội trưởng với đội bóng\n";
        echo "   - " . Team::count() . " đội bóng\n";
        echo "   - " . TeamMember::count() . " thành viên đội bóng\n";
        echo "   - " . count($owners) . " chủ sân\n";
        echo "   - " . FieldCluster::count() . " cụm sân\n";
        echo "   - " . Field::count() . " sân con\n";
        echo "   - " . count($tournaments) . " giải đấu\n";
        echo "   - " . TournamentRegistration::count() . " đội đăng ký giải đấu\n";
        echo "   - " . Book::where('booking_type', 'tournament')->count() . " booking sân cho giải đấu\n";
    }

    /**
     * Tạo tài khoản đặt sân cá nhân
     */
    private function createIndividualUsers(int $count): array
    {
        $memberRole = Role::where('name_role', 'member')->first();
        $users = [];

        for ($i = 1; $i <= $count; $i++) {
            $user = User::create([
                'role_id' => $memberRole->id,
                'name' => "Khách hàng cá nhân " . $i,
                'email' => "individual{$i}@gmail.com",
                'phone' => "090" . str_pad($i, 7, '0', STR_PAD_LEFT),
                'code_id' => $this->generateCodeId(),
                'status' => true, // boolean: true = active
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Tạo tài khoản đội trưởng với đội bóng và thành viên
     */
    private function createTeamCaptainsWithTeams(int $count): array
    {
        $memberRole = Role::where('name_role', 'member')->first();
        $captains = [];

        for ($i = 1; $i <= $count; $i++) {
            // Tạo đội trưởng
            $captain = User::create([
                'role_id' => $memberRole->id,
                'name' => $this->generateRandomName(),
                'email' => "captain{$i}@gmail.com",
                'phone' => "091" . str_pad($i, 7, '0', STR_PAD_LEFT),
                'code_id' => $this->generateCodeId(),
                'status' => true, // boolean: true = active
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);

            // Tạo đội bóng
            $team = Team::create([
                'captain_user_id' => $captain->id,
                'name' => "Đội " . $this->generateTeamName($i),
                'short_name' => strtoupper($this->generateShortName($i)),
                'logo' => null,
                'contact_phone' => $captain->phone,
                'contact_email' => $captain->email,
            ]);

            // Tạo 10 thành viên cho đội
            for ($j = 1; $j <= 10; $j++) {
                TeamMember::create([
                    'team_id' => $team->id,
                    'player_name' => $this->generateRandomName(),
                    'code_id' => $this->generateCodeId(),
                    'jersey_number' => $j,
                    'position' => $this->positions[array_rand($this->positions)],
                    'total_goals' => rand(0, 15),
                    'yellow_cards' => rand(0, 3),
                    'red_cards' => rand(0, 1),
                ]);
            }

            $captains[] = $captain;
        }

        return $captains;
    }

    /**
     * Tạo chủ sân với cụm sân và sân con
     */
    private function createOwnersWithFieldClusters(int $count): array
    {
        $ownerRole = Role::where('name_role', 'owner')->first();
        $owners = [];

        for ($i = 1; $i <= $count; $i++) {
            // Tạo user chủ sân
            $user = User::create([
                'role_id' => $ownerRole->id,
                'name' => "Chủ sân " . $i,
                'email' => "owner{$i}@gmail.com",
                'phone' => "092" . str_pad($i, 7, '0', STR_PAD_LEFT),
                'code_id' => $this->generateCodeId(),
                'status' => true, // boolean: true = active
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);

            // Tạo owner profile
            $ownerProfile = OwnerProfile::create([
                'user_id' => $user->id,
                'business_name' => "Sân bóng {$i}",
                'address' => "Địa chỉ cụm sân {$i}, Quận " . rand(1, 12) . ", TP.HCM",
                'code_id' => $this->generateCodeId(),
                'image_code' => null,
                'qr_code_image' => null,
            ]);

            // Tạo 1 hoặc 2 cụm sân ngẫu nhiên
            $clusterCount = rand(1, 2);
            for ($c = 1; $c <= $clusterCount; $c++) {
                $cluster = FieldCluster::create([
                    'owner_id' => $ownerProfile->id,
                    'name' => "Cụm sân {$i}-{$c}",
                    'address' => "Địa chỉ cụm {$c}, Quận " . rand(1, 12) . ", TP.HCM",
                    'phone' => $user->phone,
                    'description' => "Cụm sân chất lượng cao với đầy đủ tiện nghi",
                    'image' => null,
                    'open_time' => '06:00:00',
                    'close_time' => '23:00:00',
                ]);

                // Tạo 4 sân con với loại ngẫu nhiên
                for ($f = 1; $f <= 4; $f++) {
                    $fieldType = $this->fieldTypes[array_rand($this->fieldTypes)];
                    $field = Field::create([
                        'field_cluster_id' => $cluster->id,
                        'name' => "Sân {$f}",
                        'field_type' => $fieldType,
                        'status' => true, // boolean: true = active
                    ]);

                    // Tạo giá theo giờ cho sân
                    $this->createHourlyPrices($cluster->id, $field->field_type);
                }
            }

            $owners[] = $ownerProfile;
        }

        return $owners;
    }

    /**
     * Tạo giá theo giờ cho sân
     */
    private function createHourlyPrices(int $clusterId, string $fieldType): void
    {
        $basePrice = match($fieldType) {
            '5v5' => 300000,
            '7v7' => 500000,
            '11v11' => 800000,
            default => 400000,
        };

        // Giá ngày thường
        FieldHourlyPrice::updateOrCreate(
            [
                'field_cluster_id' => $clusterId,
                'day_type' => 'weekday',
                'field_type' => $fieldType,
            ],
            [
                'price_hour' => $basePrice,
            ]
        );

        // Giá cuối tuần
        FieldHourlyPrice::updateOrCreate(
            [
                'field_cluster_id' => $clusterId,
                'day_type' => 'weekend',
                'field_type' => $fieldType,
            ],
            [
                'price_hour' => $basePrice * 1.2,
            ]
        );
    }

    /**
     * Tạo giải đấu và đăng ký đội
     */
    private function createTournamentsWithRegistrations(array $owners): array
    {
        $memberRole = Role::where('name_role', 'member')->first();
        $tournaments = [];
        $allTeams = Team::all();

        foreach ($this->tournamentFormats as $index => $format) {
            $i = $index + 1;

            // Tạo tài khoản tổ chức giải
            $organizer = User::create([
                'role_id' => $memberRole->id,
                'name' => "Ban tổ chức giải {$i}",
                'email' => "organizer{$i}@gmail.com",
                'phone' => "093" . str_pad($i, 7, '0', STR_PAD_LEFT),
                'code_id' => $this->generateCodeId(),
                'status' => true, // boolean: true = active
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]);

            // Chọn cụm sân ngẫu nhiên có sân phù hợp với loại giải
            $suitableClusters = FieldCluster::whereHas('fields', function($query) use ($format) {
                $query->where('field_type', $format['field_type'])->where('status', true);
            })->get();

            if ($suitableClusters->isEmpty()) {
                echo "⚠️  Không tìm thấy cụm sân phù hợp cho giải {$i}\n";
                continue;
            }

            $selectedCluster = $suitableClusters->random();

            // Tạo giải đấu
            $tournament = Tournament::create([
                'name' => "Giải đấu {$format['format']} - {$format['max_teams']} đội",
                'description' => "Giải đấu {$format['max_teams']} đội theo thể thức {$format['format']}",
                'tournament_rules_file' => null,
                'format' => $format['format'],
                'field_type' => $format['field_type'],
                'max_teams' => $format['max_teams'],
                'min_teams' => max(4, $format['max_teams'] / 2),
                'registration_start' => Carbon::now()->subDays(30),
                'registration_end' => Carbon::now()->subDays(7),
                'start_date' => Carbon::now()->addDays(7),
                'end_date' => Carbon::now()->addDays(37),
                'status' => 'open',
                'schedule_published' => true,
                'field_cluster_id' => $selectedCluster->id,
                'organizer_id' => $organizer->id,
                'payment_method' => 'transfer',
                'payment_status' => 'paid',
                'payment_date' => Carbon::now()->subDays(5),
                'payment_proof' => null,
            ]);

            // Đăng ký đội vào giải (lấy đủ số đội theo max_teams)
            $teamsToRegister = $allTeams->random(min($format['max_teams'], $allTeams->count()));
            
            foreach ($teamsToRegister as $team) {
                TournamentRegistration::create([
                    'tournament_id' => $tournament->id,
                    'team_id' => $team->id,
                    'registration_date' => Carbon::now()->subDays(rand(7, 25)),
                    'registration_status' => 'approved',
                    'payment_status' => 'paid',
                    'payment_method' => 'transfer',
                    'payment_date' => Carbon::now()->subDays(rand(5, 20)),
                    'payment_proof' => null,
                    'payment_confirmed_at' => Carbon::now()->subDays(rand(3, 18)),
                    'payment_note' => 'Đã thanh toán đầy đủ',
                    'notes' => null,
                    'admin_notes' => null,
                    'rejected_reason' => null,
                ]);
            }

            // Tạo lịch thi đấu và booking sân cho giải
            $this->createTournamentScheduleAndBookings($tournament, $selectedCluster);

            $tournaments[] = $tournament;
            echo "   ✓ Giải {$i}: {$tournament->name} ({$teamsToRegister->count()} đội)\n";
        }

        return $tournaments;
    }

    /**
     * Tạo lịch thi đấu và booking sân cho giải
     */
    private function createTournamentScheduleAndBookings(Tournament $tournament, FieldCluster $cluster): void
    {
        // Lấy các sân phù hợp với loại giải
        $availableFields = $cluster->fields()
            ->where('field_type', $tournament->field_type)
            ->where('status', true)
            ->get();

        if ($availableFields->isEmpty()) {
            return;
        }

        // Tính số trận đấu ước lượng
        $teamCount = $tournament->registrations()->where('registration_status', 'approved')->count();
        $matchCount = $this->estimateMatchCount($tournament->format, $teamCount);

        // Tạo lịch thi đấu và booking cho các trận
        $currentDate = Carbon::parse($tournament->start_date);
        $matchesPerDay = min(4, $availableFields->count()); // Tối đa 4 trận/ngày
        $daysNeeded = ceil($matchCount / $matchesPerDay);

        for ($day = 0; $day < $daysNeeded; $day++) {
            $matchDate = $currentDate->copy()->addDays($day);
            
            // Xác định loại ngày
            $dayType = in_array($matchDate->dayOfWeek, [0, 6]) ? 'weekend' : 'weekday';

            // Tạo schedule cho ngày này
            $schedule = TournamentSchedule::create([
                'tournament_id' => $tournament->id,
                'match_date' => $matchDate,
                'session_type' => 'evening', // Chủ yếu thi đấu tối
                'day_type' => $dayType,
                'number_of_fields' => min($matchesPerDay, $availableFields->count()),
                'price_per_field' => $this->getPriceForSession($cluster->id, $tournament->field_type, $dayType, 'evening'),
                'session_cost' => $this->getPriceForSession($cluster->id, $tournament->field_type, $dayType, 'evening') * min($matchesPerDay, $availableFields->count()),
                'notes' => "Lịch thi đấu ngày {$matchDate->format('d/m/Y')}",
            ]);

            // Tạo booking cho các trận trong ngày
            $matchesThisDay = min($matchesPerDay, $matchCount - ($day * $matchesPerDay));
            
            for ($m = 0; $m < $matchesThisDay; $m++) {
                $field = $availableFields[$m % $availableFields->count()];
                $startHour = 18 + ($m * 2); // Bắt đầu từ 18h, mỗi trận cách nhau 2h
                
                if ($startHour >= 23) break; // Không book quá 23h

                // Kiểm tra xem đã có booking cho field này vào thời gian này chưa
                $existingBooking = Book::where('field_id', $field->id)
                    ->where('day_book', $matchDate)
                    ->where('start_time', sprintf('%02d:00:00', $startHour))
                    ->first();

                // Nếu đã có booking, bỏ qua hoặc thử sân khác
                if ($existingBooking) {
                    // Thử tìm sân khác trong cùng cụm
                    $alternateField = null;
                    foreach ($availableFields as $altField) {
                        $altBooking = Book::where('field_id', $altField->id)
                            ->where('day_book', $matchDate)
                            ->where('start_time', sprintf('%02d:00:00', $startHour))
                            ->first();
                        
                        if (!$altBooking) {
                            $alternateField = $altField;
                            break;
                        }
                    }
                    
                    if (!$alternateField) {
                        continue; // Bỏ qua trận này nếu không có sân trống
                    }
                    
                    $field = $alternateField;
                }

                Book::create([
                    'user_id' => $tournament->organizer_id,
                    'tournament_id' => $tournament->id,
                    'booking_type' => 'tournament',
                    'status' => 'confirmed',
                    'field_id' => $field->id,
                    'day_book' => $matchDate,
                    'start_time' => sprintf('%02d:00:00', $startHour),
                    'end_time' => sprintf('%02d:00:00', min($startHour + 2, 23)),
                    'total_price' => $this->getPriceForSession($cluster->id, $tournament->field_type, $dayType, 'evening'),
                    'payment_status' => 'paid',
                    'payment_method' => 'transfer',
                    'payment_proof' => null,
                    'payment_confirmed_at' => now(),
                    'confirmed_by_user_id' => null,
                    'payment_note' => 'Booking sân cho giải đấu',
                ]);
            }
        }
    }

    /**
     * Ước lượng số trận đấu
     */
    private function estimateMatchCount(string $format, int $teamCount): int
    {
        return match($format) {
            'single_elimination' => $teamCount - 1,
            'round_robin' => ($teamCount * ($teamCount - 1)) / 2,
            'group_stage_knockout' => (int)(($teamCount * 3) + ($teamCount / 2)),
            default => $teamCount * 3,
        };
    }

    /**
     * Lấy giá theo session
     */
    private function getPriceForSession(int $clusterId, string $fieldType, string $dayType, string $sessionType): float
    {
        $price = FieldHourlyPrice::where('field_cluster_id', $clusterId)
            ->where('field_type', $fieldType)
            ->where('day_type', $dayType)
            ->first();

        return $price ? $price->price_hour : 500000;
    }

    /**
     * Helper functions
     */
    private function generateCodeId(): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(9))), 0, 12);
    }

    private function generateRandomName(): string
    {
        $firstName = $this->firstNames[array_rand($this->firstNames)];
        $middleName = $this->middleNames[array_rand($this->middleNames)];
        $lastName = $this->lastNames[array_rand($this->lastNames)];
        return "{$firstName} {$middleName} {$lastName}";
    }

    private function generateTeamName(int $index): string
    {
        $names = ['Rồng', 'Phượng', 'Báo', 'Hổ', 'Sư Tử', 'Đại Bàng', 'Chim Ưng', 'Sóc Vàng', 
                  'Thiên Long', 'Hải Âu', 'Phù Đổng', 'Lạc Hồng', 'Việt Nam', 'Sài Gòn', 
                  'Hà Nội', 'Đà Nẵng', 'Cần Thơ', 'Huế', 'Quảng Nam', 'Bình Dương'];
        return $names[$index % count($names)];
    }

    private function generateShortName(int $index): string
    {
        $shorts = ['DRG', 'PHX', 'LEO', 'TGR', 'LIO', 'EAG', 'HAW', 'FOX', 
                   'TDR', 'SEG', 'PDG', 'LHG', 'VNM', 'SGN', 
                   'HAN', 'DNG', 'CTH', 'HUE', 'QNM', 'BDG'];
        return $shorts[$index % count($shorts)];
    }
}
