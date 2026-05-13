# 🏆 Knockout Bracket Auto-Update System

## Tổng quan

Hệ thống tự động tạo các trận đấu dự kiến (placeholder matches) cho toàn bộ vòng knockout và tự động cập nhật đội thắng vào trận tiếp theo.

---

## 🔧 Cơ chế hoạt động

### 1. **Tạo Bracket Knockout (generateKnockout)**

Khi generate schedule với format `knockout`, hệ thống:

#### ✅ Tạo TẤT CẢ trận đấu từ vòng 1 → Chung kết ngay lập tức
- **Vòng 1**: Trận có `home_team_id` và `away_team_id` thực tế
- **Vòng 2+**: Trận có `home_team_id` và `away_team_id` = `null` (placeholder chờ kết quả vòng trước)
- **Trận tranh hạng 3**: Link với 2 trận bán kết, chờ đội THUA

#### ✅ Link các trận với nhau qua:
- `home_from_match_id`: ID trận mà đội nhà đến từ đó
- `away_from_match_id`: ID trận mà đội khách đến từ đó
- `home_from_position`: Mô tả (VD: "Thắng trận #1", "Thua trận #5")
- `away_from_position`: Mô tả (VD: "Thắng trận #2", "Thua trận #6")
- `bracket_position`: Đặc biệt cho trận tranh hạng 3 = `'third_place'`

**Ví dụ Bracket 8 đội:**

```
Vòng 1 (Tứ kết) - 4 trận:
┌─────────────────────────────────┐
│ Match #1: Team A vs Team B      │ ─┐
│  - home_team_id: 1              │  │
│  - away_team_id: 2              │  │
│  - status: scheduled            │  │
└─────────────────────────────────┘  │
                                     ├─→ Match #5 (Bán kết 1)
┌─────────────────────────────────┐  │    - home_from_match_id: 1
│ Match #2: Team C vs Team D      │  │    - away_from_match_id: 2
│  - home_team_id: 3              │  │    - home_team_id: null ⏳
│  - away_team_id: 4              │ ─┘    - away_team_id: null ⏳
└─────────────────────────────────┘
┌─────────────────────────────────┐
│ Match #3: Team E vs Team F      │ ─┐
│  - home_team_id: 5              │  │
│  - away_team_id: 6              │  │
└─────────────────────────────────┘  │
                                     ├─→ Match #6 (Bán kết 2)
┌─────────────────────────────────┐  │    - home_from_match_id: 3
│ Match #4: Team G vs Team H      │  │    - away_from_match_id: 4
│  - home_team_id: 7              │  │    - home_team_id: null ⏳
│  - away_team_id: 8              │ ─┘    - away_team_id: null ⏳
└─────────────────────────────────┘

Vòng 2 (Bán kết) - 2 trận:
┌─────────────────────────────────┐
│ Match #5: TBD vs TBD            │ ─┐
│  - home_from_match_id: 1        │  │
│  - away_from_match_id: 2        │  │
│  - status: scheduled            │  │
└─────────────────────────────────┘  ├─→ Match #7 (Chung kết)
                                     │    - home_from_match_id: 5
┌─────────────────────────────────┐  │    - away_from_match_id: 6
│ Match #6: TBD vs TBD            │  │    - home_team_id: null ⏳
│  - home_from_match_id: 3        │  │    - away_team_id: null ⏳
│  - away_from_match_id: 4        │ ─┘
└─────────────────────────────────┘
        │                   │
        └─────┬─────────────┘
              │
              ▼
    Match #8 (Tranh hạng 3)
    - home_from_match_id: 5
    - away_from_match_id: 6
    - bracket_position: 'third_place'
    - Đội THUA bán kết → vào tranh hạng 3

Vòng 3 (Chung kết) - 1 trận:
┌─────────────────────────────────┐
│ Match #7: TBD vs TBD            │
│  - home_from_match_id: 5        │
│  - away_from_match_id: 6        │
│  - round_type: 'final'          │
│  - home_team_id: null ⏳        │
│  - away_team_id: null ⏳        │
└─────────────────────────────────┘
```

