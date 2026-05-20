# 🏆 LUỒNG THANH TOÁN VIETQR CHO GIẢI ĐẤU

## 📋 Tổng quan

Luồng thanh toán VietQR cho giải đấu hoạt động tương tự như booking bình thường, đảm bảo tính nhất quán trong hệ thống.

---

## 🔄 Flow Thanh toán Tournament

### 1️⃣ **Tạo Giải Đấu**

**API:** `POST /api/tournaments`

**Request:**
```json
{
  "name": "Giải đấu ABC",
  "description": "Mô tả giải đấu",
  "format": "knockout",
  "field_type": "7v7",
  "max_teams": 16,
  "min_teams": 8,
  "registration_start": "2026-05-20",
  "registration_end": "2026-06-20",
  "start_date": "2026-07-01",
  "end_date": "2026-07-31",
  "field_cluster_id": 5,
  "payment_method": "vietqr",
  "bookings": [
    {
      "field_id": 20,
      "date": "2026-07-01",
      "session_type": "evening"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Tạo giải đấu và đặt 1 sân thành công",
  "data": {
    "tournament": {
      "id": 1,
      "name": "Giải đấu ABC",
      "payment_method": "vietqr",
      "payment_status": "pending"
    },
    "bookings": [...],
    "total_price": 2500000,
    "payment_info": {
      "method": "vietqr",
      "status": "pending",
      "total_amount": 2500000,
      "instruction": "Vui lòng gọi API /api/vietqr/create-payment/tournament/1 để tạo mã VietQR...",
      "next_step": "create_vietqr",
      "api_endpoint": "/api/vietqr/create-payment/tournament/1"
    }
  }
}
```

**Trạng thái sau bước này:**
- `tournament.payment_status = 'pending'`
- `tournament.payment_date = null`
- `tournament.payment_proof = null`
- `bookings[].payment_status = 'pending'`

---

### 2️⃣ **Tạo Mã VietQR**

**API:** `POST /api/vietqr/create-payment/tournament/{tournamentId}`

**Headers:**
```
Authorization: Bearer {ORGANIZER_TOKEN}
```

**Response:**
```json
{
  "success": true,
  "message": "Tạo mã QR thanh toán giải đấu thành công",
  "data": {
    "tournament_id": 1,
    "qr_code_url": "https://img.vietqr.io/image/970422-0123456789-compact.png?amount=2500000&addInfo=T1&accountName=NGUYEN%20VAN%20A",
    "amount": 2500000,
    "amount_formatted": "2.500.000 VNĐ",
    "transfer_content": "T1",
    "account_info": {
      "bank_id": "970422",
      "account_no": "0123456789",
      "account_name": "NGUYEN VAN A",
      "is_owner_account": true
    },
    "instruction": "Vui lòng quét mã QR hoặc chuyển khoản đúng số tiền và nội dung. Sau đó upload bill qua API /api/tournaments/1/upload-payment-proof để xác nhận thanh toán.",
    "next_step": "upload_bill",
    "upload_api": "/api/tournaments/1/upload-payment-proof"
  }
}
```

**Lưu ý:**
- QR code được tạo dựa trên **thông tin ngân hàng của Owner** (không phải platform)
- Nội dung chuyển khoản: `T{tournamentId}` (VD: T1, T15)
- Tổng tiền = Tổng `total_price` của tất cả bookings thuộc giải

---

### 3️⃣ **User Quét Mã → Chuyển Khoản → Upload Bill**

**Bước thực hiện:**
1. User mở app ngân hàng
2. Quét mã QR từ `qr_code_url`
3. Xác nhận chuyển khoản ĐÚNG số tiền và nội dung
4. Chụp ảnh bill chuyển khoản thành công
5. Upload ảnh lên **Supabase/Cloudinary** (frontend xử lý)
6. Gửi URL ảnh về backend

**API:** `POST /api/tournaments/{id}/upload-payment-proof`

**Request:**
```json
{
  "payment_proof_url": "https://storage.supabase.co/.../bill-t1.jpg"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật bill thành công. Chờ chủ sân xác nhận thanh toán.",
  "data": {
    "tournament_id": 1,
    "payment_proof_url": "https://storage.supabase.co/.../bill-t1.jpg",
    "payment_status": "pending",
    "synced_bookings": 5
  }
}
```

**Trạng thái sau bước này:**
- `tournament.payment_proof = "https://..."` ✅
- `tournament.payment_status = 'pending'` (vẫn chờ owner confirm)
- Tất cả `bookings[].payment_proof` cũng được cập nhật đồng bộ

---

### 4️⃣ **Owner Xác Nhận Thanh Toán**

**API:** `PUT /api/tournaments/{id}/confirm-payment`

**Headers:**
```
Authorization: Bearer {OWNER_TOKEN}
```

**Request:**
```json
{
  "action": "approve",
  "note": "Đã nhận tiền"
}
```

**Response (Approve):**
```json
{
  "success": true,
  "message": "Xác nhận thanh toán thành công",
  "data": {
    "tournament_id": 1,
    "payment_status": "paid",
    "payment_date": "2026-05-19T15:30:00.000000Z"
  }
}
```

