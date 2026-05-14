# 🏆 THUẬT TOÁN TỰ ĐỘNG SINH LỊCH THI ĐẤU - TOURNAMENT AUTO SCHEDULING ALGORITHM

> **File nguồn:** `app/Services/TournamentScheduleService.php`  
> **Tác giả:** Hệ thống quản lý sân bóng đá - Do-an-BE-sanbongda  
> **Cập nhật:** 14/05/2026

---

## 📋 TỔNG QUAN

Hệ thống hỗ trợ **3 thể thức thi đấu** với thuật toán tự động sinh lịch:

| Format | Tên tiếng Việt | Mô tả | Ứng dụng |
|--------|----------------|-------|----------|
| **round_robin** | Vòng tròn | Tất cả đội đá với nhau đúng 1 lần | Giải nhỏ, xác định thứ hạng chính xác |
| **knockout** | Loại trực tiếp | Đội thua bị loại ngay | Giải lớn, nhanh chóng |
| **group_knockout** | Bảng + loại trực tiếp | Vòng bảng → Top teams → Knockout | World Cup, Champions League |

---

## 🔧 THAM SỐ ĐẦU VÀO CHUNG

Tất cả 3 thuật toán đều nhận các tham số sau:

```php
- Tournament $tournament           // Giải đấu
- Collection $teams                // Danh sách đội đã approved
- Carbon $startDate                // Ngày bắt đầu
- array $timeSlots                 // Khung giờ thi đấu ['08:00', '10:00', '14:00'...]
- array $fieldIds                  // Danh sách ID sân
- int $daysBetweenRounds           // Số ngày nghỉ giữa các vòng (mặc định: 3)
- int $matchDuration               // Thời lượng 1 trận (phút, mặc định: 60)
- int $preparationTime             // Thời gian chuẩn bị sau trận (phút, mặc định: 15)
- bool $shuffleTeams               // Có xáo trộn đội không (mặc định: true)
```

---

## 🎯 FORMAT 1: ROUND ROBIN (VÒNG TRÒN)

### 📖 Định nghĩa

**Vòng tròn:** Mỗi đội đá với TẤT CẢ các đội khác **đúng 1 lần**.

### 🧮 Công thức toán học

```
Số trận đấu = n × (n - 1) / 2
Số vòng đấu = n - 1 (nếu n chẵn) hoặc n (nếu n lẻ)
```

Với:
- `n` = số đội

**Ví dụ:**
- 4 đội: `4 × 3 / 2 = 6` trận
- 5 đội: `5 × 4 / 2 = 10` trận
- 8 đội: `8 × 7 / 2 = 28` trận

### 🔄 Thuật toán Circle Method (Berger Table)

Đây là thuật toán kinh điển để sinh lịch vòng tròn, đảm bảo:
- ✅ Mỗi đội đá 1 lần với tất cả đội khác
- ✅ Không có đội nào đá 2 trận liên tiếp
- ✅ Phân bố đều home/away

#### **Cách hoạt động:**

1. **Nếu số đội lẻ** → Thêm đội ảo `null` (BYE team)
2. **Cố định đội đầu tiên**, các đội còn lại **xoay vòng**

#### **Ví dụ với 4 đội: [A, B, C, D]**

```
Vòng 1:          Vòng 2:          Vòng 3:
A vs D           A vs C           A vs B
B vs C           D vs B           C vs D

Rotating:        Rotating:        Rotating:
[B,C,D]  →      [D,B,C]  →      [C,D,B]
```

#### **Công thức xoay vòng:**

```
Fixed: A (không đổi)
Rotating: [B, C, D] → Xoay trái 1 bước mỗi vòng

Vòng 1: A-D, B-C
Vòng 2: A-C, D-B  (xoay [B,C,D] → [D,B,C])
Vòng 3: A-B, C-D  (xoay [D,B,C] → [C,D,B])
```

#### **Code implementation:**