---

### 2. **Cập nhật Tỷ số (updateScore)**

Khi admin/organizer cập nhật tỷ số:

#### 1️⃣ Xác định đội thắng
```php
if (home_score > away_score) {
    winner_id = home_team_id;
} else if (away_score > home_score) {
    winner_id = away_team_id;
} else {
    // Nếu hòa, dùng penalty
    winner_id = (home_penalty_score > away_penalty_score) 
        ? home_team_id 
        : away_team_id;
}
```

#### 2️⃣ Cập nhật trận đấu hiện tại
```php
match->update([
    'home_score' => validated['home_score'],
    'away_score' => validated['away_score'],
    'winner_team_id' => winner_id,
    'status' => 'completed'
]);
```

#### 3️⃣ Tự động fill đội thắng/thua vào trận tiếp theo (updateNextMatch)
```php
// Xác định đội thua (cho trận tranh hạng 3)
$loserId = ($winnerId == home_team_id) ? away_team_id : home_team_id;

// Tìm tất cả trận có link với trận hiện tại
$nextMatches = TournamentMatch::with('round')
    ->where(function($q) use ($currentMatch) {
        $q->where('home_from_match_id', $currentMatch->id)
          ->orWhere('away_from_match_id', $currentMatch->id);
    })->get();

// Cập nhật đội vào slot tương ứng
foreach ($nextMatches as $nextMatch) {
    // Check nếu là trận tranh hạng 3 → fill đội THUA
    $isThirdPlaceMatch = ($nextMatch->bracket_position === 'third_place');
    $teamToAdvance = $isThirdPlaceMatch ? $loserId : $winnerId;
    
    if ($nextMatch->home_from_match_id == $currentMatch->id) {
        $nextMatch->home_team_id = $teamToAdvance;
    }
    if ($nextMatch->away_from_match_id == $currentMatch->id) {
        $nextMatch->away_team_id = $teamToAdvance;
    }
    $nextMatch->save();
}
```

**Flow cụ thể với 8 đội:**

```
Step 1-4: Admin update tứ kết (Match #1-4)
┌───────────────────────────┐
│ Match #1: Team A (3) vs Team B (1) │ ✅ Completed
│  Winner: Team A (ID: 1)            │
│  Loser: Team B (ID: 2)             │
└───────────────────────────┘
           │
           ▼ Auto-update
┌───────────────────────────┐
│ Match #5 (Bán kết 1)      │
│  - home_team_id: 1 ← Team A fills home slot
│  - away_team_id: null     │
└───────────────────────────┘

... (Match #2-4 tương tự)

Step 5-6: Admin update bán kết (Match #5-6)
┌───────────────────────────┐
│ Match #5: Team A (2) vs Team C (1) │ ✅ Completed
│  Winner: Team A (ID: 1)             │
│  Loser: Team C (ID: 3)              │
└───────────────────────────┘
           │              │
           ▼ Winner       ▼ Loser
┌─────────────────┐  ┌─────────────────┐
│ Match #7        │  │ Match #8        │
│ (Chung kết)     │  │ (Tranh hạng 3)  │
│ home_team_id: 1 │  │ home_team_id: 3 │
└─────────────────┘  └─────────────────┘

┌───────────────────────────┐
│ Match #6: Team E (1) vs Team G (2) │ ✅ Completed
│  Winner: Team G (ID: 7)             │
│  Loser: Team E (ID: 5)              │
└───────────────────────────┘
           │              │
           ▼ Winner       ▼ Loser
┌─────────────────┐  ┌─────────────────┐
│ Match #7        │  │ Match #8        │
│ (Chung kết)     │  │ (Tranh hạng 3)  │
│ away_team_id: 7 │  │ away_team_id: 5 │
└─────────────────┘  └─────────────────┘

Result:
✅ Match #7: Team A vs Team G (Chung kết)
✅ Match #8: Team C vs Team E (Tranh hạng 3)
```