**Trạng thái sau bước này:**
- `tournament.payment_status = 'paid'` ✅
- `tournament.payment_date = now()`
- Tất cả `bookings[].payment_status = 'paid'` ✅
- Tất cả `bookings[].status = 'completed'` ✅

**Response (Reject):**
```json
{
  "action": "reject",
  "note": "Sai số tiền"
}
```

**Trạng thái sau bước này:**
- `tournament.payment_status = 'failed'`
- `tournament.status = 'cancelled'`
- Tất cả `bookings[].payment_status = 'failed'`
- Tất cả `bookings[].status = 'cancelled'`

---

## 🔐 Phân Quyền

| Endpoint | Quyền | Ghi chú |
|----------|-------|---------|
| `POST /api/tournaments` | Member/Owner | Ai cũng có thể tổ chức giải |
| `POST /api/vietqr/create-payment/tournament/{id}` | Organizer của giải | Chỉ người tạo giải mới được tạo QR |
| `POST /api/tournaments/{id}/upload-payment-proof` | Organizer của giải | Chỉ người tạo giải mới upload bill |
| `PUT /api/tournaments/{id}/confirm-payment` | Owner của cụm sân | Chỉ chủ sân xác nhận thanh toán |

---

## 📊 So Sánh Flow: Booking vs Tournament

| Bước | Booking Bình Thường | Tournament |
|------|---------------------|------------|
| 1. Tạo | `POST /api/books` | `POST /api/tournaments` |
| 2. Owner Confirm Booking | `PUT /api/books/{id}/confirm` | `PUT /api/tournaments/{id}/confirm-bookings` |
| 3. Tạo QR | `POST /api/vietqr/create-payment/{bookingId}` | `POST /api/vietqr/create-payment/tournament/{tournamentId}` |
| 4. Upload Bill | `POST /api/books/{id}/upload-payment-proof` | `POST /api/tournaments/{id}/upload-payment-proof` |
| 5. Owner Confirm Payment | `PUT /api/books/{id}/confirm-payment` | `PUT /api/tournaments/{id}/confirm-payment` |

**✅ Nhất quán hoàn toàn!**

---

## ⚠️ Lưu Ý Quan Trọng

### 1. **Payment Method**
- Chỉ hỗ trợ: `"cash"` hoặc `"vietqr"`
- ❌ **KHÔNG dùng** `"transfer"` (đã bị deprecated)

### 2. **Yêu Cầu Owner Cấu Hình Bank Info**
Nếu chọn `payment_method = "vietqr"`, owner phải có đầy đủ:
```php
owner_profile.bank_id      // Mã ngân hàng (VD: 970422)
owner_profile.account_no   // Số tài khoản
owner_profile.account_name // Tên chủ tài khoản
```

Nếu thiếu → API trả về lỗi:
```json
{
  "success": false,
  "message": "Cụm sân này chưa cấu hình thông tin ngân hàng VietQR..."
}
```

### 3. **Đồng Bộ Payment Status**
Khi owner xác nhận thanh toán tournament:
- ✅ `tournament.payment_status` → `'paid'`
- ✅ Tất cả `bookings[].payment_status` → `'paid'`
- ✅ Tất cả `bookings[].status` → `'completed'`

Tất cả được xử lý trong **1 transaction**!

### 4. **Nội Dung Chuyển Khoản**
- Booking: `BOOK-{bookingId}` (VD: BOOK-123)
- Tournament: `T{tournamentId}` (VD: T1, T15)

---

## 🧪 Test Flow

### Test với Seeder:
```bash
php artisan migrate:fresh --seed
```

**Lưu ý:** Sau khi seed:
- Tất cả tournament có `payment_status = 'pending'` (chưa thanh toán)
- Để test thanh toán, cần:
  1. Login as organizer
  2. Gọi API tạo VietQR
  3. Upload bill
  4. Login as owner
  5. Xác nhận thanh toán

---

## 🛠️ Troubleshooting

### 1. "Cụm sân chưa cấu hình thông tin ngân hàng VietQR"
**Nguyên nhân:** Owner chưa cập nhật bank info

**Giải pháp:**
```sql
UPDATE owner_profiles 
SET bank_id = '970422', 
    account_no = '0123456789', 
    account_name = 'NGUYEN VAN A'
WHERE id = {owner_id};
```

### 2. "User chưa upload bill chuyển khoản VietQR"
**Nguyên nhân:** Organizer chưa gọi API upload bill

**Giải pháp:** Gọi `POST /api/tournaments/{id}/upload-payment-proof`

### 3. "Còn X booking giải đấu chưa được xác nhận"
**Nguyên nhân:** Owner chưa confirm tất cả bookings

**Giải pháp:** Gọi `PUT /api/tournaments/{id}/confirm-bookings` trước

---

## 📚 Tài Liệu Liên Quan

- [VIETQR_INTEGRATION_GUIDE.md](./VIETQR_INTEGRATION_GUIDE.md) - Hướng dẫn tích hợp VietQR
- [VIETQR_QUICK_START.md](./VIETQR_QUICK_START.md) - Quick start VietQR
- [VIETQR_FRONTEND_GUIDE.md](./VIETQR_FRONTEND_GUIDE.md) - Hướng dẫn frontend