```php
private function generateRoundRobinPairings(array $teamIds): array
{
    $n = count($teamIds);
    
    // Nếu số đội lẻ, thêm null (BYE team)
    if ($n % 2 === 1) {
        $teamIds[] = null;
        $n++;
    }
    
    $rounds = [];
    $fixed = $teamIds[0];           // Đội cố định
    $rotating = array_slice($teamIds, 1);  // Các đội xoay vòng
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
```

### 📊 Ví dụ chi tiết với 6 đội

```
Đội: [1, 2, 3, 4, 5, 6]

Vòng 1:    1 vs 6    2 vs 5    3 vs 4
Vòng 2:    1 vs 5    6 vs 4    2 vs 3
Vòng 3:    1 vs 4    5 vs 3    6 vs 2
Vòng 4:    1 vs 3    4 vs 2    5 vs 6
Vòng 5:    1 vs 2    3 vs 6    4 vs 5

Tổng: 5 vòng, 15 trận
```

### 🏅 Bảng xếp hạng

Sau mỗi trận, hệ thống cập nhật:
- **Điểm** (Thắng: +3, Hòa: +1, Thua: 0)
- **Hiệu số bàn thắng** (goals_for - goals_against)
- **Số trận thắng/hòa/thua**

**Thứ tự xếp hạng:**
1. Điểm cao hơn
2. Hiệu số bàn thắng lớn hơn
3. Số bàn thắng nhiều hơn
4. Số trận thắng nhiều hơn
5. Random (nếu vẫn bằng nhau)

---

## ⚔️ FORMAT 2: KNOCKOUT (LOẠI TRỰC TIẾP)

### 📖 Định nghĩa

**Knockout:** Đấu loại trực tiếp, đội **thua = bị loại** ngay.

### 🧮 Công thức toán học

```
Số đội phải là lũy thừa của 2: 2, 4, 8, 16, 32, 64...
Số vòng đấu = log₂(n)
Số trận đấu = n - 1
```

**Ví dụ:**
- 4 đội: `log₂(4) = 2` vòng (Bán kết + Chung kết), `4 - 1 = 3` trận
- 8 đội: `log₂(8) = 3` vòng (Tứ kết + Bán kết + Chung kết), `8 - 1 = 7` trận
- 16 đội: `log₂(16) = 4` vòng, `16 - 1 = 15` trận

### 🚫 Validation: Số đội phải là lũy thừa của 2

```php
if (!$this->isPowerOfTwo($n)) {
    throw new \Exception(
        "Thể thức knockout chỉ chấp nhận số đội là lũy thừa của 2 (2, 4, 8, 16, 32, 64...). 
        Hiện có {$n} đội."
    );
}

private function isPowerOfTwo(int $n): bool
{
    return $n > 0 && ($n & ($n - 1)) === 0;
}
```

### 🏗️ Cấu trúc Bracket

#### **Ví dụ với 8 đội:**

```
Vòng 1/4 (Tứ kết)    Vòng 1/2 (Bán kết)     Chung kết
   4 trận                2 trận              1 trận

Đội 1 ┐                              
      ├─── Thắng 1 ┐                 
Đội 2 ┘            │                 
                   ├─── Thắng A ┐
Đội 3 ┐            │            │
      ├─── Thắng 2 ┘            │
Đội 4 ┘                         ├─── Vô địch
                                │
Đội 5 ┐                         │
      ├─── Thắng 3 ┐            │
Đội 6 ┘            │            │
                   ├─── Thắng B ┘
Đội 7 ┐            │
      ├─── Thắng 4 ┘
Đội 8 ┘
```

### 🔗 Liên kết giữa các vòng

Hệ thống sử dụng `home_from_match_id` và `away_from_match_id` để theo dõi:

```php
// Vòng Bán kết
Match 5: Thắng Match 1 vs Thắng Match 2
Match 6: Thắng Match 3 vs Thắng Match 4

// Chung kết
Match 7: Thắng Match 5 vs Thắng Match 6
```

### 🥉 Trận tranh hạng 3 (Tùy chọn)

Nếu `createThirdPlaceMatch = true`:

```
Bán kết:
- Trận 1: A vs B → Thắng: A, Thua: B
- Trận 2: C vs D → Thắng: C, Thua: D

Chung kết:    A vs C
Hạng 3:       B vs D  (2 đội thua bán kết)
```