---

## 📊 Database Schema

### Bảng `tournament_matches`

Các field quan trọng:

| Field | Type | Mô tả |
|-------|------|-------|
| `home_team_id` | int | Đội nhà (có thể null cho placeholder) |
| `away_team_id` | int | Đội khách (có thể null cho placeholder) |
| `home_from_match_id` | int | Trận mà đội nhà đến từ đó |
| `away_from_match_id` | int | Trận mà đội khách đến từ đó |
| `home_from_position` | string | Mô tả (VD: "Thắng trận #5") |
| `away_from_position` | string | Mô tả (VD: "Thắng trận #6") |
| `winner_team_id` | int | Đội thắng (sau khi hoàn thành) |
| `status` | enum | scheduled/completed/cancelled |

---

## 🧪 Testing Guide

### Test Case 1: Generate Knockout Bracket (8 đội)

**Request:**
```bash
POST /api/tournaments/1/generate-schedule
Content-Type: application/json

{
  "start_date": "2026-06-01",
  "time_slots": ["08:00", "10:00", "14:00"],
  "field_ids": [1, 2],
  "days_between_rounds": 3,
  "create_third_place_match": true
}
```

**Expected Result:**
- ✅ Tạo TẤT CẢ 8 trận: 4 tứ kết + 2 bán kết + 1 chung kết + 1 tranh hạng 3
- ✅ Vòng 1 (Tứ kết): Match #1-4 có team thực tế
- ✅ Vòng 2 (Bán kết): Match #5-6 có `home_from_match_id`, `away_from_match_id`, team = null
- ✅ Vòng 3 (Chung kết): Match #7 có `home_from_match_id`, `away_from_match_id`, team = null
- ✅ Tranh hạng 3: Match #8 có `home_from_match_id`, `away_from_match_id`, `bracket_position = 'third_place'`

**Verify:**
```bash
GET /api/tournaments/1/bracket
```

Check response:
```json
{
  "data": {
    "rounds": [
      {
        "round_name": "Tứ kết",
        "round_type": "round_of_8",
        "matches": [
          {
            "id": 1,
            "match_number": 1,
            "home_team_id": 5,
            "away_team_id": 8,
            "home_from_match_id": null,
            "away_from_match_id": null,
            "status": "scheduled"
          },
          {
            "id": 2,
            "match_number": 2,
            "home_team_id": 12,
            "away_team_id": 15,
            "status": "scheduled"
          },
          // Match #3, #4...
        ]
      },
      {
        "round_name": "Bán kết",
        "round_type": "semifinal",
        "matches": [
          {
            "id": 5,
            "match_number": 5,
            "home_team_id": null,  ← placeholder
            "away_team_id": null,  ← placeholder
            "home_from_match_id": 1,  ← link với Match #1
            "away_from_match_id": 2,  ← link với Match #2
            "home_from_position": "Thắng trận #1",
            "away_from_position": "Thắng trận #2",
            "status": "scheduled"
          },
          {
            "id": 6,
            "match_number": 6,
            "home_team_id": null,
            "away_team_id": null,
            "home_from_match_id": 3,
            "away_from_match_id": 4,
            "home_from_position": "Thắng trận #3",
            "away_from_position": "Thắng trận #4",
            "status": "scheduled"
          }
        ]
      },
      {
        "round_name": "Chung kết",
        "round_type": "final",
        "matches": [
          {
            "id": 7,
            "match_number": 7,
            "home_team_id": null,  ← placeholder
            "away_team_id": null,  ← placeholder
            "home_from_match_id": 5,  ← link với Match #5
            "away_from_match_id": 6,  ← link với Match #6
            "home_from_position": "Thắng trận #5",
            "away_from_position": "Thắng trận #6",
            "status": "scheduled"
          }
        ]
      },
      {
        "round_name": "Tranh hạng 3",
        "round_type": "third_place",
        "matches": [
          {
            "id": 8,
            "match_number": 8,
            "home_team_id": null,  ← placeholder
            "away_team_id": null,  ← placeholder
            "home_from_match_id": 5,  ← link với Match #5
            "away_from_match_id": 6,  ← link với Match #6
            "home_from_position": "Thua trận #5",
            "away_from_position": "Thua trận #6",
            "bracket_position": "third_place",  ← đặc biệt!
            "status": "scheduled"
          }
        ]
      }
    ]
  }
}
```

