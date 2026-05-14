# 🗄️ DATABASE SCHEMA & RELATIONSHIPS - CẤU TRÚC CƠDỮ LIỆU

> **Project:** Hệ thống quản lý sân bóng đá - Do-an-BE-sanbongda  
> **DBMS:** MySQL / MariaDB  
> **ORM:** Laravel Eloquent  
> **Cập nhật:** 14/05/2026

---

## 📊 TỔNG QUAN

Hệ thống có **20 bảng chính** được chia thành **6 nhóm chức năng**:

| Nhóm | Số bảng | Mô tả |
|------|---------|-------|
| **Authentication & Authorization** | 3 | Xác thực, phân quyền |
| **Field Management** | 5 | Quản lý sân, cụm sân, giá |
| **Booking System** | 1 | Đặt sân |
| **Team Management** | 2 | Quản lý đội bóng |
| **Tournament System** | 8 | Giải đấu và lịch thi đấu |
| **Notification** | 1 | Thông báo |

**Tổng số quan hệ:** ~40 foreign keys

---

## 🔐 1. AUTHENTICATION & AUTHORIZATION

### 📋 **1.1. roles** - Vai trò người dùng

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| id | BIGINT PK | ID vai trò |
| name_role | VARCHAR(100) UNIQUE | Tên vai trò (admin, owner, staff, member) |
| description | TEXT | Mô tả vai trò |
| timestamps | | created_at, updated_at |

**Dữ liệu mặc định:**
- `admin` - Quản trị viên hệ thống
- `owner` - Chủ sân
- `staff` - Nhân viên sân
- `member` - Người dùng thường (đội trưởng, cầu thủ)

---

### 👤 **1.2. users** - Người dùng

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID người dùng | |
| role_id | BIGINT FK | Vai trò | → roles.id |
| name | VARCHAR(255) | Họ tên | |
| email | VARCHAR(255) UNIQUE | Email đăng nhập | |
| phone | VARCHAR(20) | Số điện thoại | |
| code_id | VARCHAR(20) | CCCD/CMND | |
| status | BOOLEAN | Trạng thái (active/inactive) | |
| password | VARCHAR | Mật khẩu (hashed) | |
| email_verified_at | TIMESTAMP | Thời gian verify email | |
| email_verification_token | VARCHAR | Token verify email | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(Role)` - 1 user thuộc 1 role
- `hasOne(OwnerProfile)` - 1 user có thể có 1 owner profile
- `hasOne(Staff)` - 1 user có thể là 1 staff
- `hasMany(Book)` - 1 user có nhiều booking
- `hasMany(Team, 'captain_user_id')` - 1 user có thể là đội trưởng nhiều đội
- `hasMany(Tournament, 'organizer_id')` - 1 user có thể tổ chức nhiều giải

**Indexes:**
- `UNIQUE(email)`
- `INDEX(role_id)`

---

### 🏢 **1.3. owner_profiles** - Hồ sơ chủ sân

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID profile | |
| user_id | BIGINT FK | User liên kết | → users.id |
| business_name | VARCHAR | Tên doanh nghiệp | |
| address | TEXT | Địa chỉ kinh doanh | |
| image_code | VARCHAR | Giấy phép kinh doanh (URL) | |
| code_id | VARCHAR(20) | CCCD | |
| qr_code_image | TEXT | QR code chuyển khoản (URL) | |
| bank_name | VARCHAR(100) | Tên ngân hàng | |
| account_number | VARCHAR(50) | Số tài khoản | |
| account_holder | VARCHAR(255) | Chủ tài khoản | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(User)` - 1 profile thuộc 1 user
- `hasMany(FieldCluster)` - 1 owner có nhiều cụm sân

**Cascade:** `ON DELETE CASCADE` (xóa user → xóa owner profile)

---

### 📝 **1.4. owner_registrations** - Đăng ký làm chủ sân

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID đăng ký | |
| user_id | BIGINT FK | User đăng ký | → users.id |
| business_name | VARCHAR | Tên doanh nghiệp | |
| address | TEXT | Địa chỉ | |
| phone | VARCHAR(20) | SĐT liên hệ | |
| image_code | VARCHAR | Giấy phép KD | |
| code_id | VARCHAR(20) | CCCD | |
| status | ENUM | pending/approved/rejected | |
| admin_notes | TEXT | Ghi chú admin | |
| approved_at | TIMESTAMP | Thời gian duyệt | |
| rejected_at | TIMESTAMP | Thời gian từ chối | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(User)` - 1 đăng ký thuộc 1 user

---

### 👔 **1.5. staffs** - Nhân viên sân

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID staff | |
| user_id | BIGINT FK | User liên kết | → users.id |
| field_cluster_id | BIGINT FK | Cụm sân làm việc | → field_clusters.id |
| position | VARCHAR(100) | Chức vụ | |
| salary | DECIMAL(10,2) | Lương | |
| hire_date | DATE | Ngày vào làm | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(User)` - 1 staff thuộc 1 user
- `belongsTo(FieldCluster)` - 1 staff làm việc tại 1 cụm sân

