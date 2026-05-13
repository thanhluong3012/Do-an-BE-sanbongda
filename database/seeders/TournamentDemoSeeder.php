<?php

namespace Database\Seeders;

use App\Models\FieldCluster;
use App\Models\Field;
use App\Models\OwnerProfile;
use App\Models\Role;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentGroup;
use App\Models\TournamentGroupStanding;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\TournamentRound;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a fully-functional tournament demo:
 *   - 1 admin account
 *   - 1 owner account + OwnerProfile + FieldCluster + 2 fields (5v5)
 *   - 16 team captain accounts + 16 teams
 *   - 1 group_knockout tournament (status: in_progress, payment_status: paid)
 *   - 16 approved registrations
 *   - 4 groups (A-D), 4 teams each
 *   - Round-robin schedule for each group (3 matches × 4 groups = 24 total group matches... actually 6 per group)
 *   - Initial standings (all zeros)
 *
 * Run: php artisan db:seed --class=TournamentDemoSeeder
 *
 * NOTE: This seeder is idempotent — it skips creation if records with the same
 * email/name already exist, so it's safe to run multiple times.
 */
class TournamentDemoSeeder extends Seeder
{
    private const PASSWORD = '12345678';

    private const TEAM_NAMES = [
        'FC Alpha', 'FC Beta', 'FC Gamma', 'FC Delta',
        'FC Phoenix', 'FC Dragon', 'FC Tiger', 'FC Eagle',
        'FC Thunder', 'FC Shark', 'FC Lion', 'FC Wolf',
        'FC Falcon', 'FC Storm', 'FC United', 'FC Victory',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $adminRole = Role::where('name_role', 'admin')->firstOrFail();
            $userRole  = Role::where('name_role', 'member')->firstOrFail();
            $ownerRole = Role::where('name_role', 'owner')->firstOrFail();

            // ================================================================
            // 1. Admin account
            // ================================================================
            $admin = User::firstOrCreate(
                ['email' => 'admin@test.com'],
                [
                    'name'              => 'Admin Demo',
                    'phone'             => '0900000000',
                    'password'          => Hash::make(self::PASSWORD),
                    'role_id'           => $adminRole->id,
                    'email_verified_at' => now(),
                ]
            );

            // ================================================================
            // 2. Owner account
            // ================================================================
            $ownerUser = User::firstOrCreate(
                ['email' => 'owner@test.com'],
                [
                    'name'              => 'Owner Demo',
                    'phone'             => '0901000001',
                    'password'          => Hash::make(self::PASSWORD),
                    'role_id'           => $ownerRole->id,
                    'email_verified_at' => now(),
                ]
            );

            $ownerProfile = OwnerProfile::firstOrCreate(
                ['user_id' => $ownerUser->id],
                [
                    'business_name' => 'Cụm Sân Demo',
                    'address'       => '123 Đường Demo, Hà Nội',
                    'code_id'       => '000000000000',
                ]
            );

            // ================================================================
            // 3. FieldCluster + 2 fields
            // ================================================================
            $cluster = FieldCluster::firstOrCreate(
                ['owner_id' => $ownerProfile->id, 'name' => 'Cụm Sân Demo Park'],
                [
                    'address'    => '123 Đường Demo, Hà Nội',
                    'phone'      => '0901000001',
                    'open_time'  => '06:00',
                    'close_time' => '23:00',
                ]
            );

            $field1 = Field::firstOrCreate(
                ['field_cluster_id' => $cluster->id, 'name' => 'Sân 1'],
                ['field_type' => '5v5', 'status' => 'available']
            );

            $field2 = Field::firstOrCreate(
                ['field_cluster_id' => $cluster->id, 'name' => 'Sân 2'],
                ['field_type' => '5v5', 'status' => 'available']
            );

            $fieldIds = [$field1->id, $field2->id];

            // ================================================================
            // 4. Organizer (team captain accounts + teams)
            // ================================================================
            $organizer = User::firstOrCreate(
                ['email' => 'organizer@test.com'],
                [
                    'name'              => 'Ban Tổ Chức Demo',
                    'phone'             => '0901000002',
                    'password'          => Hash::make(self::PASSWORD),
                    'role_id'           => $userRole->id,
                    'email_verified_at' => now(),
                ]
            );

            // 16 team captain accounts + teams
            $teams = [];
            foreach (self::TEAM_NAMES as $i => $teamName) {
                $idx = $i + 1;
                $email = "team{$idx}@test.com";

                $captain = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'              => "Đội trưởng {$teamName}",
                        'phone'             => '090200' . str_pad($idx, 4, '0', STR_PAD_LEFT),
                        'password'          => Hash::make(self::PASSWORD),
                        'role_id'           => $userRole->id,
                        'email_verified_at' => now(),
                    ]
                );

