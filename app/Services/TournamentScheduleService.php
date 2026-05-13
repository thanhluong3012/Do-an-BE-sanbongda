<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentRound;
use App\Models\TournamentGroup;
use App\Models\TournamentGroupStanding;
use App\Models\TournamentMatch;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service quản lý sinh lịch thi đấu tự động cho giải bóng đá
 * Hỗ trợ 3 thể thức: round_robin, knockout, group_knockout
 */
class TournamentScheduleService
{
    // ================================================================
    // PUBLIC METHODS - Entry points
    // ================================================================

    /**
     * Sinh lịch thi đấu vòng tròn (Round Robin)
     * Tất cả đội đá với nhau đúng 1 lần
     */
    public function generateRoundRobin(
        Tournament $tournament,
        Collection $teams,
        Carbon $startDate,
        array $timeSlots,
        array $fieldIds,
        int $daysBetweenRounds,
        bool $shuffleTeams = true
    ): array {
        // Xáo trộn đội nếu cần
        $teamList = $shuffleTeams ? $teams->shuffle()->values() : $teams->values();
        $teamIds = $teamList->pluck('id')->toArray();

        // Tạo bảng group duy nhất để theo dõi standings
        $group = TournamentGroup::create([
            'tournament_id' => $tournament->id,
            'group_name' => 'Vòng bảng',
            'group_order' => 1,
        ]);

        // Khởi tạo standings cho tất cả đội
        foreach ($teamList as $team) {
            TournamentGroupStanding::create([
                'tournament_group_id' => $group->id,
                'team_id' => $team->id,
                'matches_played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
                'ranking' => 0,
            ]);
        }

        // Sinh cặp đấu vòng tròn bằng Circle Method
        $pairings = $this->generateRoundRobinPairings($teamIds);
        $totalRounds = count($pairings);

        // Flatten và transform pairings thành format cho assignMatchesToSlots
        $flatPairings = [];
        foreach ($pairings as $roundPairs) {
            foreach ($roundPairs as $pair) {
                // $pair = [homeId, awayId]
                // Bỏ qua trận có BYE (team null)
                if ($pair[0] === null || $pair[1] === null) {
                    continue;
                }
                
                $flatPairings[] = [
                    'home_id' => $pair[0],
                    'away_id' => $pair[1],
                    'group_id' => $group->id,
                ];
            }
        }

        // Tạo vòng đấu
        $round = TournamentRound::create([
            'tournament_id' => $tournament->id,
            'round_name' => 'Vòng bảng',
            'round_type' => 'group_stage',
            'round_order' => 1,
            'start_date' => $startDate,
            'end_date' => null, // Sẽ update sau khi assign slots
            'is_knockout' => false,
        ]);

        // Assign matches vào time slots
        $matches = $this->assignMatchesToSlots(
            tournament: $tournament,
            round: $round,
            pairings: $flatPairings, // Sử dụng flat pairings đã transform
            startDate: $startDate,
            timeSlots: $timeSlots,
            fieldIds: $fieldIds,
            groupId: null // Không cần truyền groupId vì đã có trong pairings
        );

        // Update end_date của round
        $lastMatch = $matches->last();
        if ($lastMatch) {
            $round->update(['end_date' => $lastMatch->match_date]);
        }

        return [
            'format' => 'round_robin',
            'teams' => count($teamIds),
            'total_rounds' => $totalRounds,
            'matches_created' => $matches->count(),
            'groups' => [
                [
                    'id' => $group->id,
                    'name' => $group->group_name,
                    'teams_count' => count($teamIds),
                ]
            ],
        ];
    }