---

## ⚽ 2. FIELD MANAGEMENT - QUẢN LÝ SÂN

### 🏟️ **2.1. field_clusters** - Cụm sân

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID cụm sân | |
| owner_id | BIGINT FK | Chủ sân | → owner_profiles.id |
| name | VARCHAR(255) | Tên cụm sân | |
| address | TEXT | Địa chỉ | |
| phone | VARCHAR(20) | SĐT liên hệ | |
| description | TEXT | Mô tả | |
| image | VARCHAR | Ảnh cụm sân (URL) | |
| open_time | TIME | Giờ mở cửa | |
| close_time | TIME | Giờ đóng cửa | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(OwnerProfile, 'owner_id')` - 1 cụm sân thuộc 1 owner
- `hasMany(Field)` - 1 cụm sân có nhiều sân
- `hasMany(Staff)` - 1 cụm sân có nhiều nhân viên
- `hasMany(FieldHourlyPrice)` - 1 cụm sân có nhiều giá theo giờ
- `hasMany(TournamentSessionPrice)` - 1 cụm sân có nhiều giá giải đấu
- `hasMany(Tournament)` - 1 cụm sân có thể tổ chức nhiều giải

**Cascade:** `ON DELETE CASCADE` (xóa owner → xóa field cluster)

---

### ⚽ **2.2. fields** - Sân bóng

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID sân | |
| field_cluster_id | BIGINT FK | Cụm sân chứa | → field_clusters.id |
| name | VARCHAR(50) | Tên sân (Sân 1, Sân 2...) | |
| field_type | VARCHAR(50) | Loại sân (5v5, 7v7, 11v11) | |
| status | ENUM | available/maintenance/inactive | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(FieldCluster)` - 1 sân thuộc 1 cụm sân
- `hasMany(Book)` - 1 sân có nhiều booking
- `hasMany(FieldHourlyPrice)` - 1 sân có nhiều mức giá theo giờ
- `hasMany(TournamentMatch)` - 1 sân có nhiều trận đấu giải

**Cascade:** `ON DELETE CASCADE` (xóa field cluster → xóa field)

**Indexes:**
- `INDEX(field_cluster_id, field_type, status)`

---

### 💰 **2.3. field_hourly_prices** - Giá thuê sân theo giờ

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID giá | |
| field_id | BIGINT FK | Sân áp dụng | → fields.id |
| field_cluster_id | BIGINT FK | Cụm sân | → field_clusters.id |
| day_type | ENUM | weekday/weekend | |
| start_time | TIME | Giờ bắt đầu khung giá | |
| end_time | TIME | Giờ kết thúc khung giá | |
| price_per_hour | DECIMAL(10,2) | Giá/giờ (VNĐ) | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(Field)` - 1 giá thuộc 1 sân
- `belongsTo(FieldCluster)` - 1 giá thuộc 1 cụm sân

**Ví dụ:**
```
Sân 1 - Ngày thường:
- 06:00-12:00: 200,000 VNĐ/h
- 12:00-18:00: 300,000 VNĐ/h
- 18:00-23:00: 400,000 VNĐ/h

Sân 1 - Cuối tuần:
- 06:00-23:00: 500,000 VNĐ/h
```

---

### 🏆 **2.4. tournament_session_prices** - Giá thuê sân giải đấu

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID giá | |
| field_cluster_id | BIGINT FK | Cụm sân | → field_clusters.id |
| field_type | VARCHAR(50) | Loại sân (5v5, 7v7, 11v11) | |
| session_type | ENUM | morning/afternoon/evening/full_day | |
| day_type | ENUM | weekday/weekend | |
| price_per_session | DECIMAL(10,2) | Giá/buổi (VNĐ) | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(FieldCluster)` - 1 giá thuộc 1 cụm sân

**Ví dụ:**
```
Cụm sân A - Sân 11v11:
- Morning (06:00-12:00) - Weekday: 3,000,000 VNĐ
- Afternoon (12:00-18:00) - Weekday: 4,000,000 VNĐ
- Evening (18:00-23:00) - Weekend: 6,000,000 VNĐ
- Full day (06:00-23:00) - Weekend: 12,000,000 VNĐ
```

---

## 📅 3. BOOKING SYSTEM - ĐẶT SÂN