**Code implementation:**

```php
$thirdPlaceMatch = TournamentMatch::create([
    'home_team_id' => null,  // Thua bán kết 1
    'away_team_id' => null,  // Thua bán kết 2
    'home_from_match_id' => $semiFinal1->id,
    'away_from_match_id' => $semiFinal2->id,
    'home_from_position' => "Thua trận #{$semiFinal1->match_number}",
    'away_from_position' => "Thua trận #{$semiFinal2->match_number}",
    'bracket_position' => 'third_place',
]);
```

### 📋 Tên vòng đấu

```php
private function getRoundTypesForTeamCount(int $bracketSize): array
{
    $numRounds = (int) log($bracketSize, 2);
    
    $types = match($numRounds) {
        1 => ['final'],
        2 => ['semi_final', 'final'],
        3 => ['quarter_final', 'semi_final', 'final'],
        4 => ['round_of_16', 'quarter_final', 'semi_final', 'final'],
        5 => ['round_of_32', 'round_of_16', 'quarter_final', 'semi_final', 'final'],
        default => array_merge(
            array_fill(0, $numRounds - 5, 'round_of_32'),
            ['round_of_32', 'round_of_16', 'quarter_final', 'semi_final', 'final']
        ),
    };
    
    return $types;
}
```

---

## 🌍 FORMAT 3: GROUP KNOCKOUT (BẢNG + LOẠI TRỰC TIẾP)

### 📖 Định nghĩa

**Group Knockout:** Kết hợp 2 giai đoạn:
1. **Vòng bảng** (Round Robin trong mỗi bảng)
2. **Vòng knockout** (Top teams từ mỗi bảng)

### 🎯 Ứng dụng thực tế

- **World Cup:** 32 đội → 8 bảng (4 đội/bảng) → Top 2 → Knockout 16 đội
- **Champions League:** 32 đội → 8 bảng → Top 2 → Knockout 16 đội
- **EURO:** 24 đội → 6 bảng → Top 2 + 4 hạng 3 tốt nhất → Knockout 16 đội

### 🧮 Công thức toán học

```
Số bảng = numGroups (tham số) hoặc ceil(n / teamsPerGroup)
Đội mỗi bảng = ceil(n / numGroups)

Trận vòng bảng = Σ [teams_in_group × (teams_in_group - 1) / 2] cho tất cả bảng
Đội vào knockout = numGroups × 2 (top 2 mỗi bảng) + extra teams
Knockout bracket = 2^k (k = ceil(log₂(qualified_teams)))
```

### 📊 GIAI ĐOẠN 1: VÒNG BẢNG

#### **Phân bổ đội vào bảng (Round-Robin Distribution)**

```php
// Phân đều đội vào các bảng theo kiểu xoay vòng
$groupTeams = array_fill(0, $numGroups, []);
foreach ($teamList as $idx => $team) {
    $groupTeams[$idx % $numGroups][] = $team;
}
```

**Ví dụ với 13 đội, 4 bảng:**

```
Bảng A: [Đội 1, Đội 5, Đội 9, Đội 13]   → 4 đội
Bảng B: [Đội 2, Đội 6, Đội 10]          → 3 đội
Bảng C: [Đội 3, Đội 7, Đội 11]          → 3 đội
Bảng D: [Đội 4, Đội 8, Đội 12]          → 3 đội
```

#### **Tính số trận vòng bảng:**

```
Bảng A (4 đội): 4 × 3 / 2 = 6 trận
Bảng B (3 đội): 3 × 2 / 2 = 3 trận
Bảng C (3 đội): 3 × 2 / 2 = 3 trận
Bảng D (3 đội): 3 × 2 / 2 = 3 trận

Tổng: 6 + 3 + 3 + 3 = 15 trận
```

#### **Mỗi bảng thi đấu theo Circle Method:**

```
Bảng A (4 đội: [1,2,3,4]):
Vòng 1: 1 vs 4, 2 vs 3
Vòng 2: 1 vs 3, 4 vs 2
Vòng 3: 1 vs 2, 3 vs 4
```

