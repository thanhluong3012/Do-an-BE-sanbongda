# HƯỚNG DẪN API QUẢN LÝ ĐẶT SÂN (BOOKING)

## Tổng quan
API quản lý đặt sân cho phép người dùng đặt sân bóng theo giờ, xem lịch đặt, cập nhật và hủy đặt sân.

## Các endpoint

### 1. Lấy danh sách đặt sân
**Endpoint:** `GET /api/books`
**Middleware:** `jwt.auth`
**Quyền:**
- `admin`: Xem tất cả booking
- `owner`: Xem booking của sân thuộc về họ
- `member`: Xem booking của chính họ

**Response thành công (200):**
```json
{
    "success": true,
    "message": "Danh sách đặt sân",
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "field_id": 1,
            "day_book": "2026-02-27",
            "start_time": "08:00:00",
            "end_time": "10:00:00",
            "total_price": "200000.00",
            "created_at": "2026-02-26T14:53:40.000000Z",
            "updated_at": "2026-02-26T14:53:40.000000Z",
            "user": {
                "id": 1,
                "name": "Khách hàng",
                "email": "customer@gmail.com"
            },
            "field": {
                "id": 1,
                "field_type": "5v5",
                "status": "available",
                "field_cluster": {
                    "id": 1,
                    "name": "Sân Bóng ABC"
                }
            }
        }
    ],
    "code": 200
}
```

---

### 2. Tạo booking mới (Đặt sân)
**Endpoint:** `POST /api/books`
**Middleware:** `jwt.auth`
**Quyền:** Tất cả user đã đăng nhập

**Request Body:**
```json
{
    "field_id": 1,
    "day_book": "2026-02-27",
    "start_time": "08:00",
    "end_time": "10:00",
    "total_price": 200000
}
```

**Validation:**
- `field_id`: required, integer, exists trong bảng fields
- `day_book`: required, date, phải từ hôm nay trở đi
- `start_time`: required, format HH:mm
- `end_time`: required, format HH:mm, phải sau start_time
- `total_price`: required, numeric, >= 0

**Logic kiểm tra:**
1. Sân phải ở trạng thái `available`
2. Không được trùng khung giờ với booking khác cùng ngày, cùng sân

**Response thành công (201):**
```json
{
    "success": true,
    "message": "Đặt sân thành công",
    "data": {
        "id": 4,
        "user_id": 1,
        "field_id": 1,
        "day_book": "2026-02-27",
        "start_time": "08:00:00",
        "end_time": "10:00:00",
        "total_price": "200000.00",
        "created_at": "2026-02-26T15:00:00.000000Z",
        "updated_at": "2026-02-26T15:00:00.000000Z",
        "user": {...},
        "field": {...}
    },
    "code": 201
}
```

**Response lỗi (400):**
```json
{
    "success": false,
    "message": "Khung giờ này đã được đặt",
    "data": null,
    "code": 400
}
```

---

### 3. Xem chi tiết booking
**Endpoint:** `GET /api/books/{id}`
**Middleware:** `jwt.auth`
**Quyền:**
- `admin`: Xem tất cả
- `owner`: Xem booking của sân thuộc về họ
- `member`: Chỉ xem booking của chính họ

**Response thành công (200):**
```json
{
    "success": true,
    "message": "Chi tiết đặt sân",
    "data": {
        "id": 1,
        "user_id": 1,
        "field_id": 1,
        "day_book": "2026-02-27",
        "start_time": "08:00:00",
        "end_time": "10:00:00",
        "total_price": "200000.00",
        "created_at": "2026-02-26T14:53:40.000000Z",
        "updated_at": "2026-02-26T14:53:40.000000Z",
        "user": {...},
        "field": {...}
    },
    "code": 200
}
```

---

### 4. Cập nhật booking
**Endpoint:** `PUT/PATCH /api/books/{id}`
**Middleware:** `jwt.auth`
**Quyền:**
- `admin`: Cập nhật tất cả
- `owner`: Cập nhật booking của sân thuộc về họ
- `member`: Chỉ cập nhật booking của chính họ

**Request Body (tất cả field đều optional):**
```json
{
    "field_id": 2,
    "day_book": "2026-02-28",
    "start_time": "14:00",
    "end_time": "16:00",
    "total_price": 250000
}
```

**Response thành công (200):**
```json
{
    "success": true,
    "message": "Cập nhật booking thành công",
    "data": {
        "id": 1,
        "user_id": 1,
        "field_id": 2,
        "day_book": "2026-02-28",
        "start_time": "14:00:00",
        "end_time": "16:00:00",
        "total_price": "250000.00",
        "created_at": "2026-02-26T14:53:40.000000Z",
        "updated_at": "2026-02-26T15:30:00.000000Z",
        "user": {...},
        "field": {...}
    },
    "code": 200
}
```

---

### 5. Hủy booking (Xóa)
**Endpoint:** `DELETE /api/books/{id}`
**Middleware:** `jwt.auth`
**Quyền:**
- `admin`: Xóa tất cả
- `owner`: Xóa booking của sân thuộc về họ
- `member`: Chỉ xóa booking của chính họ

**Response thành công (200):**
```json
{
    "success": true,
    "message": "Hủy đặt sân thành công",
    "data": null,
    "code": 200
}
```

---

## Tài khoản test

### Customer (Member)
- Email: `customer@gmail.com`
- Password: `password123`
- Role: `member`

### Admin
- Email: `admin@sanbongda.com`
- Password: `admin123`
- Role: `admin`

### Owner
- Email: `owner@gmail.com`
- Password: `password123`
- Role: `owner`

---

## Ví dụ sử dụng với CURL

### 1. Đăng nhập để lấy token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "customer@gmail.com",
    "password": "password123"
  }'
```

### 2. Lấy danh sách booking (với token)
```bash
curl -X GET http://localhost:8000/api/books \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 3. Đặt sân mới
```bash
curl -X POST http://localhost:8000/api/books \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "field_id": 1,
    "day_book": "2026-02-27",
    "start_time": "08:00",
    "end_time": "10:00",
    "total_price": 200000
  }'
```

### 4. Xem chi tiết booking
```bash
curl -X GET http://localhost:8000/api/books/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 5. Cập nhật booking
```bash
curl -X PUT http://localhost:8000/api/books/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "start_time": "14:00",
    "end_time": "16:00",
    "total_price": 250000
  }'
```

### 6. Hủy booking
```bash
curl -X DELETE http://localhost:8000/api/books/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Lưu ý
1. Tất cả API đều yêu cầu JWT token (trừ login/register)
2. Token được truyền qua header: `Authorization: Bearer {token}`
3. Kiểm tra khung giờ trống trước khi đặt để tránh conflict
4. Member chỉ có thể quản lý booking của chính mình
5. Owner có thể quản lý tất cả booking của sân thuộc về họ
6. Admin có quyền cao nhất, quản lý toàn bộ booking