### Test Case 2: Update Score & Auto-fill Winner/Loser

**Scenario: 8 đội → Tứ kết → Bán kết → Chung kết + Tranh hạng 3**

**Step 1: Update Match #1 score (Tứ kết)**
```bash
PUT /api/tournament-matches/1/result
Content-Type: application/json

{
  "home_score": 3,
  "away_score": 1,
  "status": "completed"
}
```

**Expected:**
- ✅ Match #1 status = completed
- ✅ Match #1 winner_team_id = 5 (home team)
- ✅ Match #5 (Bán kết 1) home_team_id = 5 (auto-updated)

**Verify:**
```bash
GET /api/tournament-matches/5
```

Response:
```json
{
  "data": {
    "id": 5,
    "home_team_id": 5,  ← Auto-filled!
    "away_team_id": null,
    "home_team": {
      "id": 5,
      "name": "Team A"
    },
    "away_team": null
  }
}
```

**Step 2-4: Update Match #2-4 scores (Tứ kết)**
_(Tương tự, fill teams vào Match #5, #6)_

**Step 5: Update Match #5 score (Bán kết 1)**
```bash
PUT /api/tournament-matches/5/result
Content-Type: application/json

{
  "home_score": 2,
  "away_score": 1,
  "status": "completed"
}
```

**Expected:**
- ✅ Match #5 status = completed
- ✅ Match #5 winner_team_id = 5 (Team A)
- ✅ Match #5 loser = Team C (ID: 12)
- ✅ Match #7 (Chung kết) home_team_id = 5 (winner → auto-filled)
- ✅ Match #8 (Tranh hạng 3) home_team_id = 12 (loser → auto-filled)

**Verify:**
```bash
GET /api/tournament-matches/7
GET /api/tournament-matches/8
```

Response Match #7 (Chung kết):
```json
{
  "data": {
    "id": 7,
    "round_type": "final",
    "home_team_id": 5,  ← Winner of Match #5
    "away_team_id": null,  ← Waiting for Match #6
    "home_team": {
      "id": 5,
      "name": "Team A"
    }
  }
}
```

Response Match #8 (Tranh hạng 3):
```json
{
  "data": {
    "id": 8,
    "round_type": "third_place",
    "bracket_position": "third_place",
    "home_team_id": 12,  ← Loser of Match #5
    "away_team_id": null,  ← Waiting for Match #6
    "home_team": {
      "id": 12,
      "name": "Team C"
    }
  }
}
```

**Step 6: Update Match #6 score (Bán kết 2)**
```bash
PUT /api/tournament-matches/6/result
Content-Type: application/json

{
  "home_score": 1,
  "away_score": 2,
  "status": "completed"
}
```

**Expected:**
- ✅ Match #6 winner_team_id = 15 (away team)
- ✅ Match #6 loser = Team E (ID: 8)
- ✅ Match #7 (Chung kết) away_team_id = 15 (winner → auto-filled) ✅ Ready!
- ✅ Match #8 (Tranh hạng 3) away_team_id = 8 (loser → auto-filled) ✅ Ready!

**Final Verify:**
```bash
GET /api/tournament-matches/7
GET /api/tournament-matches/8
```

Response Match #7:
```json
{
  "data": {
    "id": 7,
    "round_type": "final",
    "home_team_id": 5,
    "away_team_id": 15,
    "home_team": { "id": 5, "name": "Team A" },
    "away_team": { "id": 15, "name": "Team D" },
    "status": "scheduled"  ← Ready to play!
  }
}
```

Response Match #8:
```json
{
  "data": {
    "id": 8,
    "round_type": "third_place",
    "bracket_position": "third_place",
    "home_team_id": 12,
    "away_team_id": 8,
    "home_team": { "id": 12, "name": "Team C" },
    "away_team": { "id": 8, "name": "Team E" },
    "status": "scheduled"  ← Ready to play!
  }
}
```

---

## 🐛 Debug & Logging

Khi update score, hệ thống log các thông tin sau:

```php
// Log khi tìm thấy trận tiếp theo
\Log::info('Updating next matches after match completion', [
    'current_match_id' => 1,
    'winner_id' => 5,
    'next_matches_count' => 1
]);

// Log khi update team vào trận tiếp theo
\Log::info('Updated next match team', [
    'next_match_id' => 3,
    'home_team_id' => 5,
    'away_team_id' => null
]);
```

Check log:
```bash
tail -f storage/logs/laravel.log | grep "Updating next matches"
```

---

## 🎯 Benefits

### ✅ Tự động hóa hoàn toàn
- Không cần admin thủ công chọn đội cho trận tiếp theo
- Giảm thiểu lỗi người dùng

### ✅ Real-time update
- Ngay khi nhập tỷ số → đội thắng tự động vào vòng sau
- Người dùng có thể xem bracket cập nhật tức thì

### ✅ Transparent & Trackable
- Có `home_from_position` và `away_from_position` để biết đội đến từ đâu
- Có logging đầy đủ để debug

### ✅ Hỗ trợ BYE teams
- Đội gặp BYE tự động vào vòng sau mà không cần đá

---

## 📝 Notes

### ✅ Tạo trận trước vs sau

**Trước đây:**
- Chỉ tạo trận cho vòng hiện tại
- Vòng tiếp theo chỉ tạo sau khi vòng trước hoàn thành

**Bây giờ:**
- ✅ Tạo TẤT CẢ trận cho TẤT CẢ vòng ngay khi generate schedule
- ✅ Vòng 1: Có teams thực tế
- ✅ Vòng 2+: Team = null, nhưng có link với trận trước qua `home_from_match_id`/`away_from_match_id`
- ✅ Khi trận hoàn thành → Auto-fill team vào trận tiếp theo

**Lợi ích:**
- User nhìn thấy TOÀN BỘ bracket ngay từ đầu
- Dễ dàng visualize tournament structure
- Không cần trigger manual để tạo vòng tiếp theo

### ✅ Winner vs Loser Advancement

**Winner Advancement (Vòng thường):**
- Đội thắng → vào trận tiếp theo
- VD: Thắng tứ kết → vào bán kết

**Loser Advancement (Tranh hạng 3):**
- Đội THUA bán kết → vào tranh hạng 3
- Logic đặc biệt: Check `bracket_position = 'third_place'`
- Auto-fill loser thay vì winner

### ✅ Các trường hợp đặc biệt

1. **Penalty shootout**: Nếu hòa, dùng `home_penalty_score` và `away_penalty_score` để xác định thắng thua
2. **BYE teams**: Đội gặp BYE tự động vào vòng sau (trận có 1 team null)
3. **Round robin**: Không có auto-update, chỉ update standings
4. **Group knockout**: Vòng bảng không có auto-update, chỉ vòng knockout mới có
5. **Third place match**: Link với 2 trận bán kết, fill đội THUA (không phải đội thắng)

---

## 🔗 Related Files

- `TournamentScheduleService.php` - Logic generate bracket
- `TournamentMatchController.php` - Logic update score & auto-fill
- `TournamentMatch.php` - Model với relationships
- `tournament_matches` migration - Database schema

---

## 🚀 Future Enhancements

- [ ] Websocket real-time notification khi bracket update
- [ ] Undo/Redo score update
- [ ] Manual override cho special cases
- [ ] Bracket visualization với highlight winner path
