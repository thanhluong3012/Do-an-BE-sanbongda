    # TOURNAMENT SCHEDULING LOGIC

    ## Tổng quan

    Hệ thống hỗ trợ 3 format giải đấu tự động sinh lịch thi đấu:
    - **Knockout** (Loại trực tiếp)
    - **Round Robin** (Vòng tròn tính điểm) 
    - **Group + Knockout** (Bảng đấu + Knockout)

    ## 1. KNOCKOUT (Loại trực tiếp)

    ### Đặc điểm
    - Thua 1 trận bị loại
    - Số đội phải là lũy thừa của 2: 2, 4, 8, 16, 32...
    - Số vòng đấu = log₂(n)

    ### Công thức tính

    ```
    Số vòng đấu = log₂(n)
    Số trận mỗi vòng = n / 2^vòng
    Tổng số trận = n - 1
    ```

    **Ví dụ với 8 đội:**
    - Vòng 1 (Tứ kết): 8/2 = 4 trận
    - Vòng 2 (Bán kết): 4/2 = 2 trận  
    - Vòng 3 (Chung kết): 2/2 = 1 trận
    - Tổng: 7 trận = 8 - 1

    ### Cấu trúc bracket

    ```
    Round 1          Round 2        Round 3
    Team 1 ─┐
            ├─ Winner1 ─┐
    Team 2 ─┘           │
                        ├─ Winner5 ─┐
    Team 3 ─┐           │           │
            ├─ Winner2 ─┘           │
    Team 4 ─┘                       ├─ Champion
                                    │
    Team 5 ─┐                       │
            ├─ Winner3 ─┐           │
    Team 6 ─┘           │           │
                        ├─ Winner6 ─┘
    Team 7 ─┐           │
            ├─ Winner4 ─┘
    Team 8 ─┘
    ```

    ### Logic code (TournamentController.php)

    **Function:** `generateKnockout()` (dòng 953-1080)

    ```php
    private function generateKnockout($tournament, $teams, $startDate)
    {
        // 1. Validate số đội phải là lũy thừa 2
        $teamCount = count($teams);
        if (($teamCount & ($teamCount - 1)) !== 0) {
            throw new \Exception('Knockout format requires power of 2 teams');
        }

        // 2. Tính số vòng đấu: log₂(n)
        $rounds = log($teamCount, 2);
        
        // 3. Tạo các vòng đấu
        for ($roundNumber = 1; $roundNumber <= $rounds; $roundNumber++) {
            $round = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_number' => $roundNumber,
                'round_name' => $this->getRoundName($roundNumber, $rounds),
            ]);
            
            // 4. Tính số trận trong vòng: n / 2^roundNumber
            $matchesInRound = $teamCount / pow(2, $roundNumber);
            
            // 5. Tạo trận đấu
            for ($i = 0; $i < $matchesInRound; $i++) {
                // Vòng 1: ghép đội thật
                // Vòng sau: ghép winner từ vòng trước
                TournamentMatch::create([...]);
            }
        }
    }
    ```

    **Helper function:** Đặt tên vòng đấu
    ```php
    private function getRoundName($roundNumber, $totalRounds)
    {
        $fromEnd = $totalRounds - $roundNumber + 1;
        switch($fromEnd) {
            case 1: return 'Chung kết';
            case 2: return 'Bán kết';
            case 3: return 'Tứ kết';
            default: return 'Vòng ' . $roundNumber;
        }
    }
    ```

    ### API Usage

    ```bash
    POST /api/tournaments/{id}/generate-schedule
    {
        "format": "knockout",
        "start_date": "2026-06-01"
    }
    ```

    ### Kết quả Database

    **tournament_rounds:**
    ```
    id | tournament_id | round_number | round_name
    1  | 1             | 1            | Vòng 1
    2  | 1             | 2            | Tứ kết
    3  | 1             | 3            | Bán kết
    4  | 1             | 4            | Chung kết
    ```

    **tournament_matches:**
    ```
    id | round_id | match_number | team1_id | team2_id | winner_id
    1  | 1        | 1            | 1        | 2        | NULL
    2  | 1        | 2            | 3        | 4        | NULL
    ...
    13 | 3        | 1            | NULL     | NULL     | NULL  (chờ winner)
    ```

    ---

    ## 2. ROUND ROBIN (Vòng tròn)

    ### Đặc điểm
    - Mọi đội đấu với nhau đúng 1 lần
    - Tính điểm: Thắng 3đ, Hòa 1đ, Thua 0đ
    - Xếp hạng theo: Điểm → Hiệu số → Bàn thắng

    ### Công thức tính

    ```
    Tổng số trận = n × (n-1) / 2
    Số lượt (nếu lẻ) = n
    Số lượt (nếu chẵn) = n - 1
    Số trận mỗi lượt = floor(n/2)
    ```

    **Ví dụ với 6 đội:**
    - Tổng trận: 6 × 5 / 2 = 15 trận
    - Số lượt: 6 - 1 = 5 lượt
    - Mỗi lượt: 6 / 2 = 3 trận

    **Ví dụ với 5 đội (lẻ):**
    - Tổng trận: 5 × 4 / 2 = 10 trận
    - Số lượt: 5 lượt
    - Mỗi lượt: 2 trận (1 đội nghỉ - bye)

    ### Thuật toán Berger Table (Circle Method)

    **Nguyên lý:** Xoay vòng các đội quanh 1 điểm cố định

    ```
    Lượt 1:  1 vs 6    2 vs 5    3 vs 4
    Lượt 2:  1 vs 5    6 vs 4    2 vs 3
    Lượt 3:  1 vs 4    5 vs 3    6 vs 2
    Lượt 4:  1 vs 3    4 vs 2    5 vs 6
    Lượt 5:  1 vs 2    3 vs 6    4 vs 5
    ```

    **Quy luật:**
    - Đội 1 cố định ở vị trí đầu
    - Các đội khác xoay ngược chiều kim đồng hồ

    ### Logic code (TournamentController.php)

    **Function:** `generateRoundRobin()` (dòng 1084-1155)

    ```php
    private function generateRoundRobin($tournament, $teams, $startDate)
    {
        $teamCount = count($teams);
        $rounds = ($teamCount % 2 == 0) ? $teamCount - 1 : $teamCount;
        
        // 1. Sinh cặp đấu bằng Berger algorithm
        $allPairings = $this->generateRoundRobinPairings($teams);
        
        // 2. Tạo vòng đấu và trận đấu
        foreach ($allPairings as $roundNumber => $pairings) {
            $round = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_number' => $roundNumber + 1,
                'round_name' => 'Lượt ' . ($roundNumber + 1),
            ]);
            
            foreach ($pairings as $index => $pairing) {
                TournamentMatch::create([
                    'tournament_round_id' => $round->id,
                    'match_number' => $index + 1,
                    'team1_id' => $pairing[0],
                    'team2_id' => $pairing[1],
                    'scheduled_time' => $matchTime,
                ]);
            }
        }
    }
    ```

    **Function:** `generateRoundRobinPairings()` (dòng 1328-1400)

    ```php
    private function generateRoundRobinPairings($teams)
    {
        $teamCount = count($teams);
        $rounds = ($teamCount % 2 == 0) ? $teamCount - 1 : $teamCount;
        
        // Thêm đội "bye" nếu số đội lẻ
        if ($teamCount % 2 != 0) {
            $teams[] = null; // Đội nghỉ
            $teamCount++;
        }
        
        $pairings = [];
        for ($round = 0; $round < $rounds; $round++) {
            $roundPairings = [];
            
            for ($i = 0; $i < $teamCount / 2; $i++) {
                $home = ($round + $i) % ($teamCount - 1);
                $away = ($teamCount - 1 - $i + $round) % ($teamCount - 1);
                
                if ($i == 0) {
                    $away = $teamCount - 1; // Đội cố định
                }
                
                // Bỏ qua trận có đội bye
                if ($teams[$home] !== null && $teams[$away] !== null) {
                    $roundPairings[] = [$teams[$home], $teams[$away]];
                }
            }
            
            $pairings[] = $roundPairings;
        }
        
        return $pairings;
    }
    ```

    ### API Usage

    ```bash
    POST /api/tournaments/{id}/generate-schedule
    {
        "format": "round_robin",
        "start_date": "2026-06-01"
    }
    ```

    ### Kết quả Database

    **tournament_rounds:**
    ```
    id | tournament_id | round_number | round_name
    1  | 1             | 1            | Lượt 1
    2  | 1             | 2            | Lượt 2
    3  | 1             | 3            | Lượt 3
    4  | 1             | 4            | Lượt 4
    5  | 1             | 5            | Lượt 5
    ```

    **tournament_matches (6 đội - 15 trận):**
    ```
    id | round_id | match_number | team1_id | team2_id | scheduled_time
    1  | 1        | 1            | 1        | 6        | 2026-06-01 09:00
    2  | 1        | 2            | 2        | 5        | 2026-06-01 11:00
    3  | 1        | 3            | 3        | 4        | 2026-06-01 13:00
    4  | 2        | 1            | 1        | 5        | 2026-06-02 09:00
    ...
    ```

    ---

    ## 3. GROUP + KNOCKOUT (Bảng + Knockout)

    ### Đặc điểm
    - Chia đội thành các bảng (groups)
    - Mỗi bảng đấu vòng tròn
    - Top đội mỗi bảng vào vòng knockout

    ### Công thức tính

    **Phase 1: Group Stage**
    ```
    Số bảng = groups
    Đội mỗi bảng = ceil(n / groups)
    Trận mỗi bảng = teams_per_group × (teams_per_group - 1) / 2
    ```

    **Phase 2: Knockout**
    ```
    Đội vào knockout = top_from_each × groups
    Số vòng knockout = log₂(qualified_teams)
    ```

    **Ví dụ với 8 đội, 2 bảng, top 2:**
    - Bảng A: 4 đội → 6 trận
    - Bảng B: 4 đội → 6 trận
    - Knockout: 4 đội (2 từ mỗi bảng) → 3 trận (bán kết + chung kết)
    - Tổng: 15 trận

    ### Logic code (TournamentController.php)

    **Function:** `generateGroupKnockout()` (dòng 1156-1325)

    ```php
    private function generateGroupKnockout($tournament, $teams, $startDate, $groups = 2, $topTeams = 2)
    {
        $teamCount = count($teams);
        $teamsPerGroup = ceil($teamCount / $groups);
        
        // === PHASE 1: GROUP STAGE ===
        
        // 1. Tạo các bảng đấu
        $tournamentGroups = [];
        for ($i = 0; $i < $groups; $i++) {
            $group = TournamentGroup::create([
                'tournament_id' => $tournament->id,
                'group_name' => chr(65 + $i), // A, B, C...
            ]);
            $tournamentGroups[] = $group;
        }
        
        // 2. Chia đội vào bảng (round-robin distribution)
        foreach ($teams as $index => $teamId) {
            $groupIndex = $index % $groups;
            TournamentGroupStanding::create([
                'tournament_group_id' => $tournamentGroups[$groupIndex]->id,
                'team_id' => $teamId,
                'points' => 0,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
            ]);
        }
        
        // 3. Sinh lịch vòng bảng cho mỗi bảng
        foreach ($tournamentGroups as $groupIndex => $group) {
            $groupTeams = TournamentGroupStanding::where('tournament_group_id', $group->id)
                ->pluck('team_id')->toArray();
            
            // Dùng thuật toán round-robin
            $pairings = $this->generateRoundRobinPairings($groupTeams);
            
            foreach ($pairings as $roundNumber => $roundPairings) {
                $round = TournamentRound::create([
                    'tournament_id' => $tournament->id,
                    'round_number' => $roundNumber + 1,
                    'round_name' => 'Bảng ' . $group->group_name . ' - Lượt ' . ($roundNumber + 1),
                    'is_knockout' => false,
                ]);
                
                foreach ($roundPairings as $index => $pairing) {
                    TournamentMatch::create([...]);
                }
            }
        }
        
        // === PHASE 2: KNOCKOUT STAGE ===
        
        // 4. Tạo vòng knockout cho top teams
        $qualifiedTeams = $topTeams * $groups;
        
        // Validate phải là lũy thừa 2
        if (($qualifiedTeams & ($qualifiedTeams - 1)) !== 0) {
            throw new \Exception('Qualified teams must be power of 2');
        }
        
        $knockoutRounds = log($qualifiedTeams, 2);
        
        for ($roundNumber = 1; $roundNumber <= $knockoutRounds; $roundNumber++) {
            $round = TournamentRound::create([
                'tournament_id' => $tournament->id,
                'round_number' => $lastGroupRound + $roundNumber,
                'round_name' => $this->getRoundName($roundNumber, $knockoutRounds),
                'is_knockout' => true,
            ]);
            
            // Tạo trận knockout (team1/team2 = NULL, chờ kết quả bảng)
            $matchesInRound = $qualifiedTeams / pow(2, $roundNumber);
            for ($i = 0; $i < $matchesInRound; $i++) {
                TournamentMatch::create([
                    'team1_id' => null, // Chờ xếp hạng bảng
                    'team2_id' => null,
                ]);
            }
        }
    }
    ```

    ### Quy tắc xếp hạng bảng

    ```php
    ORDER BY 
        points DESC,           -- Điểm số
        goal_difference DESC,  -- Hiệu số bàn thắng
        goals_for DESC,        -- Tổng bàn thắng
        head_to_head          -- Đối đầu trực tiếp (nếu cần)
    ```

    ### API Usage

    ```bash
    POST /api/tournaments/{id}/generate-schedule
    {
        "format": "group_knockout",
        "start_date": "2026-06-01",
        "groups": 2,
        "top_teams": 2
    }
    ```

    ### Kết quả Database

    **tournament_groups:**
    ```
    id | tournament_id | group_name
    1  | 1             | A
    2  | 1             | B
    ```

    **tournament_group_standings:**
    ```
    id | group_id | team_id | points | played | won | drawn | lost | goals_for | goals_against
    1  | 1        | 1       | 0      | 0      | 0   | 0     | 0    | 0         | 0
    2  | 1        | 2       | 0      | 0      | 0   | 0     | 0    | 0         | 0
    3  | 1        | 3       | 0      | 0      | 0   | 0     | 0    | 0         | 0
    4  | 1        | 4       | 0      | 0      | 0   | 0     | 0    | 0         | 0
    5  | 2        | 5       | 0      | 0      | 0   | 0     | 0    | 0         | 0
    ...
    ```

    **tournament_rounds:**
    ```
    id | tournament_id | round_number | round_name              | is_knockout
    1  | 1             | 1            | Bảng A - Lượt 1         | 0
    2  | 1             | 2            | Bảng A - Lượt 2         | 0
    3  | 1             | 3            | Bảng A - Lượt 3         | 0
    4  | 1             | 4            | Bảng B - Lượt 1         | 0
    5  | 1             | 5            | Bảng B - Lượt 2         | 0
    6  | 1             | 6            | Bảng B - Lượt 3         | 0
    7  | 1             | 7            | Bán kết                 | 1
    8  | 1             | 8            | Chung kết               | 1
    ```

    ---

    ## So sánh 3 Format

    | Feature | Knockout | Round Robin | Group + Knockout |
    |---------|----------|-------------|------------------|
    | Số trận | n - 1 | n×(n-1)/2 | Nhiều nhất |
    | Thời gian | Ngắn nhất | Dài nhất | Trung bình |
    | Công bằng | Thấp | Cao nhất | Cao |
    | Kịch tính | Cao | Trung bình | Cân bằng |
    | Yêu cầu đội | 2^n | Bất kỳ | Linh hoạt |

    ---

    ## API Endpoints

    ### 1. Tạo lịch thi đấu
    ```
    POST /api/tournaments/{id}/generate-schedule
    ```

    **Request Body:**
    ```json
    {
        "format": "knockout|round_robin|group_knockout",
        "start_date": "2026-06-01",
        "groups": 2,         // Chỉ cho group_knockout
        "top_teams": 2       // Chỉ cho group_knockout
    }
    ```

    **Response:**
    ```json
    {
        "success": true,
        "message": "Đã tạo lịch thi đấu",
        "data": {
            "rounds": 3,
            "matches": 7,
            "start_date": "2026-06-01",
            "end_date": "2026-06-03"
        }
    }
    ```

    ### 2. Xem lịch thi đấu
    ```
    GET /api/tournaments/{id}/schedule
    ```

    ### 3. Cập nhật kết quả trận đấu
    ```
    PUT /api/tournaments/matches/{id}/result
    ```

    **Request Body:**
    ```json
    {
        "team1_score": 2,
        "team2_score": 1
    }
    ```

    ---

    ## Database Schema

    ### tournament_rounds
    ```sql
    id              BIGINT PRIMARY KEY
    tournament_id   BIGINT FK -> tournaments.id
    round_number    INT
    round_name      VARCHAR
    is_knockout     BOOLEAN (group_knockout format)
    created_at      TIMESTAMP
    ```

    ### tournament_matches
    ```sql
    id                  BIGINT PRIMARY KEY
    tournament_round_id BIGINT FK -> tournament_rounds.id
    match_number        INT
    team1_id            BIGINT FK -> teams.id (nullable)
    team2_id            BIGINT FK -> teams.id (nullable)
    scheduled_time      TIMESTAMP
    team1_score         INT (nullable)
    team2_score         INT (nullable)
    winner_id           BIGINT FK -> teams.id (nullable)
    status              ENUM('pending', 'completed')
    created_at          TIMESTAMP
    ```

    ### tournament_groups (chỉ group_knockout)
    ```sql
    id              BIGINT PRIMARY KEY
    tournament_id   BIGINT FK -> tournaments.id
    group_name      VARCHAR (A, B, C...)
    created_at      TIMESTAMP
    ```

    ### tournament_group_standings
    ```sql
    id                  BIGINT PRIMARY KEY
    tournament_group_id BIGINT FK -> tournament_groups.id
    team_id             BIGINT FK -> teams.id
    points              INT DEFAULT 0
    played              INT DEFAULT 0
    won                 INT DEFAULT 0
    drawn               INT DEFAULT 0
    lost                INT DEFAULT 0
    goals_for           INT DEFAULT 0
    goals_against       INT DEFAULT 0
    goal_difference     INT DEFAULT 0
    created_at          TIMESTAMP
    ```

    ---

    ## Testing Examples

    ### Test Knockout với 8 đội
    ```bash
    POST /api/tournaments/1/generate-schedule
    {
        "format": "knockout",
        "start_date": "2026-06-01"
    }

    Expected:
    - 3 vòng (log₂(8) = 3)
    - 7 trận (8 - 1)
    - Vòng 1: 4 trận
    - Vòng 2: 2 trận
    - Vòng 3: 1 trận
    ```

    ### Test Round Robin với 6 đội
    ```bash
    POST /api/tournaments/2/generate-schedule
    {
        "format": "round_robin",
        "start_date": "2026-06-05"
    }

    Expected:
    - 5 lượt (6 - 1)
    - 15 trận (6×5/2)
    - Mỗi lượt: 3 trận
    ```

    ### Test Group + Knockout với 8 đội
    ```bash
    POST /api/tournaments/3/generate-schedule
    {
        "format": "group_knockout",
        "start_date": "2026-06-10",
        "groups": 2,
        "top_teams": 2
    }

    Expected:
    - Group Stage: 2 bảng × 6 trận = 12 trận
    - Knockout Stage: 3 trận (bán kết + chung kết)
    - Tổng: 15 trận
    ```

    ---

    ## Error Handling

    ### Common Errors

    **1. Knockout: Số đội không phải lũy thừa 2**
    ```json
    {
        "success": false,
        "message": "Knockout format requires power of 2 teams (2, 4, 8, 16...)"
    }
    ```

    **2. Group + Knockout: Số đội qualified không phải lũy thừa 2**
    ```json
    {
        "success": false,
        "message": "Qualified teams must be power of 2. Current: groups=3, top=2 = 6 teams"
    }
    ```

    **3. Không đủ đội đăng ký**
    ```json
    {
        "success": false,
        "message": "Need at least 2 registered teams"
    }
    ```

    ---

    ## Performance Notes

    - **Knockout**: O(n) complexity, nhanh nhất
    - **Round Robin**: O(n²) complexity, chậm với nhiều đội
    - **Group + Knockout**: O(n²/g + k) với g = groups, k = knockout teams

    **Khuyến nghị:**
    - Knockout: < 32 đội
    - Round Robin: < 12 đội
    - Group + Knockout: 8-64 đội

    ---

    ## Maintenance

    ### Code Location
    - Controller: `app/Http/Controllers/Api/TournamentController.php`
    - Models: `app/Models/Tournament*.php`
    - Migrations: `database/migrations/2026_03_*_tournament_*.php`

    ### Key Functions
    - `generateSchedule()` - Entry point (line 634)
    - `generateKnockout()` - Knockout logic (line 953)
    - `generateRoundRobin()` - Round robin logic (line 1084)
    - `generateGroupKnockout()` - Hybrid format (line 1156)
    - `generateRoundRobinPairings()` - Berger algorithm (line 1328)

    ### Testing
    ```bash
    php artisan test --filter TournamentSchedulingTest
    ```

    ---

    **Document Version:** 1.0  
    **Last Updated:** May 12, 2026  
    **Author:** Backend Development Team