### 📝 **3.1. books** - Đặt sân

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID booking | |
| user_id | BIGINT FK | Người đặt | → users.id |
| field_id | BIGINT FK | Sân đặt | → fields.id |
| tournament_id | BIGINT FK (nullable) | Giải đấu (nếu có) | → tournaments.id |
| booking_type | ENUM | regular/tournament | |
| day_book | DATE | Ngày đặt sân | |
| start_time | TIME | Giờ bắt đầu | |
| end_time | TIME | Giờ kết thúc | |
| total_price | DECIMAL(10,2) | Tổng tiền | |
| status | ENUM | pending/confirmed/cancelled | |
| payment_status | ENUM | unpaid/paid/refunded | |
| payment_method | VARCHAR(50) | cash/transfer | |
| payment_date | TIMESTAMP | Ngày thanh toán | |
| payment_proof | TEXT | Bill chuyển khoản (URL) | |
| payment_note | TEXT | Ghi chú thanh toán | |
| confirmed_by_user_id | BIGINT FK | Owner xác nhận | → users.id |
| confirmed_at | TIMESTAMP | Thời gian xác nhận | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(User)` - 1 booking thuộc 1 user
- `belongsTo(Field)` - 1 booking thuộc 1 sân
- `belongsTo(Tournament)` - 1 booking có thể thuộc 1 giải đấu
- `belongsTo(User, 'confirmed_by_user_id')` - Owner xác nhận

**Constraints:**
- `UNIQUE(field_id, day_book, start_time, end_time)` - Tránh booking trùng
- `INDEX(user_id, day_book, status)`
- `INDEX(field_id, day_book, start_time)`

**Workflow:**
```
1. User tạo booking → status='pending', payment_status='unpaid'
2. User upload bill → payment_proof
3. Owner xác nhận → status='confirmed', payment_status='paid'
4. Owner từ chối → status='cancelled'
```

---

## 👥 4. TEAM MANAGEMENT - QUẢN LÝ ĐỘI BÓNG

### ⚽ **4.1. teams** - Đội bóng

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID đội | |
| captain_user_id | BIGINT FK UNIQUE | Đội trưởng | → users.id |
| name | VARCHAR(255) | Tên đội | |
| short_name | VARCHAR(50) | Tên viết tắt | |
| logo | VARCHAR | Logo đội (URL) | |
| contact_phone | VARCHAR(20) | SĐT liên hệ | |
| contact_email | VARCHAR | Email liên hệ | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(User, 'captain_user_id')` - 1 đội có 1 đội trưởng
- `hasMany(TeamMember)` - 1 đội có nhiều thành viên
- `hasMany(TournamentRegistration)` - 1 đội có nhiều đăng ký giải
- `hasMany(TournamentMatch, 'home_team_id')` - Đội nhà
- `hasMany(TournamentMatch, 'away_team_id')` - Đội khách

**Constraints:**
- `UNIQUE(captain_user_id)` - 1 user chỉ là đội trưởng của 1 đội

**Methods:**
```php
// Tổng số thành viên (captain + members)
$team->totalMembersCount()
```

---

### 👤 **4.2. team_members** - Thành viên đội

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID thành viên | |
| team_id | BIGINT FK | Đội bóng | → teams.id |
| player_name | VARCHAR(255) | Tên cầu thủ | |
| shirt_number | INT | Số áo | |
| position | VARCHAR(50) | Vị trí (GK, DF, MF, FW) | |
| date_of_birth | DATE | Ngày sinh | |
| phone | VARCHAR(20) | SĐT | |
| email | VARCHAR | Email | |
| avatar | VARCHAR | Ảnh (URL) | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(Team)` - 1 member thuộc 1 đội

**Constraints:**
- `UNIQUE(team_id, shirt_number)` - Số áo unique trong đội

---

## 🏆 5. TOURNAMENT SYSTEM - GIẢI ĐẤU

### 🎯 **5.1. tournaments** - Giải đấu

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID giải | |
| organizer_id | BIGINT FK | Người tổ chức | → users.id |
| field_cluster_id | BIGINT FK | Cụm sân tổ chức | → field_clusters.id |
| name | VARCHAR(255) | Tên giải | |
| description | TEXT | Mô tả | |
| tournament_rules_file | VARCHAR | File thể lệ (URL) | |
| format | ENUM | round_robin/knockout/group_knockout | |
| field_type | VARCHAR(50) | Loại sân (5v5, 7v7, 11v11) | |
| max_teams | INT | Số đội tối đa | |
| min_teams | INT | Số đội tối thiểu | |
| registration_start | DATETIME | Ngày mở đăng ký | |
| registration_end | DATETIME | Ngày đóng đăng ký | |
| start_date | DATE | Ngày bắt đầu giải | |
| end_date | DATE | Ngày kết thúc giải | |
| status | ENUM | draft/open/in_progress/completed/cancelled | |
| schedule_published | BOOLEAN | Lịch đã công bố? | |
| payment_method | ENUM | cash/transfer | |
| payment_status | ENUM | pending/paid/failed | |
| payment_date | TIMESTAMP | Ngày thanh toán | |
| payment_proof | TEXT | Bill chuyển khoản (URL) | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(User, 'organizer_id')` - 1 giải có 1 người tổ chức
- `belongsTo(FieldCluster)` - 1 giải tổ chức tại 1 cụm sân
- `hasMany(TournamentRegistration)` - 1 giải có nhiều đăng ký
- `hasMany(TournamentGroup)` - 1 giải có nhiều bảng đấu
- `hasMany(TournamentRound)` - 1 giải có nhiều vòng đấu
- `hasMany(TournamentMatch)` - 1 giải có nhiều trận đấu
- `hasMany(Book, 'tournament_id')` - 1 giải có nhiều booking sân