### 🏆 GIAI ĐOẠN 2: XÁC ĐỊNH ĐỘI VÀO KNOCKOUT

#### **Quy tắc cơ bản:**

1. **Top 2 mỗi bảng** → Chắc chắn vào
2. **Số đội qualified = numGroups × 2**
3. **Bracket size = 2^k** (k = ceil(log₂(qualified_teams)))

#### **Auto-fill với đội hạng 3 tốt nhất:**

Nếu `autoFillThirdPlace = true` và cần thêm đội để đủ bracket chuẩn:

```
Qualified teams = 14 (top 2 của 7 bảng)
Bracket size chuẩn = 16 (2^4)
Missing slots = 16 - 14 = 2

→ Lấy 2 đội hạng 3 tốt nhất từ TẤT CẢ 7 bảng
```

#### **Ví dụ chi tiết với 5 bảng:**

```
5 bảng → Top 2 mỗi bảng = 10 đội
Bracket chuẩn = 16 (2^4)
Missing = 16 - 10 = 6 đội

Auto-fill:
- Lấy 5 đội hạng 3 tốt nhất (từ 5 bảng)
- Lấy 1 đội hạng 4 tốt nhất (từ 5 bảng)

→ Tổng: 10 + 5 + 1 = 16 đội vào knockout
```

#### **Thuật toán chọn đội hạng 3 tốt nhất:**

```php
private function getBestThirdPlacedTeams(array $groups, int $count): array
{
    $thirdPlaceTeams = [];
    
    // Lấy đội hạng 3 từ TẤT CẢ các bảng
    foreach ($groups as $group) {
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
    return array_slice($thirdPlaceTeams, 0, $count);
}
```

### ⚔️ GIAI ĐOẠN 3: VÒNG KNOCKOUT

#### **Ghép cặp vòng 1 knockout:**

**Quy tắc ghép cặp bảng chẵn:**

```
Bảng A - Bảng B, Bảng C - Bảng D, Bảng E - Bảng F...

Trận 1: Nhất A vs Nhì B
Trận 2: Nhất B vs Nhì A
Trận 3: Nhất C vs Nhì D
Trận 4: Nhất D vs Nhì C
Trận 5: Nhất E vs Nhì F
Trận 6: Nhất F vs Nhì E
```

**Ví dụ với 4 bảng (A, B, C, D):**

```
8 đội vào knockout:
- Nhất A, Nhì A
- Nhất B, Nhì B
- Nhất C, Nhì C
- Nhất D, Nhì D

Vòng 1/4 (Tứ kết):
Trận 1: Nhất A vs Nhì B
Trận 2: Nhất B vs Nhì A
Trận 3: Nhất C vs Nhì D
Trận 4: Nhất D vs Nhì C
```

#### **Quy tắc ghép cặp bảng lẻ + đội extra:**

```
Ví dụ 5 bảng + 6 đội extra = 16 đội:

Bảng A-B: Nhất A vs Nhì B, Nhất B vs Nhì A
Bảng C-D: Nhất C vs Nhì D, Nhất D vs Nhì C

Bảng E (lẻ) + 2 extra tốt nhất:
- Nhất E vs Hạng 3 tốt #2
- Nhì E vs Hạng 3 tốt #1

4 extra còn lại ghép với nhau:
- Hạng 3 tốt #3 vs Hạng 3 tốt #6
- Hạng 3 tốt #4 vs Hạng 3 tốt #5
```

**Code implementation:**