                $team = Team::firstOrCreate(
                    ['name' => $teamName],
                    [
                        'captain_user_id' => $captain->id,
                        'short_name'      => strtoupper(substr(explode(' ', $teamName)[1], 0, 3)),
                        'contact_phone'   => $captain->phone,
                        'contact_email'   => $email,
                    ]
                );

                $teams[] = $team;
            }

            // ================================================================
            // 5. Tournament
            // ================================================================
            $tournament = Tournament::firstOrCreate(
                ['name' => 'Demo Cup 2026'],
                [
                    'organizer_id'       => $organizer->id,
                    'field_cluster_id'   => $cluster->id,
                    'description'        => 'Giải đấu demo tạo bởi TournamentDemoSeeder',
                    'format'             => 'group_knockout',
                    'field_type'         => '5v5',
                    'max_teams'          => 16,
                    'min_teams'          => 8,
                    'registration_start' => now()->subDays(30),
                    'registration_end'   => now()->subDays(10),
                    'start_date'         => now()->addDays(5),
                    'end_date'           => now()->addDays(35),
                    'status'             => 'in_progress',
                    'schedule_published' => true,
                    'payment_method'     => 'cash',
                    'payment_status'     => 'paid',
                    'payment_date'       => now()->subDays(9),
                ]
            );

            // ================================================================
            // 6. Registrations (approved)
            // ================================================================
            foreach ($teams as $team) {
                TournamentRegistration::firstOrCreate(
                    ['tournament_id' => $tournament->id, 'team_id' => $team->id],
                    [
                        'registration_date'   => now()->subDays(20),
                        'registration_status' => 'approved',
                        'payment_status'      => 'paid',
                        'payment_method'      => 'cash',
                    ]
                );
            }

            // ================================================================
            // 7. Skip if schedule already generated
            // ================================================================
            if ($tournament->rounds()->count() > 0) {
                $this->command->info('Schedule already generated for Demo Cup 2026, skipping.');
                return;
            }

            // ================================================================
            // 8. Group stage — 4 groups of 4 teams
            // ================================================================
            $groupLabels = ['A', 'B', 'C', 'D'];
            $numGroups   = 4;
            $groupTeams  = array_fill(0, $numGroups, []);

            foreach ($teams as $i => $team) {
                $groupTeams[$i % $numGroups][] = $team;
            }

            $groupStageRound = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name'    => 'Vòng bảng',
                'round_type'    => 'group_stage',
                'round_order'   => 1,
                'start_date'    => now()->addDays(5),
                'end_date'      => now()->addDays(20),
                'is_knockout'   => false,
            ]);

            $matchNumber = 1;
            $startDate   = Carbon::now()->addDays(5);
            $timeSlots   = ['08:00', '10:00', '14:00', '16:00'];
            $slotIdx     = 0;
            $fieldIdx    = 0;
            $currentDate = $startDate->copy();

            for ($g = 0; $g < $numGroups; $g++) {
                $group = TournamentGroup::create([
                    'tournament_id' => $tournament->id,
                    'group_name'    => 'Bảng ' . $groupLabels[$g],
                ]);

                $teamsInGroup = $groupTeams[$g];

                // Initialize standings
                foreach ($teamsInGroup as $team) {
                    TournamentGroupStanding::create([
                        'tournament_group_id' => $group->id,
                        'team_id'             => $team->id,
                        'matches_played'      => 0,
                        'wins'                => 0,
                        'draws'               => 0,
                        'losses'              => 0,
                        'goals_for'           => 0,
                        'goals_against'       => 0,
                        'goal_difference'     => 0,
                        'points'              => 0,
                        'ranking'             => 0,
                    ]);

                    // Pivot table
                    DB::table('tournament_group_teams')->insertOrIgnore([
                        'tournament_group_id' => $group->id,
                        'team_id'             => $team->id,
                        'seed_number'         => null,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }

                // Round-robin pairings (circle method)
                $teamIds = array_map(fn ($t) => $t->id, $teamsInGroup);
                $pairings = $this->generateRoundRobinPairings($teamIds);

                foreach ($pairings as $roundPairs) {
                    foreach ($roundPairs as [$homeId, $awayId]) {
                        if (is_null($homeId) || is_null($awayId)) continue;

                        TournamentMatch::create([
                            'tournament_id'       => $tournament->id,
                            'tournament_round_id' => $groupStageRound->id,
                            'tournament_group_id' => $group->id,
                            'home_team_id'        => $homeId,
                            'away_team_id'        => $awayId,
                            'field_id'            => $fieldIds[$fieldIdx % count($fieldIds)],
                            'match_date'          => $currentDate->toDateString(),
                            'match_time'          => $timeSlots[$slotIdx % count($timeSlots)],
                            'match_number'        => $matchNumber++,
                            'status'              => 'scheduled',
                        ]);

                        $slotIdx++;
                        if ($slotIdx >= count($timeSlots)) {
                            $slotIdx = 0;
                            $currentDate->addDay();
                        }
                        $fieldIdx++;
                    }
                }
            }

            // ================================================================
            // 9. Knockout rounds (semi + final, 4 teams advance — 1 per group)
            // ================================================================
            $knockoutStart = $currentDate->copy()->addDays(3);

            // Semi-finals
            $semiFinalRound = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name'    => 'Bán kết',
                'round_type'    => 'semi_final',
                'round_order'   => 2,
                'start_date'    => $knockoutStart,
                'end_date'      => $knockoutStart->copy()->addDays(2),
                'is_knockout'   => true,
            ]);

            $sf1 = TournamentMatch::create([
                'tournament_id'       => $tournament->id,
                'tournament_round_id' => $semiFinalRound->id,
                'home_team_id'        => null,
                'away_team_id'        => null,
                'home_from_position'  => 'Nhất bảng A',
                'away_from_position'  => 'Nhất bảng B',
                'field_id'            => $fieldIds[0],
                'match_date'          => $knockoutStart->toDateString(),
                'match_time'          => '14:00',
                'match_number'        => $matchNumber++,
                'status'              => 'scheduled',
            ]);

            $sf2 = TournamentMatch::create([
                'tournament_id'       => $tournament->id,
                'tournament_round_id' => $semiFinalRound->id,
                'home_team_id'        => null,
                'away_team_id'        => null,
                'home_from_position'  => 'Nhất bảng C',
                'away_from_position'  => 'Nhất bảng D',
                'field_id'            => $fieldIds[1 % count($fieldIds)],
                'match_date'          => $knockoutStart->toDateString(),
                'match_time'          => '16:00',
                'match_number'        => $matchNumber++,
                'status'              => 'scheduled',
            ]);

            // Final
            $finalDate = $knockoutStart->copy()->addDays(5);

            $finalRound = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name'    => 'Chung kết',
                'round_type'    => 'final',
                'round_order'   => 3,
                'start_date'    => $finalDate,
                'end_date'      => $finalDate,
                'is_knockout'   => true,
            ]);

            TournamentMatch::create([
                'tournament_id'       => $tournament->id,
                'tournament_round_id' => $finalRound->id,
                'home_team_id'        => null,
                'away_team_id'        => null,
                'home_from_match_id'  => $sf1->id,
                'away_from_match_id'  => $sf2->id,
                'home_from_position'  => 'Thắng bán kết 1',
                'away_from_position'  => 'Thắng bán kết 2',
                'field_id'            => $fieldIds[0],
                'match_date'          => $finalDate->toDateString(),
                'match_time'          => '16:00',
                'match_number'        => $matchNumber++,
                'status'              => 'scheduled',
            ]);

            // Third-place
            $thirdPlaceRound = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name'    => 'Tranh hạng 3',
                'round_type'    => 'third_place',
                'round_order'   => 3,
                'start_date'    => $finalDate,
                'end_date'      => $finalDate,
                'is_knockout'   => true,
            ]);

            TournamentMatch::create([
                'tournament_id'       => $tournament->id,
                'tournament_round_id' => $thirdPlaceRound->id,
                'home_team_id'        => null,
                'away_team_id'        => null,
                'home_from_position'  => 'Thua bán kết 1',
                'away_from_position'  => 'Thua bán kết 2',
                'field_id'            => $fieldIds[1 % count($fieldIds)],
                'match_date'          => $finalDate->toDateString(),
                'match_time'          => '14:00',
                'match_number'        => $matchNumber++,
                'status'              => 'scheduled',
            ]);

            $this->command->info('TournamentDemoSeeder completed successfully!');
            $this->command->info("  Admin:     admin@test.com / " . self::PASSWORD);
            $this->command->info("  Owner:     owner@test.com / " . self::PASSWORD);
            $this->command->info("  Organizer: organizer@test.com / " . self::PASSWORD);
            $this->command->info("  Teams:     team1@test.com .. team16@test.com / " . self::PASSWORD);
            $this->command->info("  Tournament: Demo Cup 2026 (id={$tournament->id})");
        });
    }

    /**
     * Berger table / circle method for round-robin pairings.
     * Returns array of rounds, each round is array of [homeId, awayId] pairs.
     */
    private function generateRoundRobinPairings(array $teamIds): array
    {
        $n = count($teamIds);
        if ($n % 2 === 1) {
            $teamIds[] = null; // bye
            $n++;
        }

        $rounds   = [];
        $fixed    = $teamIds[0];
        $rotating = array_slice($teamIds, 1);
        $half     = $n / 2;

        for ($r = 0; $r < $n - 1; $r++) {
            $round   = [];
            $round[] = [$fixed, $rotating[$n - 2]];

            for ($i = 0; $i < $half - 1; $i++) {
                $round[] = [$rotating[$i], $rotating[$n - 2 - $i - 1]];
            }

            $rounds[] = $round;
            array_push($rotating, array_shift($rotating));
        }

        return $rounds;
    }
}