**Indexes:**
- `INDEX(status)`
- `INDEX(format)`
- `INDEX(organizer_id)`

**Workflow:**
```
draft → open → in_progress → completed/cancelled
  ↓       ↓         ↓
Tạo   Mở ĐK   Thi đấu   Kết thúc
```

**Methods:**
```php
$tournament->approvedTeamsCount() // Đếm đội approved
$tournament->isFull()             // Kiểm tra đã đủ đội
$tournament->availableSlots()     // Số slot còn trống
```

---

### 📝 **5.2. tournament_registrations** - Đăng ký giải đấu

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID đăng ký | |
| tournament_id | BIGINT FK | Giải đấu | → tournaments.id |
| team_id | BIGINT FK | Đội đăng ký | → teams.id |
| registration_date | DATETIME | Ngày đăng ký | |
| registration_status | ENUM | pending/approved/rejected/withdrawn | |
| payment_status | ENUM | unpaid/paid | |
| payment_method | VARCHAR(50) | cash/transfer | |
| payment_date | DATETIME | Ngày thanh toán | |
| payment_proof | VARCHAR | Bill (URL) | |
| notes | TEXT | Ghi chú của đội | |
| admin_notes | TEXT | Ghi chú BTC | |
| rejected_reason | TEXT | Lý do từ chối | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(Tournament)` - 1 đăng ký thuộc 1 giải
- `belongsTo(Team)` - 1 đăng ký thuộc 1 đội

**Constraints:**
- `UNIQUE(tournament_id, team_id)` - 1 đội chỉ đăng ký 1 lần
- `INDEX(registration_status)`

**Workflow:**
```
1. Đội đăng ký → registration_status='pending'
2. BTC duyệt → registration_status='approved'
3. BTC từ chối → registration_status='rejected'
4. Đội rút đăng ký → registration_status='withdrawn'
```

---

### 📊 **5.3. tournament_groups** - Bảng đấu

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID bảng | |
| tournament_id | BIGINT FK | Giải đấu | → tournaments.id |
| group_name | VARCHAR(10) | Tên bảng (A, B, C...) | |
| group_order | INT | Thứ tự bảng | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(Tournament)` - 1 bảng thuộc 1 giải
- `hasMany(TournamentGroupStanding)` - 1 bảng có nhiều đội
- `hasMany(TournamentMatch)` - 1 bảng có nhiều trận đấu

**Constraints:**
- `UNIQUE(tournament_id, group_name)` - Tên bảng unique trong giải

**Ví dụ:**
```
Giải đấu World Cup Mini:
- Bảng A: 4 đội
- Bảng B: 4 đội
- Bảng C: 4 đội
- Bảng D: 4 đội
```

---

### 🏅 **5.4. tournament_group_standings** - Bảng xếp hạng

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| id | BIGINT PK | ID standing |
| tournament_group_id | BIGINT FK | Bảng đấu → tournament_groups.id |
| team_id | BIGINT FK | Đội bóng → teams.id |
| matches_played | INT | Số trận đã đá |
| wins | INT | Thắng |
| draws | INT | Hòa |
| losses | INT | Thua |
| goals_for | INT | Bàn thắng |
| goals_against | INT | Bàn thua |
| goal_difference | INT | Hiệu số |
| points | INT | Điểm (Thắng: 3, Hòa: 1, Thua: 0) |
| ranking | INT | Thứ hạng |
| timestamps | | created_at, updated_at |

**Relationships:**
- `belongsTo(TournamentGroup)` - 1 standing thuộc 1 bảng
- `belongsTo(Team)` - 1 standing thuộc 1 đội

**Thứ tự xếp hạng:**
1. Điểm cao hơn
2. Hiệu số bàn thắng lớn hơn
3. Số bàn thắng nhiều hơn
4. Số trận thắng nhiều hơn
5. Random

---