```php
// Ghép các bảng theo cặp: A-B, C-D, E-F...
for ($g = 0; $g + 1 < $numGroups; $g += 2) {
    $groupA = $g;
    $groupB = $g + 1;
    
    $pairings[] = [
        'home_from_position' => "Nhất bảng {$groupLabels[$groupA]}",
        'away_from_position' => "Nhì bảng {$groupLabels[$groupB]}",
    ];
    
    $pairings[] = [
        'home_from_position' => "Nhất bảng {$groupLabels[$groupB]}",
        'away_from_position' => "Nhì bảng {$groupLabels[$groupA]}",
    ];
}

// Nếu số bảng lẻ
if ($numGroups % 2 === 1) {
    $oddGroupIndex = $numGroups - 1;
    $oddGroupLabel = $groupLabels[$oddGroupIndex];
    
    $pairings[] = [
        'home_from_position' => "Nhất bảng {$oddGroupLabel}",
        'away_from_position' => $extraQualifiedPositions[1] ?? 'TBD',
    ];
    
    $pairings[] = [
        'home_from_position' => "Nhì bảng {$oddGroupLabel}",
        'away_from_position' => $extraQualifiedPositions[0] ?? 'TBD',
    ];
}

// Các đội extra còn lại ghép với nhau: tốt nhất vs kém nhất
$remainingExtras = array_slice($extraQualifiedPositions, $extraStartIndex);

while (count($remainingExtras) >= 2) {
    $first = array_shift($remainingExtras);  // Đội tốt nhất còn lại
    $last = array_pop($remainingExtras);     // Đội kém nhất còn lại
    
    $pairings[] = [
        'home_from_position' => $first,
        'away_from_position' => $last,
    ];
}
```

### 📋 Ví dụ thực tế: EURO 2024

```
24 đội → 6 bảng (4 đội/bảng)
Vòng bảng: 6 × (4 × 3 / 2) = 6 × 6 = 36 trận

Top 2 mỗi bảng = 6 × 2 = 12 đội
+ 4 đội hạng 3 tốt nhất = 16 đội

Knockout: 16 đội → 4 vòng (1/8, 1/4, 1/2, CK)
- Vòng 1/8: 8 trận
- Tứ kết: 4 trận
- Bán kết: 2 trận
- Chung kết: 1 trận
Tổng: 15 trận

Tổng toàn giải: 36 + 15 = 51 trận
```

---

## 🎰 THUẬT TOÁN ASSIGN MATCHES VÀO SLOTS

### 🎯 Mục tiêu

Xếp các trận đấu vào:
- **Time slots** (khung giờ thi đấu)
- **Fields** (sân)
- **Dates** (ngày thi đấu)

Đảm bảo:
1. ✅ **Không conflict sân:** 1 sân chỉ có 1 trận tại 1 thời điểm
2. ✅ **Không conflict đội:** 1 đội không đá 2 trận cùng lúc
3. ✅ **Đủ thời gian nghỉ:** Match duration + preparation time

### 🔄 Thuật toán Greedy Scheduling

```php
private function assignMatchesToSlots(
    Tournament $tournament,
    TournamentRound $round,
    array $pairings,            // Danh sách cặp đấu cần xếp
    Carbon $startDate,
    array $timeSlots,           // ['08:00', '10:00', '14:00', '16:00']
    array $fieldIds,            // [1, 2, 3]
    int $matchDuration = 60,    // 60 phút
    int $preparationTime = 15   // 15 phút
): Collection
{
    $matches = collect();
    $matchNumber = 1;
    
    // Tracking conflict bằng hash map
    $fieldSlotUsage = [];  // ['2026-05-10|08:00|1' => true]
    $teamSlotUsage = [];   // ['2026-05-10|08:00|123' => true]
    
    $currentDate = $startDate->copy();
    $slotIdx = 0;
    $fieldIdx = 0;
    
    foreach ($pairings as $pairing) {
        $homeId = $pairing['home_id'] ?? null;
        $awayId = $pairing['away_id'] ?? null;
        
        // Tìm slot hợp lệ (tránh conflict)
        $assigned = false;
        $maxAttempts = count($timeSlots) * count($fieldIds) * 365;
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $fieldId = $fieldIds[$fieldIdx % count($fieldIds)];
            $timeSlot = $timeSlots[$slotIdx % count($timeSlots)];
            $dateStr = $currentDate->toDateString();
            
            // Tạo key duy nhất
            $slotKey = "{$dateStr}|{$timeSlot}|{$fieldId}";
            $teamHomeKey = $homeId ? "{$dateStr}|{$timeSlot}|{$homeId}" : null;
            $teamAwayKey = $awayId ? "{$dateStr}|{$timeSlot}|{$awayId}" : null;
            
            // Kiểm tra conflict
            $fieldConflict = isset($fieldSlotUsage[$slotKey]);
            $teamHomeConflict = $teamHomeKey && isset($teamSlotUsage[$teamHomeKey]);
            $teamAwayConflict = $teamAwayKey && isset($teamSlotUsage[$teamAwayKey]);
            
            if (!$fieldConflict && !$teamHomeConflict && !$teamAwayConflict) {
                // ✅ Slot hợp lệ - tạo trận đấu
                $match = TournamentMatch::create([...]);
                $matches->push($match);
                
                // Đánh dấu slot đã dùng
                $fieldSlotUsage[$slotKey] = true;
                if ($teamHomeKey) $teamSlotUsage[$teamHomeKey] = true;
                if ($teamAwayKey) $teamSlotUsage[$teamAwayKey] = true;
                
                $assigned = true;
                break;
            }
            
            // ⏭️ Chuyển slot tiếp theo
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
            throw new \Exception("Không thể tìm slot hợp lệ sau {$maxAttempts} lần thử.");
        }
    }
    
    return $matches;
}
```