    private function getTeamId($team): ?int
    {
        if ($team === null) {
            return null;
        }

        return is_array($team) ? ($team['id'] ?? null) : $team->id;
    }
    /**
     * Sinh lịch thi đấu loại trực tiếp (Knockout)
     * Đội thua bị loại ngay
     */
    public function generateKnockout(
        Tournament $tournament,
        Collection $teams,
        Carbon $startDate,
        array $timeSlots,
        array $fieldIds,
        int $daysBetweenRounds,
        bool $shuffleTeams = true,
        bool $createThirdPlaceMatch = false
    ): array {
        // Xáo trộn đội nếu cần
        $teamList = $shuffleTeams ? $teams->shuffle()->values() : $teams->values();
        $n = $teamList->count();

        // Kiểm tra số đội tối thiểu
        if ($n < 2) {
            throw new \Exception('Cần ít nhất 2 đội để tạo giải loại trực tiếp.');
        }

        // Tìm số đội bracket chuẩn (lũy thừa của 2)
        $bracketSize = $this->getNextPowerOfTwo($n);
        $numByeTeams = $bracketSize - $n;
        $numRounds = (int) log($bracketSize, 2);

        // Tạo danh sách đội với BYE
        $teamsWithBye = $teamList->values()->all();
        for ($i = 0; $i < $numByeTeams; $i++) {
            $teamsWithBye[] = null; // null = BYE
        }

        // Tạo bracket ban đầu (vòng 1)
        $currentBracket = [];
        for ($i = 0; $i < $bracketSize / 2; $i++) {
            $currentBracket[] = [
                'home' => $teamsWithBye[$i * 2] ?? null,
                'away' => $teamsWithBye[$i * 2 + 1] ?? null,
            ];
        }

        $matchNumber = 1;
        $roundData = [];
        $currentDate = $startDate->copy();
        
        // Track previous round matches để link với vòng tiếp theo
        $previousRoundMatches = [];

        // Tên vòng đấu tính từ cuối
        $roundTypes = $this->getRoundTypesForTeamCount($bracketSize);

        // Tạo các vòng knockout
        for ($r = 0; $r < $numRounds; $r++) {
            $roundType = $roundTypes[$r] ?? 'knockout';
            $roundName = $this->getRoundNameForType($roundType);
            
            $round = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name' => $roundName,
                'round_type' => $roundType,
                'round_order' => $r + 1,
                'start_date' => $currentDate,
                'end_date' => $currentDate->copy()->addDays(max(0, $daysBetweenRounds - 1)),
                'is_knockout' => true,
            ]);

            $roundMatches = [];
            $pairings = [];

            foreach ($currentBracket as $idx => $pair) {
                $home = $pair['home'];
                $away = $pair['away'];
                
                // Xác định home_from_match_id và away_from_match_id cho vòng sau vòng 1
                $homeFromMatchId = null;
                $awayFromMatchId = null;
                $homeFromPosition = null;
                $awayFromPosition = null;
                
                if ($r > 0 && !empty($previousRoundMatches)) {
                    // Link với 2 trận của vòng trước
                    $homeFromMatchId = $previousRoundMatches[$idx * 2]->id ?? null;
                    $awayFromMatchId = $previousRoundMatches[$idx * 2 + 1]->id ?? null;
                    
                    // Mô tả vị trí đội đến từ đâu
                    $homeFromPosition = $homeFromMatchId ? "Thắng trận #{$previousRoundMatches[$idx * 2]->match_number}" : null;
                    $awayFromPosition = $awayFromMatchId ? "Thắng trận #{$previousRoundMatches[$idx * 2 + 1]->match_number}" : null;
                }

                // Xử lý BYE và placeholder: team gặp BYE tự động thắng
                if ($home === null && $away === null) {
                    if ($r === 0) {
                        // Vòng 1: Cả 2 đều null = không nên xảy ra, bỏ qua
                        continue;
                    } else {
                        // Vòng sau: Cả 2 đều null = trận placeholder chờ kết quả vòng trước
                        $pairings[] = [
                            'home_id' => null,
                            'away_id' => null,
                            'home_from_match_id' => $homeFromMatchId,
                            'away_from_match_id' => $awayFromMatchId,
                            'home_from_position' => $homeFromPosition,
                            'away_from_position' => $awayFromPosition,
                            'winner' => null,
                        ];
                    }
                } elseif ($home === null) {
                    // Home BYE -> Away thắng, nhưng vẫn tạo trận để track
                    $pairings[] = [
                        'home_id' => null,
                        'away_id' => $away->id,
                        'home_from_match_id' => $homeFromMatchId,
                        'away_from_match_id' => $awayFromMatchId,
                        'home_from_position' => $homeFromPosition ?? 'BYE',
                        'away_from_position' => $awayFromPosition,
                        'winner' => $away,
                    ];
                } elseif ($away === null) {
                    // Away BYE -> Home thắng
                    $pairings[] = [
                        'home_id' => $home->id,
                        'away_id' => null,
                        'home_from_match_id' => $homeFromMatchId,
                        'away_from_match_id' => $awayFromMatchId,
                        'home_from_position' => $homeFromPosition,
                        'away_from_position' => $awayFromPosition ?? 'BYE',
                        'winner' => $home,
                    ];
                } else {
                    // Trận đấu bình thường
                    $pairings[] = [
                        'home_id' => $home ? $home->id : null,
                        'away_id' => $away ? $away->id : null,
                        'home_from_match_id' => $homeFromMatchId,
                        'away_from_match_id' => $awayFromMatchId,
                        'home_from_position' => $homeFromPosition,
                        'away_from_position' => $awayFromPosition,
                        'winner' => null, // Chưa biết
                    ];
                }
            }