### 🎭 **5.5. tournament_rounds** - Vòng đấu

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID vòng đấu | |
| tournament_id | BIGINT FK | Giải đấu | → tournaments.id |
| round_name | VARCHAR(50) | Tên vòng (Vòng bảng, Tứ kết...) | |
| round_type | VARCHAR(50) | group_stage/round_of_16/quarter_final/semi_final/final/third_place | |
| round_order | INT | Thứ tự vòng đấu | |
| start_date | DATE | Ngày bắt đầu vòng | |
| end_date | DATE | Ngày kết thúc vòng | |
| is_knockout | BOOLEAN | Có phải knockout? | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(Tournament)` - 1 vòng đấu thuộc 1 giải
- `hasMany(TournamentMatch)` - 1 vòng có nhiều trận đấu

**Ví dụ thể thức knockout (8 đội):**
```
Vòng 1: Tứ kết (Quarter-final) - 4 trận
Vòng 2: Bán kết (Semi-final) - 2 trận
Vòng 3: Chung kết (Final) - 1 trận
Vòng 4: Tranh hạng 3 (Third place) - 1 trận (optional)
```

---

### ⚽ **5.6. matches** - Trận đấu

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID trận đấu | |
| tournament_id | BIGINT FK | Giải đấu | → tournaments.id |
| tournament_round_id | BIGINT FK | Vòng đấu | → tournament_rounds.id |
| tournament_group_id | BIGINT FK (nullable) | Bảng đấu | → tournament_groups.id |
| home_team_id | BIGINT FK (nullable) | Đội nhà | → teams.id |
| away_team_id | BIGINT FK (nullable) | Đội khách | → teams.id |
| home_from_match_id | BIGINT FK (nullable) | Link trận trước (đội nhà) | → matches.id |
| away_from_match_id | BIGINT FK (nullable) | Link trận trước (đội khách) | → matches.id |
| home_from_position | VARCHAR(50) | VD: "Nhất bảng A" | |
| away_from_position | VARCHAR(50) | VD: "Nhì bảng B" | |
| field_id | BIGINT FK | Sân thi đấu | → fields.id |
| match_date | DATE | Ngày thi đấu | |
| match_time | TIME | Giờ thi đấu | |
| match_number | INT | Số thứ tự trận | |
| home_score | INT | Tỷ số đội nhà | |
| away_score | INT | Tỷ số đội khách | |
| winner_team_id | BIGINT FK (nullable) | Đội thắng | → teams.id |
| status | ENUM | scheduled/in_progress/completed/cancelled/postponed | |
| bracket_position | VARCHAR(50) | Vị trí trong bracket (final, semi_final...) | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(Tournament)` - 1 trận thuộc 1 giải
- `belongsTo(TournamentRound)` - 1 trận thuộc 1 vòng đấu
- `belongsTo(TournamentGroup)` - 1 trận có thể thuộc 1 bảng (vòng bảng)
- `belongsTo(Team, 'home_team_id')` - Đội nhà
- `belongsTo(Team, 'away_team_id')` - Đội khách
- `belongsTo(Team, 'winner_team_id')` - Đội thắng
- `belongsTo(Field)` - Sân thi đấu
- `belongsTo(Match, 'home_from_match_id')` - Link trận trước (đội nhà)
- `belongsTo(Match, 'away_from_match_id')` - Link trận trước (đội khách)

**Indexes:**
- `INDEX(tournament_id, match_date, match_time)`
- `INDEX(home_team_id)`
- `INDEX(away_team_id)`
- `INDEX(field_id, match_date, match_time)`

**Ví dụ knockout linking:**
```sql
-- Bán kết 1
Match #5: Team A vs Team B → Winner = Team A

-- Bán kết 2
Match #6: Team C vs Team D → Winner = Team C

-- Chung kết
Match #7:
  home_from_match_id = 5  (Thắng trận #5 = Team A)
  away_from_match_id = 6  (Thắng trận #6 = Team C)
  home_from_position = "Thắng trận #5"
  away_from_position = "Thắng trận #6"
```

---

### 📅 **5.7. tournament_schedules** - Lịch thi đấu (Deprecated)

> ⚠️ **Lưu ý:** Bảng này có vẻ không được sử dụng nữa. Thông tin lịch thi đấu được lưu trực tiếp trong bảng `matches`.

---

## 🔔 6. NOTIFICATION SYSTEM - THÔNG BÁO

### 📬 **6.1. notifications** - Thông báo

| Cột | Kiểu | Mô tả | Quan hệ |
|-----|------|-------|---------|
| id | BIGINT PK | ID thông báo | |
| user_id | BIGINT FK | Người nhận | → users.id |
| type | VARCHAR(50) | Loại thông báo | |
| title | VARCHAR(255) | Tiêu đề | |
| message | TEXT | Nội dung | |
| data | JSON | Dữ liệu bổ sung | |
| is_read | BOOLEAN | Đã đọc? | |
| read_at | TIMESTAMP | Thời gian đọc | |
| action_required | BOOLEAN | Cần hành động? | |
| action_url | VARCHAR | Link hành động | |
| timestamps | | created_at, updated_at | |

