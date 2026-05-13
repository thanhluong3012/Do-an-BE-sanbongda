# Hướng Dẫn Seeder Dữ Liệu Hệ Thống QLSB

## Tổng Quan

File `ComprehensiveSeeder.php` tạo dữ liệu demo đầy đủ cho toàn bộ hệ thống quản lý sân bóng đá, bao gồm người dùng, đội bóng, cụm sân và giải đấu.

## Cách Chạy Seeder

```bash
cd BE
php artisan migrate:fresh --seed
```

## Dữ Liệu Được Tạo

### 1. Roles (4 roles)
- **admin**: Quản lý toàn bộ hệ thống
- **owner**: Chủ sở hữu cụm sân bóng
- **staff**: Nhân viên thuộc khu vực sân bóng
- **member**: Người dùng đã đăng nhập vào hệ thống

### 2. Tài Khoản Cá Nhân (10 tài khoản)
- **Email**: `individual1@gmail.com` đến `individual10@gmail.com`
- **Password**: `password123`
- **Role**: member
- **Số điện thoại**: 090xxxxxxx
- **Mục đích**: Đặt sân cá nhân

### 3. Tài Khoản Đội Trưởng (30 tài khoản)
- **Email**: `captain1@gmail.com` đến `captain30@gmail.com`
- **Password**: `password123`
- **Role**: member
- **Số điện thoại**: 091xxxxxxx

#### Đội Bóng (30 đội)
Mỗi đội trưởng có 1 đội bóng với:
- Tên đội đa dạng (Rồng, Phượng, Báo, Hổ, Sư Tử, v.v.)
- Short name viết tắt
- Thông tin liên hệ (phone, email)

#### Thành Viên Đội (300 thành viên)
Mỗi đội có 10 thành viên với:
- Tên ngẫu nhiên (họ + tên đệm + tên)
- Số áo (1-10)
- Vị trí: Thủ môn, Hậu vệ, Tiền vệ, Tiền đạo
- Thống kê: total_goals (0-15), yellow_cards (0-3), red_cards (0-1)
- Code ID (CMND/CCCD)

### 4. Chủ Sân (20 tài khoản)
- **Email**: `owner1@gmail.com` đến `owner20@gmail.com`
- **Password**: `password123`
- **Role**: owner
- **Số điện thoại**: 092xxxxxxx
- Mỗi chủ sân có owner profile với:
  - Business name
  - Địa chỉ
  - Code ID

#### Cụm Sân (28 cụm)
- Mỗi chủ sân có 1-2 cụm sân
- Thông tin cụm sân:
  - Tên cụm
  - Địa chỉ tại TP.HCM (Quận 1-12)
  - Giờ mở cửa: 06:00 - 23:00
  - Số điện thoại liên hệ

#### Sân Con (112 sân)
- Mỗi cụm sân có 4 sân con
- Loại sân ngẫu nhiên: **5v5**, **7v7**, **11v11**
- Status: active
- Tên sân: Sân 1, Sân 2, Sân 3, Sân 4

#### Giá Sân (Field Hourly Prices)
Mỗi loại sân trong mỗi cụm có giá theo:

**Ngày thường (weekday):**
- 5v5: 300,000 VNĐ/giờ
- 7v7: 500,000 VNĐ/giờ
- 11v11: 800,000 VNĐ/giờ

**Cuối tuần (weekend):**
- 5v5: 360,000 VNĐ/giờ (x1.2)
- 7v7: 600,000 VNĐ/giờ (x1.2)
- 11v11: 960,000 VNĐ/giờ (x1.2)

### 5. Giải Đấu (10 giải)

#### Thể Thức Giải Đấu

| Giải | Thể thức | Số đội | Loại sân |
|------|----------|--------|----------|
| 1 | knockout | 4 | 5v5 |
| 2 | round_robin | 5 | 5v5 |
| 3 | knockout | 8 | 7v7 |
| 4 | round_robin | 10 | 7v7 |
| 5 | knockout | 12 | 11v11 |
| 6 | knockout | 16 | 11v11 |
| 7 | knockout | 32 | 11v11 |
| 8 | group_knockout | 16 | 7v7 |
| 9 | knockout | 64 | 11v11 |
| 10 | group_knockout | 32 | 11v11 |