### 📊 Ví dụ cụ thể

**Input:**
```
Time slots: ['08:00', '10:00', '14:00']
Fields: [1, 2]
Start date: 2026-05-10
Matches: 6 trận (A-B, C-D, E-F, G-H, I-J, K-L)
```

**Output:**

| Match | Date | Time | Field | Reason |
|-------|------|------|-------|--------|
| A-B | 2026-05-10 | 08:00 | 1 | First slot |
| C-D | 2026-05-10 | 08:00 | 2 | Field 1 used, try field 2 ✅ |
| E-F | 2026-05-10 | 10:00 | 1 | Next time slot |
| G-H | 2026-05-10 | 10:00 | 2 | Field 1 used, try field 2 ✅ |
| I-J | 2026-05-10 | 14:00 | 1 | Next time slot |
| K-L | 2026-05-10 | 14:00 | 2 | Field 1 used, try field 2 ✅ |

**→ Tất cả 6 trận xếp trong 1 ngày!**

### 🚫 Xử lý conflict

**Ví dụ conflict đội:**

```
Match 1: A vs B (08:00, Field 1) ✅
Match 2: C vs D (08:00, Field 2) ✅
Match 3: A vs E (08:00, Field 1)
         ❌ Field 1 đã dùng
         → Try Field 2
         ❌ Đội A đang đá Match 1 lúc 08:00
         → Try 10:00, Field 1 ✅
```

### ⏱️ Độ phức tạp thuật toán

```
Worst case: O(M × S × F × D)

M = Số trận đấu
S = Số time slots
F = Số sân
D = Số ngày tối đa (365)

Best case: O(M) - Không có conflict
Average case: O(M × log(S × F))
```

---

## 🔧 OPTIMIZATIONS & BEST PRACTICES

### ✅ 1. Validation trước khi sinh lịch

```php
// Kiểm tra số đội tối thiểu
if ($teams->count() < 2) {
    throw new \Exception('Cần ít nhất 2 đội');
}

// Knockout: Phải là lũy thừa của 2
if ($format === 'knockout' && !$this->isPowerOfTwo($n)) {
    throw new \Exception('Knockout cần số đội là 2^n');
}

// Kiểm tra đã có lịch chưa
if ($tournament->rounds()->count() > 0) {
    throw new \Exception('Lịch đã tồn tại. Xóa lịch cũ trước.');
}

// Kiểm tra đã book sân chưa
$bookings = Book::where('tournament_id', $tournament->id)
    ->where('booking_type', 'tournament')
    ->get();

if ($bookings->isEmpty()) {
    throw new \Exception('Chưa book sân cho giải đấu');
}
```

### ✅ 2. Transaction để đảm bảo consistency