**Relationships:**
- `belongsTo(User)` - 1 thông báo thuộc 1 user

**Indexes:**
- `INDEX(user_id, is_read, created_at)`

**Loại thông báo:**
- `owner_registration_approved` - Duyệt đăng ký chủ sân
- `owner_registration_rejected` - Từ chối đăng ký chủ sân
- `booking_confirmed` - Xác nhận booking
- `booking_cancelled` - Hủy booking
- `tournament_registration_approved` - Duyệt đăng ký giải
- `tournament_registration_rejected` - Từ chối đăng ký giải
- `tournament_schedule_published` - Công bố lịch thi đấu
- `payment_confirmed` - Xác nhận thanh toán
- `payment_rejected` - Từ chối thanh toán

---

## 🔗 QUAN HỆ GIỮA CÁC BẢNG

### 📊 **Sơ đồ ERD (Entity Relationship Diagram)**

```
┌─────────────────────────────────────────────────────────────────┐
│                    AUTHENTICATION & USERS                        │
└─────────────────────────────────────────────────────────────────┘

         roles (1) ──< (N) users
                           │
           ┌───────────────┼───────────────┬──────────────┐
           │               │               │              │
           ▼               ▼               ▼              ▼
    owner_profiles    staffs         teams          books
           │               │          (captain)         │
           │               │               │            │
           ▼               ▼               ▼            ▼
    field_clusters ───> fields ◄────────────────────────┘
           │               │
           └───────────────┤
                          │
┌─────────────────────────┴───────────────────────────────────────┐
│                    TOURNAMENT SYSTEM                             │
└─────────────────────────────────────────────────────────────────┘

         users (organizer)
           │
           ▼
       tournaments ◄─── field_clusters
           │
           ├──> tournament_registrations ◄─── teams
           │
           ├──> tournament_groups
           │         │
           │         ├──> tournament_group_standings ◄─── teams
           │         │
           │         └──> matches (group stage)
           │
           ├──> tournament_rounds
           │         │
           │         └──> matches (knockout)
           │
           ├──> matches
           │      │
           │      ├──> home_team (teams)
           │      ├──> away_team (teams)
           │      ├──> winner_team (teams)
           │      └──> field
           │
           └──> books (tournament bookings)

┌─────────────────────────────────────────────────────────────────┐
│                      TEAM MANAGEMENT                             │
└─────────────────────────────────────────────────────────────────┘

         users (captain)
           │
           ▼
         teams
           │
           ├──> team_members
           │
           ├──> tournament_registrations
           │
           └──> matches (home/away/winner)
```

---

## 📋 DANH SÁCH TẤT CẢ QUAN HỆ

### **1. Users & Authentication**

| From | To | Relationship | Type |
|------|-----|--------------|------|
| users | roles | belongsTo | N:1 |
| users | owner_profiles | hasOne | 1:1 |
| users | staffs | hasOne | 1:1 |
| users | teams | hasMany (captain) | 1:N |
| users | books | hasMany | 1:N |
| users | tournaments | hasMany (organizer) | 1:N |
| users | notifications | hasMany | 1:N |

### **2. Field Management**

| From | To | Relationship | Type |
|------|-----|--------------|------|
| owner_profiles | users | belongsTo | N:1 |
| owner_profiles | field_clusters | hasMany | 1:N |
| field_clusters | owner_profiles | belongsTo | N:1 |
| field_clusters | fields | hasMany | 1:N |
| field_clusters | staffs | hasMany | 1:N |
| field_clusters | field_hourly_prices | hasMany | 1:N |
| field_clusters | tournament_session_prices | hasMany | 1:N |
| field_clusters | tournaments | hasMany | 1:N |
| fields | field_clusters | belongsTo | N:1 |
| fields | books | hasMany | 1:N |
| fields | field_hourly_prices | hasMany | 1:N |
| fields | matches | hasMany | 1:N |

### **3. Booking System**

| From | To | Relationship | Type |
|------|-----|--------------|------|
| books | users | belongsTo | N:1 |
| books | fields | belongsTo | N:1 |
| books | tournaments | belongsTo (nullable) | N:1 |
| books | users (confirmed_by) | belongsTo | N:1 |

### **4. Team Management**

| From | To | Relationship | Type |
|------|-----|--------------|------|
| teams | users (captain) | belongsTo | N:1 |
| teams | team_members | hasMany | 1:N |
| teams | tournament_registrations | hasMany | 1:N |
| teams | matches (home) | hasMany | 1:N |
| teams | matches (away) | hasMany | 1:N |
| teams | matches (winner) | hasMany | 1:N |
| team_members | teams | belongsTo | N:1 |

