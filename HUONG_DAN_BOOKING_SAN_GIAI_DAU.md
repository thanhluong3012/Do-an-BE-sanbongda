# HƯỚNG DẪN BOOKING SÂN CHO GIẢI ĐẤU

## 🎯 LOGIC HOÀN CHỈNH

```
1. Tạo Giải → Chọn Cụm Sân
2. Book Sân Con → Chọn sân nào, ngày nào, buổi nào (morning/afternoon/evening/full_day)
3. Sinh Lịch → Tự động phân bổ trận đấu vào sân đã book
```

---

## 📋 CÁC API MỚI

### 1. **Quản lý Giá Thuê Sân Giải Đấu**

#### Xem giá (Public)
```http
GET /api/tournament-session-prices?field_cluster_id=1&field_type=5v5
```

#### Tạo giá (Admin/Owner)
```http
POST /api/tournament-session-prices
Authorization: Bearer {token}

{
  "field_cluster_id": 1,
  "field_type": "5v5",
  "day_type": "weekday",        // weekday | weekend
  "session_type": "morning",    // morning | afternoon | evening | full_day
  "price_per_session": 500000
}
```

**Session Types:**
- `morning`: 06:00-12:00 (6 tiếng)
- `afternoon`: 12:00-18:00 (6 tiếng)
- `evening`: 18:00-23:00 (5 tiếng)
- `full_day`: 06:00-23:00 (17 tiếng)

#### Cập nhật giá
```http
PUT /api/tournament-session-prices/{id}
{
  "price_per_session": 600000
}
```

---

### 2. **Book Sân Cho Giải Đấu**

#### Book sân (Organizer)
```http
POST /api/tournaments/{id}/book-fields
Authorization: Bearer {token}

{
  "bookings": [
    {
      "field_id": 1,
      "date": "2026-04-02",
      "session_type": "morning"
    },
    {
      "field_id": 2,
      "date": "2026-04-02",
      "session_type": "afternoon"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "bookings": [...],
    "total_bookings": 2,
    "total_price": 1100000
  }
}
```

#### Xem sân đã book
```http
GET /api/tournaments/{id}/field-bookings
```

#### Hủy booking
```http
DELETE /api/tournaments/{id}/field-bookings/{booking_id}
```

---

### 3. **Generate Schedule (Cập nhật)**

**Thay đổi:** Không cần truyền `field_ids` và `time_slots` nữa. Hệ thống tự động lấy từ bookings!

```http
POST /api/tournaments/{id}/generate-schedule
Authorization: Bearer {token}

{
  "start_date": "2026-04-02",
  "days_between_rounds": 2
}
```

**Logic tự động:**
- ✅ Lấy `field_ids` từ bookings
- ✅ Tạo `time_slots` dựa vào thời lượng trận (5v5=60p, 7v7=80p, 11v11=90p)
- ✅ Validation: Phải book sân trước khi generate

---

## 🧪 DỮ LIỆU TEST

Chạy seeder:
```bash
php artisan db:seed --class=TestDataSeeder
```

**Đã tạo sẵn:**
- ✅ Bảng giá 5v5 (weekday & weekend, 4 buổi)
- ✅ 8 bookings: 2 sân × 2 ngày × 2 buổi

---

## 🚀 FLOW TEST NHANH

### Bước 1: Login
```bash
POST /api/auth/login
{
  "email": "admin@test.com",
  "password": "123456"
}
```

### Bước 2: Xem sân đã book
```bash
GET /api/tournaments/1/field-bookings
```

### Bước 3: Generate schedule
```bash
POST /api/tournaments/1/generate-schedule
{
  "start_date": "2026-04-02"
}
```

### Bước 4: Xem lịch thi đấu
```bash
GET /api/tournaments/1/bracket
```

**Kết quả:** Sẽ thấy ngày giờ thi đấu, sân nào, thời lượng mỗi trận!

---

## 💡 TÍNH NĂNG NỔI BẬT

1. **Tự động tính giá** dựa vào ngày (weekday/weekend) và buổi
2. **Validate trùng lịch** - Không cho book sân đã có booking khác
3. **Tự động phân bổ** time slots dựa vào thời lượng trận
4. **Ngăn hủy booking** nếu đã generate schedule
5. **Bảo mật** - Chỉ organizer được book/hủy

---

## 📊 THỜI LƯỢNG TRẬN

- **5v5:** 60 phút (30p × 2 hiệp) + 15 phút nghỉ
- **7v7:** 80 phút (40p × 2 hiệp) + 15 phút nghỉ
- **11v11:** 90 phút (45p × 2 hiệp) + 15 phút nghỉ

---

## ⚠️ LƯU Ý

1. Phải **book sân trước** khi generate schedule
2. Sân phải thuộc **cụm sân của giải**
3. Giải phải có **bảng giá** tương ứng
4. Không hủy booking sau khi đã generate schedule