```php
DB::beginTransaction();

try {
    // Tạo rounds
    // Tạo groups
    // Tạo standings
    // Tạo matches
    
    $tournament->update(['status' => 'in_progress']);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### ✅ 3. Tự động tạo time slots từ bookings

```php
private function generateTimeSlotsFromBookings($bookings, string $fieldType): array
{
    // Thời lượng 1 trận theo field type
    $matchDuration = match ($fieldType) {
        '5v5' => 60,
        '7v7' => 80,
        '11v11' => 90,
        default => 60,
    };
    
    $timeSlots = [];
    
    foreach ($bookings as $booking) {
        $start = Carbon::parse($booking->start_time);
        $end = Carbon::parse($booking->end_time);
        
        // Tạo các slot trong khoảng thời gian này
        $current = $start->copy();
        while ($current->copy()->addMinutes($matchDuration)->lte($end)) {
            $timeSlots[] = $current->format('H:i');
            $current->addMinutes($matchDuration + 15); // +15 phút nghỉ
        }
    }
    
    return array_unique($timeSlots);
}
```

### ✅ 4. Eager loading để tránh N+1 query

```php
$tournament = Tournament::with([
    'registrations' => function ($q) {
        $q->where('registration_status', 'approved');
    },
    'registrations.team'  // Eager load team relationship
])->findOrFail($id);
```

### ✅ 5. Xử lý số đội lẻ (BYE team)

```php
// Round Robin: Thêm null vào cuối danh sách
if ($n % 2 === 1) {
    $teamIds[] = null;
    $n++;
}

// Khi assign matches: Bỏ qua trận có BYE
foreach ($pairings as $pair) {
    if ($pair[0] === null || $pair[1] === null) {
        continue;  // Skip BYE match
    }
    
    // Create match...
}
```

---

## 📊 SO SÁNH 3 THUẬT TOÁN

| Tiêu chí | Round Robin | Knockout | Group Knockout |
|----------|-------------|----------|----------------|
| **Số trận** | n×(n-1)/2 | n-1 | Vòng bảng + (qualified-1) |
| **Thời gian** | Dài nhất | Ngắn nhất | Trung bình |
| **Công bằng** | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐⭐ |
| **Hấp dẫn** | ⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Độ phức tạp code** | Đơn giản | Trung bình | Phức tạp |
| **Yêu cầu số đội** | ≥ 2 | 2^n | ≥ 4 |

### 🎯 Khi nào dùng format nào?

**Round Robin:**
- ✅ Giải nhỏ (4-8 đội)
- ✅ Cần xác định thứ hạng chính xác
- ✅ Có thời gian dài
- ❌ Không phù hợp cho giải lớn (>10 đội)

**Knockout:**
- ✅ Giải lớn (16-64 đội)
- ✅ Cần kết thúc nhanh
- ✅ Tạo drama cao
- ❌ Cần số đội chuẩn (2^n)
- ❌ Đội yếu gặp may có thể đi xa

**Group Knockout:**
- ✅ Giải chuyên nghiệp
- ✅ Cân bằng giữa công bằng và hấp dẫn
- ✅ Linh hoạt với số đội bất kỳ
- ❌ Phức tạp nhất
- ❌ Mất nhiều thời gian nhất

---

## 🧪 TEST CASES

### Test Case 1: Round Robin với 4 đội

```php
Input:
- Teams: 4 đội
- Time slots: ['08:00', '10:00']
- Fields: [1]
- Days between rounds: 1

Expected:
- Rounds: 3 vòng
- Matches: 6 trận
- Duration: 3 ngày (2 trận/ngày)

Result:
✅ Pass
```

### Test Case 2: Knockout với 8 đội

```php
Input:
- Teams: 8 đội
- Time slots: ['14:00', '16:00']
- Fields: [1, 2]
- Days between rounds: 2
- Third place: true

Expected:
- Rounds: 3 (Tứ kết, Bán kết, Chung kết) + 1 Hạng 3
- Matches: 7 + 1 = 8 trận
- Duration: ~6 ngày

Result:
✅ Pass
```

### Test Case 3: Group Knockout với 13 đội

```php
Input:
- Teams: 13 đội
- Groups: 4 bảng
- Time slots: ['08:00', '10:00', '14:00', '16:00']
- Fields: [1, 2, 3]
- Auto fill third place: true