### **5. Tournament System**

| From | To | Relationship | Type |
|------|-----|--------------|------|
| tournaments | users (organizer) | belongsTo | N:1 |
| tournaments | field_clusters | belongsTo | N:1 |
| tournaments | tournament_registrations | hasMany | 1:N |
| tournaments | tournament_groups | hasMany | 1:N |
| tournaments | tournament_rounds | hasMany | 1:N |
| tournaments | matches | hasMany | 1:N |
| tournaments | books | hasMany | 1:N |
| tournament_registrations | tournaments | belongsTo | N:1 |
| tournament_registrations | teams | belongsTo | N:1 |
| tournament_groups | tournaments | belongsTo | N:1 |
| tournament_groups | tournament_group_standings | hasMany | 1:N |
| tournament_groups | matches | hasMany | 1:N |
| tournament_group_standings | tournament_groups | belongsTo | N:1 |
| tournament_group_standings | teams | belongsTo | N:1 |
| tournament_rounds | tournaments | belongsTo | N:1 |
| tournament_rounds | matches | hasMany | 1:N |
| matches | tournaments | belongsTo | N:1 |
| matches | tournament_rounds | belongsTo | N:1 |
| matches | tournament_groups | belongsTo (nullable) | N:1 |
| matches | teams (home) | belongsTo (nullable) | N:1 |
| matches | teams (away) | belongsTo (nullable) | N:1 |
| matches | teams (winner) | belongsTo (nullable) | N:1 |
| matches | fields | belongsTo | N:1 |
| matches | matches (home_from) | belongsTo (self) | N:1 |
| matches | matches (away_from) | belongsTo (self) | N:1 |

---

## 🔑 FOREIGN KEYS & CASCADE

### **ON DELETE CASCADE:**

Khi xóa record cha → Tự động xóa record con

| Parent Table | Child Table | Cascade Action |
|--------------|-------------|----------------|
| users | owner_profiles | CASCADE |
| users | staffs | CASCADE |
| users | teams | CASCADE |
| users | books | CASCADE |
| users | tournaments | CASCADE |
| owner_profiles | field_clusters | CASCADE |
| field_clusters | fields | CASCADE |
| field_clusters | staffs | CASCADE |
| tournaments | tournament_registrations | CASCADE |
| tournaments | tournament_groups | CASCADE |
| tournaments | tournament_rounds | CASCADE |
| tournaments | matches | CASCADE |
| tournament_groups | tournament_group_standings | CASCADE |
| tournament_rounds | matches | CASCADE |
| teams | team_members | CASCADE |
| teams | tournament_registrations | CASCADE |

### **ON DELETE SET NULL:**

Khi xóa record cha → Đặt foreign key = NULL

| Parent Table | Child Table | Foreign Key |
|--------------|-------------|-------------|
| teams | matches | home_team_id |
| teams | matches | away_team_id |
| teams | matches | winner_team_id |
| tournament_groups | matches | tournament_group_id |

### **ON DELETE RESTRICT:**

Không cho phép xóa nếu còn record con phụ thuộc

| Parent Table | Child Table | Foreign Key |
|--------------|-------------|-------------|
| fields | matches | field_id |

---

## 📊 THỐNG KÊ DATABASE

| Chỉ số | Số lượng |
|--------|----------|
| **Tổng số bảng** | 20 |
| **Tổng số foreign keys** | ~42 |
| **Bảng có nhiều FK nhất** | `matches` (9 FK) |
| **Bảng không có FK** | `roles`, `cache`, `jobs` |
| **Quan hệ 1:N** | ~35 |
| **Quan hệ 1:1** | 2 (user-owner_profile, user-staff) |
| **Self-referencing** | 1 (matches.home_from_match_id, matches.away_from_match_id) |
| **Unique constraints** | 8 |
| **Composite unique keys** | 3 |

---

## 🎯 USE CASES VÀ QUERY PATTERNS

### **UC1: Lấy tất cả giải đấu của 1 user (tổ chức + tham gia)**

```php
// Giải user tổ chức
$organizedTournaments = Tournament::where('organizer_id', $userId)->get();

// Giải user tham gia (qua đội)
$userTeams = Team::where('captain_user_id', $userId)->pluck('id');
$participatingTournamentIds = TournamentRegistration::whereIn('team_id', $userTeams)
    ->where('registration_status', 'approved')
    ->pluck('tournament_id');
$participatingTournaments = Tournament::whereIn('id', $participatingTournamentIds)->get();

// Hoặc dùng relationship
$user->load([
    'captainOfTeams.registrations' => function($q) {
        $q->where('registration_status', 'approved');
    },
    'captainOfTeams.registrations.tournament'
]);
```