            // Assign matches vào slots
            $matches = $this->assignMatchesToSlots(
                tournament: $tournament,
                round: $round,
                pairings: $pairings,
                startDate: $currentDate,
                timeSlots: $timeSlots,
                fieldIds: $fieldIds,
                groupId: null,
                startMatchNumber: $matchNumber
            );

            $matchNumber += $matches->count();
            $roundMatches = $matches->toArray();
            $roundData[$r] = [
                'round' => $round,
                'matches' => $matches,
            ];
            
            // Lưu matches của vòng này để link cho vòng tiếp theo
            $previousRoundMatches = $matches->all();

            // Chuẩn bị bracket cho vòng tiếp theo
            $nextBracket = [];
            foreach ($pairings as $pair) {
                if ($pair['winner']) {
                    // Đội có BYE tự động vào vòng sau
                    $nextBracket[] = $pair['winner'];
                } else {
                    // Chờ kết quả trận đấu - tạo placeholder
                    $nextBracket[] = null;
                }
            }

            // Ghép cặp cho vòng tiếp theo
            $currentBracket = [];
            for ($i = 0; $i < count($nextBracket) / 2; $i++) {
                $currentBracket[] = [
                    'home' => $nextBracket[$i * 2] ?? null,
                    'away' => $nextBracket[$i * 2 + 1] ?? null,
                ];
            }