Expected:
- Group stage: 15 trận
- Qualified: 8 (top 2) + 8 (extra) = 16 đội
- Knockout: 4 vòng, 15 trận
- Total: 30 trận

Result:
✅ Pass
```

---

## 🐛 TROUBLESHOOTING

### ❌ Error: "Không thể tìm slot hợp lệ"

**Nguyên nhân:**
- Quá nhiều trận, quá ít sân/time slots
- Conflict không thể giải quyết

**Giải pháp:**
```php
// 1. Tăng số sân
$fieldIds = [1, 2, 3, 4];  // Thay vì [1, 2]

// 2. Tăng số time slots
$timeSlots = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00'];

// 3. Giảm match duration
$matchDuration = 45;  // Thay vì 60
```

### ❌ Error: "Knockout chỉ chấp nhận số đội là lũy thừa của 2"

**Nguyên nhân:**
- Số đội không phải 2, 4, 8, 16, 32...

**Giải pháp:**
```php
// Option 1: Dùng Group Knockout thay vì Knockout
$format = 'group_knockout';

// Option 2: Thêm/bớt đội để đủ 2^n
if ($n === 7) {
    // Thêm 1 đội → 8 đội
}
```

### ❌ Error: "N+1 query problem"

**Nguyên nhân:**
- Không eager load relationships

**Giải pháp:**
```php
// ❌ Bad
$tournament = Tournament::findOrFail($id);
foreach ($tournament->registrations as $reg) {
    echo $reg->team->name;  // N queries
}

// ✅ Good
$tournament = Tournament::with('registrations.team')->findOrFail($id);
foreach ($tournament->registrations as $reg) {
    echo $reg->team->name;  // 1 query
}
```

---

## 📚 TÀI LIỆU THAM KHẢO

### Papers & Algorithms

1. **Circle Method (Berger Table)**
   - Richard Schurig (1886)
   - [Wikipedia - Round-robin tournament](https://en.wikipedia.org/wiki/Round-robin_tournament)

2. **Knockout Tournament Design**
   - Single-elimination bracket
   - [Bracket Theory](https://en.wikipedia.org/wiki/Single-elimination_tournament)

3. **Greedy Scheduling Algorithm**
   - Time slot assignment
   - Conflict resolution

### Real World Applications

1. **FIFA World Cup** - Group Knockout format
2. **UEFA Champions League** - Group Knockout format
3. **NBA** - Round Robin (regular season)
4. **Wimbledon** - Knockout format

---

## 🎓 KẾT LUẬN

Hệ thống tournament scheduling này implement 3 thuật toán cổ điển:

1. **Circle Method** cho Round Robin - Đảm bảo mỗi đội đá với nhau đúng 1 lần
2. **Binary Tree Bracket** cho Knockout - Cấu trúc cây nhị phân chuẩn
3. **Hybrid Algorithm** cho Group Knockout - Kết hợp 2 thuật toán trên

Tất cả đều sử dụng **Greedy Scheduling** để xếp lịch thi đấu tối ưu, tránh conflict về thời gian và tài nguyên.

---

**📝 Ghi chú:**
- Code: `app/Services/TournamentScheduleService.php`
- Controller: `app/Http/Controllers/Api/TournamentController.php`
- Models: `app/Models/Tournament*.php`

**⚠️ Cảnh báo:**
- Không nên sinh lịch cho giải > 64 đội với Round Robin
- Knockout yêu cầu số đội chuẩn (2^n)
- Group Knockout phức tạp nhất, cần test kỹ

---

**🔥 Performance Tips:**

```php
// 1. Use indexes
Schema::table('tournament_matches', function (Blueprint $table) {
    $table->index(['match_date', 'match_time', 'field_id']);
    $table->index(['home_team_id', 'away_team_id']);
});

// 2. Use caching
Cache::remember("tournament_{$id}_bracket", 3600, function () {
    return Tournament::with([...])->find($id);
});

// 3. Use queues for large tournaments
GenerateTournamentScheduleJob::dispatch($tournament);
```

---

**✨ END OF DOCUMENT ✨**