### **UC2: Lấy lịch thi đấu của 1 giải**

```php
$tournament = Tournament::with([
    'rounds.matches' => function($q) {
        $q->with(['homeTeam', 'awayTeam', 'field.fieldCluster'])
          ->orderBy('match_date')
          ->orderBy('match_time');
    }
])->findOrFail($tournamentId);
```

### **UC3: Lấy bảng xếp hạng các bảng đấu**

```php
$tournament = Tournament::with([
    'groups.standings' => function($q) {
        $q->with('team')->orderBy('ranking');
    }
])->findOrFail($tournamentId);

foreach ($tournament->groups as $group) {
    echo "Bảng {$group->group_name}:\n";
    foreach ($group->standings as $standing) {
        echo "{$standing->ranking}. {$standing->team->name} - {$standing->points} pts\n";
    }
}
```

### **UC4: Kiểm tra conflict booking**

```php
$conflict = Book::where('field_id', $fieldId)
    ->where('day_book', $date)
    ->where('status', '!=', 'cancelled')
    ->where(function($q) use ($startTime, $endTime) {
        $q->whereBetween('start_time', [$startTime, $endTime])
          ->orWhereBetween('end_time', [$startTime, $endTime])
          ->orWhere(function($q2) use ($startTime, $endTime) {
              $q2->where('start_time', '<=', $startTime)
                 ->where('end_time', '>=', $endTime);
          });
    })
    ->exists();
```

### **UC5: Lấy thông tin chi tiết 1 trận knockout**

```php
$match = TournamentMatch::with([
    'homeTeam',
    'awayTeam',
    'winnerTeam',
    'field.fieldCluster',
    'homeFromMatch.homeTeam',
    'homeFromMatch.awayTeam',
    'awayFromMatch.homeTeam',
    'awayFromMatch.awayTeam',
])->findOrFail($matchId);

// Hiển thị: "Chung kết: [Thắng Bán kết 1] vs [Thắng Bán kết 2]"
```

---

## 🔒 SECURITY & BEST PRACTICES

### **1. Cascade Deletion Safety**

⚠️ **Cảnh báo:** Xóa user sẽ xóa toàn bộ:
- Owner profiles
- Teams (nếu là captain)
- Bookings
- Tournaments (nếu là organizer)
- Staffs

**Giải pháp:**
```php
// Soft delete cho users
$table->softDeletes();

// Hoặc check trước khi xóa
if ($user->tournaments()->exists()) {
    throw new Exception('Không thể xóa user có giải đấu');
}
```

### **2. Unique Constraints**

Đảm bảo data integrity:

```php
// 1 user chỉ là captain 1 đội
$table->unique('captain_user_id', 'teams');

// 1 đội chỉ đăng ký 1 lần
$table->unique(['tournament_id', 'team_id'], 'tournament_registrations');

// Tránh booking trùng
$table->unique(['field_id', 'day_book', 'start_time', 'end_time'], 'books');
```

### **3. Index Performance**

```php
// Tìm kiếm giải theo status và format
$table->index(['status', 'format'], 'tournaments');

// Tìm booking theo user và ngày
$table->index(['user_id', 'day_book', 'status'], 'books');

// Tìm trận đấu theo sân và thời gian
$table->index(['field_id', 'match_date', 'match_time'], 'matches');
```

### **4. Validation Rules**

```php
// Tournament
'max_teams' >= 'min_teams'
'registration_end' > 'registration_start'
'start_date' > 'registration_end'
'end_date' >= 'start_date'

// Match
'home_team_id' != 'away_team_id'
'match_date' >= tournament.start_date
'match_date' <= tournament.end_date

// Booking
'end_time' > 'start_time'
'day_book' >= today()
```

---

## 🚀 MIGRATION ORDER

Thứ tự tạo bảng để tránh foreign key error:

```
1. roles
2. users
3. owner_profiles
4. field_clusters
5. fields
6. field_hourly_prices
7. tournament_session_prices
8. staffs
9. teams
10. team_members
11. books
12. tournaments
13. tournament_registrations
14. tournament_groups
15. tournament_group_standings
16. tournament_rounds
17. matches (tournament_matches)
18. tournament_schedules
19. notifications
20. cache, jobs (system tables)
```

---

## 📚 TÀI LIỆU THAM KHẢO

### **Migrations:**
- `database/migrations/*.php`

### **Models:**
- `app/Models/*.php`

### **Eloquent Relationships:**
- [Laravel Eloquent Relationships](https://laravel.com/docs/11.x/eloquent-relationships)

### **Database Indexes:**
- [MySQL Index Optimization](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)

---

**✨ END OF DOCUMENT ✨**