            // Ngày bắt đầu vòng tiếp theo
            $currentDate->addDays($daysBetweenRounds);
        }

        // Tạo trận tranh hạng 3 nếu yêu cầu
        $thirdPlaceMatch = null;
        if ($createThirdPlaceMatch && $numRounds >= 2) {
            $semiFinalRound = $roundData[$numRounds - 2]['round'];
            $semiFinalMatches = $roundData[$numRounds - 2]['matches'];
            $finalDate = $currentDate->copy()->subDays($daysBetweenRounds); // Cùng ngày với chung kết

            // Lấy 2 trận bán kết để link
            $semiFinal1 = $semiFinalMatches->get(0);
            $semiFinal2 = $semiFinalMatches->get(1);

            $thirdPlaceRound = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name' => 'Tranh hạng 3',
                'round_type' => 'third_place',
                'round_order' => $numRounds + 1,
                'start_date' => $finalDate,
                'end_date' => $finalDate,
                'is_knockout' => true,
            ]);

            $thirdPlaceMatch = TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'tournament_round_id' => $thirdPlaceRound->id,
                'home_team_id' => null,
                'away_team_id' => null,
                'home_from_match_id' => $semiFinal1?->id, // Link với bán kết 1
                'away_from_match_id' => $semiFinal2?->id, // Link với bán kết 2
                'home_from_position' => $semiFinal1 ? "Thua trận #{$semiFinal1->match_number}" : 'Thua bán kết 1',
                'away_from_position' => $semiFinal2 ? "Thua trận #{$semiFinal2->match_number}" : 'Thua bán kết 2',
                'field_id' => $fieldIds[0],
                'match_date' => $finalDate->toDateString(),
                'match_time' => $timeSlots[0],
                'match_number' => $matchNumber++,
                'status' => 'scheduled',
                'bracket_position' => 'third_place',
            ]);
        }

        $totalMatches = array_sum(array_map(fn($rd) => $rd['matches']->count(), $roundData));
        if ($thirdPlaceMatch) {
            $totalMatches++;
        }

        return [
            'format' => 'knockout',
            'teams' => $n,
            'bracket_size' => $bracketSize,
            'bye_teams' => $numByeTeams,
            'rounds_created' => $numRounds + ($createThirdPlaceMatch ? 1 : 0),
            'matches_created' => $totalMatches,
            'create_third_place_match' => $createThirdPlaceMatch,
            'note' => $numByeTeams > 0 
                ? "Có {$numByeTeams} đội được BYE (tự động vào vòng tiếp theo)." 
                : 'Bracket chuẩn, không có BYE.',
        ];
    }

    /**
     * Sinh lịch thi đấu bảng + knockout (Group Knockout)
     * Vòng bảng -> Top teams -> Knockout
     */
    public function generateGroupKnockout(
        Tournament $tournament,
        Collection $teams,
        Carbon $startDate,
        array $timeSlots,
        array $fieldIds,
        int $daysBetweenRounds,
        int $numGroups = 2,
        ?int $teamsPerGroup = null,
        bool $shuffleTeams = true,
        bool $createThirdPlaceMatch = false,
        bool $autoFillThirdPlace = true
    ): array {
        // Xáo trộn đội nếu cần
        $teamList = $shuffleTeams ? $teams->shuffle()->values() : $teams->values();
        $n = $teamList->count();

        // Tính toán số bảng
        if ($teamsPerGroup !== null && $teamsPerGroup >= 2) {
            $numGroups = (int) ceil($n / $teamsPerGroup);
        }
        $numGroups = max(2, min($numGroups, 8)); // Giới hạn 2-8 bảng

        // Label bảng
        $groupLabels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        // Phân bổ đội vào các bảng (round-robin distribution)
        $groupTeams = array_fill(0, $numGroups, []);
        foreach ($teamList as $idx => $team) {
            $groupTeams[$idx % $numGroups][] = $team;
        }

        // ============================================
        // PHASE 1: Vòng bảng (Group Stage)
        // ============================================
        
        $groupStageRound = TournamentRound::create([
            'tournament_id' => $tournament->id,
            'round_name' => 'Vòng bảng',
            'round_type' => 'group_stage',
            'round_order' => 1,
            'start_date' => $startDate,
            'end_date' => null,
            'is_knockout' => false,
        ]);

        $groups = [];
        $allGroupPairings = [];
        $matchNumber = 1;

        // Tạo từng bảng và lịch đấu
        for ($g = 0; $g < $numGroups; $g++) {
            $group = TournamentGroup::create([
                'tournament_id' => $tournament->id,
                'group_name' => 'Bảng ' . $groupLabels[$g],
                'group_order' => $g + 1,
            ]);
            $groups[] = $group;

            // Khởi tạo standings
            foreach ($groupTeams[$g] as $team) {
                TournamentGroupStanding::create([
                    'tournament_group_id' => $group->id,
                    'team_id' => $team->id,
                    'matches_played' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'goal_difference' => 0,
                    'points' => 0,
                    'ranking' => 0,
                ]);
            }

            // Sinh lịch vòng tròn nội bộ bảng
            $teamIdsInGroup = array_map(fn($t) => $t->id, $groupTeams[$g]);
            $pairings = $this->generateRoundRobinPairings($teamIdsInGroup);

            foreach ($pairings as $roundPairs) {
                foreach ($roundPairs as [$homeId, $awayId]) {
                    if ($homeId === null || $awayId === null) continue; // Skip BYE

                    $allGroupPairings[] = [
                        'home_id' => $homeId,
                        'away_id' => $awayId,
                        'group_id' => $group->id,
                    ];
                }
            }
        }

        // Assign tất cả trận vòng bảng vào slots
        $groupMatches = $this->assignMatchesToSlots(
            tournament: $tournament,
            round: $groupStageRound,
            pairings: $allGroupPairings,
            startDate: $startDate,
            timeSlots: $timeSlots,
            fieldIds: $fieldIds,
            groupId: null, // Group ID đã có trong pairings
            startMatchNumber: $matchNumber
        );

        $matchNumber += $groupMatches->count();

        // Update end_date của group stage
        $lastGroupMatch = $groupMatches->last();
        if ($lastGroupMatch) {
            $groupStageRound->update(['end_date' => $lastGroupMatch->match_date]);
            $knockoutStartDate = Carbon::parse($lastGroupMatch->match_date)->addDays($daysBetweenRounds);
        } else {
            $knockoutStartDate = $startDate->copy()->addDays($daysBetweenRounds);
        }

        // ============================================
        // PHASE 2: Xác định đội vào knockout
        // ============================================

        // Số đội qualified: Top 2 mỗi bảng
        $teamsAdvancing = $numGroups * 2;

        // Tính bracket size chuẩn
        $knockoutBracketSize = $this->getNextPowerOfTwo($teamsAdvancing);
        $missingSlots = $knockoutBracketSize - $teamsAdvancing;

        $thirdPlaceTeamsAdded = 0;
        $thirdPlaceTeamsList = [];

        // Nếu thiếu slot và auto_fill_third_place = true, lấy đội hạng 3 tốt nhất
        if ($missingSlots > 0 && $autoFillThirdPlace) {
            $thirdPlaceTeamsList = $this->getBestThirdPlacedTeams($groups, $missingSlots);
            $thirdPlaceTeamsAdded = count($thirdPlaceTeamsList);
        }

        $totalKnockoutTeams = $teamsAdvancing + $thirdPlaceTeamsAdded;
        $knockoutBracketSize = $this->getNextPowerOfTwo($totalKnockoutTeams);

        // ============================================
        // PHASE 3: Tạo vòng knockout
        // ============================================

        $numKnockoutRounds = (int) log($knockoutBracketSize, 2);
        $roundTypes = $this->getRoundTypesForTeamCount($knockoutBracketSize);
        $knockoutRoundData = [];

        for ($r = 0; $r < $numKnockoutRounds; $r++) {
            $roundType = $roundTypes[$r] ?? 'knockout';
            $roundName = $this->getRoundNameForType($roundType);
            $roundStartDate = $knockoutStartDate->copy()->addDays($r * $daysBetweenRounds);

            $round = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name' => $roundName,
                'round_type' => $roundType,
                'round_order' => $r + 2, // +2 vì group_stage là order 1
                'start_date' => $roundStartDate,
                'end_date' => $roundStartDate->copy()->addDays(max(0, $daysBetweenRounds - 1)),
                'is_knockout' => true,
            ]);

            $pairings = [];
            $matchesInRound = $knockoutBracketSize / (int) pow(2, $r + 1);

            for ($m = 0; $m < $matchesInRound; $m++) {
                if ($r === 0) {
                    // Vòng đầu tiên: Cross-match giữa các bảng
                    // Ví dụ: A1 vs B2, B1 vs A2, C1 vs D2, D1 vs C2...
                    $pairIndex = (int) floor($m / 2);
                    $groupA = $pairIndex * 2;
                    $groupB = $pairIndex * 2 + 1;

                    if ($groupA >= $numGroups || $groupB >= $numGroups) {
                        // Nếu vượt quá số bảng, dùng placeholder
                        $pairings[] = [
                            'home_id' => null,
                            'away_id' => null,
                            'home_from_position' => 'TBD',
                            'away_from_position' => 'TBD',
                        ];
                    } elseif ($m % 2 === 0) {
                        // Match chẵn: Nhất A vs Nhì B
                        $pairings[] = [
                            'home_id' => null,
                            'away_id' => null,
                            'home_from_position' => "Nhất bảng {$groupLabels[$groupA]}",
                            'away_from_position' => "Nhì bảng {$groupLabels[$groupB]}",
                        ];
                    } else {
                        // Match lẻ: Nhất B vs Nhì A
                        $pairings[] = [
                            'home_id' => null,
                            'away_id' => null,
                            'home_from_position' => "Nhất bảng {$groupLabels[$groupB]}",
                            'away_from_position' => "Nhì bảng {$groupLabels[$groupA]}",
                        ];
                    }
                } else {
                    // Vòng sau: Thắng trận trước đó
                    $prevMatches = $knockoutRoundData[$r - 1]['matches'];
                    $match1 = $prevMatches[$m * 2] ?? null;
                    $match2 = $prevMatches[$m * 2 + 1] ?? null;

                    $pairings[] = [
                        'home_id' => null,
                        'away_id' => null,
                        'home_from_position' => $match1 ? "Thắng trận #{$match1->match_number}" : 'TBD',
                        'away_from_position' => $match2 ? "Thắng trận #{$match2->match_number}" : 'TBD',
                        'home_from_match_id' => $match1?->id,
                        'away_from_match_id' => $match2?->id,
                    ];
                }
            }

            // Assign matches vào slots
            $matches = $this->assignMatchesToSlots(
                tournament: $tournament,
                round: $round,
                pairings: $pairings,
                startDate: $roundStartDate,
                timeSlots: $timeSlots,
                fieldIds: $fieldIds,
                groupId: null,
                startMatchNumber: $matchNumber
            );

            $matchNumber += $matches->count();
            $knockoutRoundData[$r] = [
                'round' => $round,
                'matches' => $matches,
            ];
        }

        // Tạo trận tranh hạng 3 nếu yêu cầu
        $thirdPlaceMatch = null;
        if ($createThirdPlaceMatch && $numKnockoutRounds >= 2) {
            $semiFinalMatches = $knockoutRoundData[$numKnockoutRounds - 2]['matches'];
            $finalDate = $knockoutStartDate->copy()->addDays(($numKnockoutRounds - 1) * $daysBetweenRounds);

            // Lấy 2 trận bán kết để link
            $semiFinal1 = $semiFinalMatches->get(0);
            $semiFinal2 = $semiFinalMatches->get(1);

            $thirdPlaceRound = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_name' => 'Tranh hạng 3',
                'round_type' => 'third_place',
                'round_order' => $numKnockoutRounds + 2,
                'start_date' => $finalDate,
                'end_date' => $finalDate,
                'is_knockout' => true,
            ]);

            $thirdPlaceMatch = TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'tournament_round_id' => $thirdPlaceRound->id,
                'home_team_id' => null,
                'away_team_id' => null,
                'home_from_match_id' => $semiFinal1?->id, // Link với bán kết 1
                'away_from_match_id' => $semiFinal2?->id, // Link với bán kết 2
                'home_from_position' => $semiFinal1 ? "Thua trận #{$semiFinal1->match_number}" : 'Thua bán kết 1',
                'away_from_position' => $semiFinal2 ? "Thua trận #{$semiFinal2->match_number}" : 'Thua bán kết 2',
                'field_id' => $fieldIds[0],
                'match_date' => $finalDate->toDateString(),
                'match_time' => $timeSlots[0],
                'match_number' => $matchNumber++,
                'status' => 'scheduled',
                'bracket_position' => 'third_place',
            ]);
        }

        $knockoutMatchCount = array_sum(array_map(fn($rd) => $rd['matches']->count(), $knockoutRoundData));
        if ($thirdPlaceMatch) {
            $knockoutMatchCount++;
        }

        return [
            'format' => 'group_knockout',
            'total_teams' => $n,
            'num_groups' => $numGroups,
            'group_stage_matches' => $groupMatches->count(),
            'qualified_teams_count' => $totalKnockoutTeams,
            'third_place_teams_added' => $thirdPlaceTeamsAdded,
            'third_place_teams' => $thirdPlaceTeamsList,
            'knockout_bracket_size' => $knockoutBracketSize,
            'knockout_rounds' => $numKnockoutRounds,
            'knockout_matches' => $knockoutMatchCount,
            'create_third_place_match' => $createThirdPlaceMatch,
            'matches_created' => $groupMatches->count() + $knockoutMatchCount,
            'note' => 'Sau khi vòng bảng kết thúc, hệ thống sẽ tự động tính bảng xếp hạng và gán đội vào vòng knockout.',
        ];
    }

    // ================================================================
    // HELPER METHODS
    // ================================================================

    /**
     * Sinh cặp đấu vòng tròn theo Circle Method (Berger Table)
     * @param array $teamIds - Danh sách team IDs
     * @return array - Mảng các vòng, mỗi vòng là mảng các cặp [homeId, awayId]
     */
    private function generateRoundRobinPairings(array $teamIds): array
    {
        $n = count($teamIds);

        // Nếu số đội lẻ, thêm null (BYE team)
        if ($n % 2 === 1) {
            $teamIds[] = null;
            $n++;
        }

        $rounds = [];
        $fixed = $teamIds[0]; // Đội cố định
        $rotating = array_slice($teamIds, 1); // Các đội xoay vòng
        $half = $n / 2;

        for ($r = 0; $r < $n - 1; $r++) {
            $round = [];

            // Fixed team đấu với cuối rotating
            $round[] = [$fixed, $rotating[$n - 2]];

            // Các cặp còn lại
            for ($i = 0; $i < $half - 1; $i++) {
                $round[] = [$rotating[$i], $rotating[$n - 2 - $i - 1]];
            }

            $rounds[] = $round;

            // Xoay rotating sang trái 1 bước
            array_push($rotating, array_shift($rotating));
        }

        return $rounds;
    }

    /**
     * Assign các trận đấu vào time slots và fields
     * Tránh conflict về thời gian và sân
     */
    private function assignMatchesToSlots(
        Tournament $tournament,
        TournamentRound $round,
        array $pairings,
        Carbon $startDate,
        array $timeSlots,
        array $fieldIds,
        ?int $groupId = null,
        int $startMatchNumber = 1
    ): Collection {
        $matches = collect();
        $matchNumber = $startMatchNumber;

        // Tracking conflict
        $fieldSlotUsage = []; // ['2026-05-10|08:00|1' => true]
        $teamSlotUsage = [];  // ['2026-05-10|08:00|123' => true]

        $currentDate = $startDate->copy();
        $slotIdx = 0;
        $fieldIdx = 0;

        foreach ($pairings as $pairing) {
            $homeId = $pairing['home_id'] ?? null;
            $awayId = $pairing['away_id'] ?? null;
            $homeFromPosition = $pairing['home_from_position'] ?? null;
            $awayFromPosition = $pairing['away_from_position'] ?? null;
            $homeFromMatchId = $pairing['home_from_match_id'] ?? null;
            $awayFromMatchId = $pairing['away_from_match_id'] ?? null;
            $localGroupId = $pairing['group_id'] ?? $groupId;

            // Bỏ qua trận BYE (cả 2 team đều null)
            if ($homeId === null && $awayId === null && $homeFromPosition === null) {
                continue;
            }

            // Tìm slot hợp lệ (tránh conflict)
            $assigned = false;
            $maxAttempts = count($timeSlots) * count($fieldIds) * 365; // Tối đa 1 năm

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $fieldId = $fieldIds[$fieldIdx % count($fieldIds)];
                $timeSlot = $timeSlots[$slotIdx % count($timeSlots)];
                $dateStr = $currentDate->toDateString();

                $slotKey = "{$dateStr}|{$timeSlot}|{$fieldId}";
                $teamHomeKey = $homeId ? "{$dateStr}|{$timeSlot}|{$homeId}" : null;
                $teamAwayKey = $awayId ? "{$dateStr}|{$timeSlot}|{$awayId}" : null;

                // Kiểm tra conflict
                $fieldConflict = isset($fieldSlotUsage[$slotKey]);
                $teamHomeConflict = $teamHomeKey && isset($teamSlotUsage[$teamHomeKey]);
                $teamAwayConflict = $teamAwayKey && isset($teamSlotUsage[$teamAwayKey]);

                if (!$fieldConflict && !$teamHomeConflict && !$teamAwayConflict) {
                    // Slot hợp lệ
                    $match = TournamentMatch::create([
                        'tournament_id' => $tournament->id,
                        'tournament_round_id' => $round->id,
                        'tournament_group_id' => $localGroupId,
                        'home_team_id' => $homeId,
                        'away_team_id' => $awayId,
                        'home_from_position' => $homeFromPosition,
                        'away_from_position' => $awayFromPosition,
                        'home_from_match_id' => $homeFromMatchId,
                        'away_from_match_id' => $awayFromMatchId,
                        'field_id' => $fieldId,
                        'match_date' => $dateStr,
                        'match_time' => $timeSlot,
                        'match_number' => $matchNumber++,
                        'status' => 'scheduled',
                    ]);

                    $matches->push($match);

                    // Đánh dấu slot đã dùng
                    $fieldSlotUsage[$slotKey] = true;
                    if ($teamHomeKey) $teamSlotUsage[$teamHomeKey] = true;
                    if ($teamAwayKey) $teamSlotUsage[$teamAwayKey] = true;

                    $assigned = true;
                    break;
                }

                // Chuyển slot tiếp theo
                $slotIdx++;
                if ($slotIdx >= count($timeSlots)) {
                    $slotIdx = 0;
                    $fieldIdx++;
                    if ($fieldIdx >= count($fieldIds)) {
                        $fieldIdx = 0;
                        $currentDate->addDay(); // Chuyển ngày tiếp theo
                    }
                }
            }

            if (!$assigned) {
                throw new \Exception("Không thể tìm slot hợp lệ cho trận đấu sau {$maxAttempts} lần thử. Vui lòng tăng số sân hoặc time slots.");
            }
        }

        return $matches;
    }

    /**
     * Lấy danh sách các đội hạng 3 tốt nhất từ các bảng
     * Dùng khi cần đủ số đội cho knockout bracket
     */
    private function getBestThirdPlacedTeams(array $groups, int $count): array
    {
        $thirdPlaceTeams = [];

        foreach ($groups as $group) {
            // Lấy đội hạng 3 (ranking = 3)
            $thirdPlace = TournamentGroupStanding::where('tournament_group_id', $group->id)
                ->where('ranking', 3)
                ->with('team')
                ->first();

            if ($thirdPlace) {
                $thirdPlaceTeams[] = $thirdPlace;
            }
        }

        // Sắp xếp theo thành tích
        usort($thirdPlaceTeams, function ($a, $b) {
            // 1. Điểm cao hơn
            if ($a->points !== $b->points) {
                return $b->points - $a->points;
            }

            // 2. Hiệu số bàn thắng cao hơn
            if ($a->goal_difference !== $b->goal_difference) {
                return $b->goal_difference - $a->goal_difference;
            }

            // 3. Số bàn thắng nhiều hơn
            if ($a->goals_for !== $b->goals_for) {
                return $b->goals_for - $a->goals_for;
            }

            // 4. Số trận thắng nhiều hơn
            if ($a->wins !== $b->wins) {
                return $b->wins - $a->wins;
            }

            // 5. Random
            return rand(-1, 1);
        });

        // Lấy top $count
        $selected = array_slice($thirdPlaceTeams, 0, $count);

        return array_map(function ($standing) {
            return [
                'team_id' => $standing->team_id,
                'team_name' => $standing->team->name ?? 'Unknown',
                'group_name' => $standing->tournamentGroup->group_name ?? 'Unknown',
                'points' => $standing->points,
                'goal_difference' => $standing->goal_difference,
                'goals_for' => $standing->goals_for,
                'ranking' => $standing->ranking,
            ];
        }, $selected);
    }

    /**
     * Tính bảng xếp hạng cho một group
     * Gọi sau khi update kết quả trận đấu
     */
    public function calculateStandings(int $groupId): void
    {
        $standings = TournamentGroupStanding::where('tournament_group_id', $groupId)
            ->get();

        // Sắp xếp theo thành tích
        $standings = $standings->sortBy([
            ['points', 'desc'],
            ['goal_difference', 'desc'],
            ['goals_for', 'desc'],
            ['wins', 'desc'],
        ]);

        // Cập nhật ranking
        $rank = 1;
        foreach ($standings as $standing) {
            $standing->update(['ranking' => $rank++]);
        }
    }

    /**
     * Lấy số lũy thừa của 2 tiếp theo (bracket size)
     */
    private function getNextPowerOfTwo(int $n): int
    {
        if ($n <= 1) return 2;
        
        $power = 1;
        while ($power < $n) {
            $power *= 2;
        }
        
        return $power;
    }

    /**
     * Lấy danh sách round types dựa trên số đội
     */
    private function getRoundTypesForTeamCount(int $bracketSize): array
    {
        $numRounds = (int) log($bracketSize, 2);
        $types = ['final', 'semi_final', 'quarter_final', 'round_of_16', 'round_of_32'];
        
        return array_slice(array_reverse($types), 0, $numRounds);
    }

    /**
     * Lấy tên vòng đấu từ round type
     */
    private function getRoundNameForType(string $type): string
    {
        return match ($type) {
            'round_of_32' => 'Vòng 1/32',
            'round_of_16' => 'Vòng 1/16',
            'quarter_final' => 'Tứ kết',
            'semi_final' => 'Bán kết',
            'final' => 'Chung kết',
            'third_place' => 'Tranh hạng 3',
            'group_stage' => 'Vòng bảng',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}