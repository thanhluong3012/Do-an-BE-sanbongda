# Tóm Tắt Dữ Liệu Seed

## ✅ Hoàn Thành!

Đã tạo thành công toàn bộ dữ liệu seed cho hệ thống QLSB.

## 📊 Thống Kê Tổng Quan

| Loại dữ liệu | Số lượng | Ghi chú |
|--------------|----------|---------|
| **Roles** | 4 | admin, owner, staff, member |
| **Users** | 70 | 10 cá nhân + 30 captain + 20 owner + 10 organizer |
| **Đội bóng** | 30 | Mỗi đội có 1 captain |
| **Thành viên đội** | 300 | 10 người/đội |
| **Chủ sân** | 20 | Mỗi chủ sân có 1-2 cụm sân |
| **Cụm sân** | 28 | 4 sân con/cụm |
| **Sân con** | 112 | Loại 5v5, 7v7, 11v11 |
| **Giá sân** | 336+ | Theo loại sân và ngày |
| **Giải đấu** | 10 | Nhiều thể thức khác nhau |
| **Đăng ký giải** | 161 | Các đội tham gia giải |
| **Booking giải** | 493 | Lịch thi đấu đã book sân |

## 🔑 Thông Tin Đăng Nhập

### Khách hàng cá nhân (10 tài khoản)
```
Email: individual1@gmail.com → individual10@gmail.com
Password: password123
Phone: 090xxxxxxx
```

### Đội trưởng (30 tài khoản)
```
Email: captain1@gmail.com → captain30@gmail.com
Password: password123
Phone: 091xxxxxxx
```

### Chủ sân (20 tài khoản)
```
Email: owner1@gmail.com → owner20@gmail.com
Password: password123
Phone: 092xxxxxxx
```

### Ban tổ chức giải (10 tài khoản)
```
Email: organizer1@gmail.com → organizer10@gmail.com
Password: password123
Phone: 093xxxxxxx
```

## 🏟️ Cấu Trúc Cụm Sân

- **28 cụm sân** phân bố cho 20 chủ sân
- Mỗi cụm có **4 sân con**
- Loại sân ngẫu nhiên: **5v5**, **7v7**, **11v11**
- Giờ mở cửa: **06:00 - 23:00**

### Giá Sân

| Loại sân | Ngày thường | Cuối tuần |
|----------|-------------|-----------|
| 5v5 | 300,000đ/h | 360,000đ/h |
| 7v7 | 500,000đ/h | 600,000đ/h |
| 11v11 | 800,000đ/h | 960,000đ/h |

## ⚽ Đội Bóng

- **30 đội** với tên đa dạng
- Mỗi đội có:
  - 1 đội trưởng (captain)
  - 10 thành viên
  - Thông tin đầy đủ: tên, số áo, vị trí, thống kê

## 🏆 Giải Đấu

| # | Thể thức | Số đội | Loại sân | Đội đăng ký |
|---|----------|--------|----------|-------------|
| 1 | knockout | 4 | 5v5 | 4 |
| 2 | round_robin | 5 | 5v5 | 5 |
| 3 | knockout | 8 | 7v7 | 8 |
| 4 | round_robin | 10 | 7v7 | 10 |
| 5 | knockout | 12 | 11v11 | 12 |
| 6 | knockout | 16 | 11v11 | 16 |
| 7 | knockout | 32 | 11v11 | 30 |
| 8 | group_knockout | 16 | 7v7 | 16 |
| 9 | knockout | 64 | 11v11 | 30 |
| 10 | group_knockout | 32 | 11v11 | 30 |

**Tổng: 161 đội đã đăng ký tham gia các giải**

## 📅 Lịch Thi Đấu

- Tất cả giải đã có **lịch thi đấu** (Tournament Schedules)
- **493 booking sân** cho các trận đấu
- Thời gian: Chủ yếu buổi tối (18h-23h)
- Status: Tất cả đã confirmed và paid
- Logic tự động tránh trùng lặp booking

## 🚀 Cách Sử Dụng

### Chạy lại seeder
```bash
cd BE
php artisan migrate:fresh --seed
```

### Xem dữ liệu
```bash
# Đếm users
php artisan tinker
>>> User::count()
>>> Team::count()
>>> Tournament::count()
```

## 📝 Notes

- Tất cả tài khoản đã **verify email**
- Tất cả thanh toán đã **hoàn tất** (paid)
- Code ID được generate **ngẫu nhiên** 12 ký tự
- Booking sân tránh trùng lặp bằng logic check trước khi tạo
- Thời gian seed: ~24 giây

## 📚 Tài Liệu Liên Quan

- [DATABASE_SEEDER_GUIDE.md](./DATABASE_SEEDER_GUIDE.md) - Hướng dẫn chi tiết
- [ComprehensiveSeeder.php](./database/seeders/ComprehensiveSeeder.php) - Source code

---

**Ngày tạo:** 13/05/2026  
**Version:** 1.0  
**Status:** ✅ Production Ready