#### Tài Khoản Ban Tổ Chức
- **Email**: `organizer1@gmail.com` đến `organizer10@gmail.com`
- **Password**: `password123`
- **Role**: member
- **Số điện thoại**: 093xxxxxxx

#### Thông Tin Giải Đấu
- **Status**: open
- **Đăng ký**: 30 ngày trước → 7 ngày trước
- **Thi đấu**: Bắt đầu sau 7 ngày từ hiện tại, kéo dài 30 ngày
- **Schedule published**: true
- **Payment method**: transfer
- **Payment status**: paid
- **Field cluster**: Được chọn ngẫu nhiên từ các cụm sân phù hợp

### 6. Đăng Ký Giải Đấu (161 registrations)
- Các đội được phân bổ vào giải ngẫu nhiên
- **Registration status**: approved
- **Payment status**: paid
- **Payment method**: transfer
- **Payment date**: Ngẫu nhiên trong khoảng thời gian đăng ký

### 7. Booking Sân Cho Giải (493 bookings)
- **Booking type**: tournament
- **Status**: confirmed
- **Payment status**: paid
- **Payment method**: transfer
- **Thời gian**: Chủ yếu buổi tối (18h-23h)
- **Lịch thi đấu**: Tự động phân bổ theo số trận và số sân
- **Tránh trùng lặp**: Logic kiểm tra và chọn sân thay thế nếu bị trùng

#### Logic Booking Giải Đấu
- Mỗi trận đấu kéo dài 2 giờ
- Tối đa 4 trận/ngày tùy theo số sân có sẵn
- Giá booking theo loại ngày (weekday/weekend) và loại sân

## Số Lượng Tổng Kết

```
✅ 4 roles
✅ 10 tài khoản đặt sân cá nhân
✅ 30 tài khoản đội trưởng
✅ 30 đội bóng
✅ 300 thành viên đội bóng
✅ 20 chủ sân
✅ 28 cụm sân
✅ 112 sân con
✅ 336 field hourly prices (112 sân x 2 loại ngày + unique prices)
✅ 10 giải đấu
✅ 10 tài khoản ban tổ chức
✅ 161 đội đăng ký giải đấu
✅ 493 booking sân cho giải đấu
✅ Lịch thi đấu tương ứng với từng giải
```

## Thông Tin Đăng Nhập Demo

### Tài khoản đặt sân cá nhân
```
Email: individual1@gmail.com (đến individual10@gmail.com)
Password: password123
```

### Tài khoản đội trưởng
```
Email: captain1@gmail.com (đến captain30@gmail.com)
Password: password123
```

### Tài khoản chủ sân
```
Email: owner1@gmail.com (đến owner20@gmail.com)
Password: password123
```

### Tài khoản ban tổ chức giải
```
Email: organizer1@gmail.com (đến organizer10@gmail.com)
Password: password123
```

## Lưu Ý

1. **Code ID**: Được tạo ngẫu nhiên 12 ký tự
2. **Email verified**: Tất cả tài khoản đã verify email
3. **Status**: Tất cả users và fields đều ở trạng thái active (boolean true)
4. **Tournament Schedule**: Tự động tạo lịch và booking sân dựa trên format giải
5. **Conflict Resolution**: Logic tự động tránh booking trùng lặp giữa các giải

## Cấu Trúc File

```
BE/database/seeders/
├── ComprehensiveSeeder.php     # Seeder chính tạo tất cả dữ liệu
├── RoleSeeder.php              # Tạo roles (được gọi trước)
├── DatabaseSeeder.php          # Main seeder runner
└── (các seeder khác đã disable)
```

## Mở Rộng

Để tạo thêm dữ liệu hoặc tùy chỉnh:
1. Sửa các biến trong `ComprehensiveSeeder.php`:
   - `$fieldTypes`: Thay đổi loại sân
   - `$tournamentFormats`: Thêm/sửa thể thức giải đấu
   - `$positions`, `$firstNames`, etc.: Tùy chỉnh tên

2. Gọi lại seeder:
   ```bash
   php artisan migrate:fresh --seed
   ```

## Troubleshooting

### Lỗi trùng lặp booking
- Đã xử lý bằng logic kiểm tra và chọn sân thay thế

### Lỗi không tìm thấy cụm sân phù hợp
- Đảm bảo có đủ cụm sân với các loại sân cần thiết

### Lỗi enum values
- Kiểm tra migration để đảm bảo các enum values khớp với seeder
