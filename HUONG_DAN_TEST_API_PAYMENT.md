# 🧪 HƯỚNG DẪN TEST API PAYMENT & QR CODE

## 📋 DANH SÁCH API MỚI

### 1️⃣ Upload QR Code (Owner Only)
**Endpoint:** `POST /api/owner/upload-qr`

**Headers:**
```
Authorization: Bearer {owner_token}
Content-Type: multipart/form-data
```

**Body:**
```
qr_code: [File ảnh QR code - JPG/PNG, max 2MB]
```

**Response Success:**
```json
{
  "success": true,
  "message": "Upload QR code thành công",
  "data": {
    "qr_code_url": "http://localhost:8000/storage/owner_qr_codes/owner_1_qr_1733483200.jpg",
    "owner_profile": {
      "id": 1,
      "qr_code_image": "owner_qr_codes/owner_1_qr_1733483200.jpg",
      ...
    }
  }
}
```

---

### 2️⃣ Lấy QR Code của Owner
**Endpoint:** `GET /api/owner/qr-code`

**Headers:**
```
Authorization: Bearer {owner_token}
```

**Response Success:**
```json
{
  "success": true,
  "message": "Thông tin QR code",
  "data": {
    "qr_code_url": "http://localhost:8000/storage/owner_qr_codes/owner_1_qr_1733483200.jpg",
    "has_qr_code": true
  }
}
```

---

### 3️⃣ Tạo Giải Đấu với Payment
**Endpoint:** `POST /api/tournaments`

**Headers:**
```
Authorization: Bearer {user_token}
Content-Type: application/json
```

**Body Example - Chuyển khoản:**
```json
{
  "name": "Giải Bóng Đá Mùa Xuân 2026",
  "description": "Giải đấu thường niên",
  "format": "knockout",
  "field_type": "5v5",
  "max_teams": 16,
  "min_teams": 8,
  "registration_start": "2026-04-10",
  "registration_end": "2026-04-20",
  "start_date": "2026-04-25",
  "end_date": "2026-05-05",
  "field_cluster_id": 1,
  "payment_method": "transfer",
  "bookings": [
    {
      "field_id": 1,
      "date": "2026-04-25",
      "session_type": "morning"
    },
    {
      "field_id": 2,
      "date": "2026-04-26",
      "session_type": "afternoon"
    }
  ]
}
```

**Body Example - Tiền mặt:**
```json
{
  "name": "Giải Bóng Đá Mùa Hè 2026",
  ...
  "payment_method": "cash",
  "bookings": [...]
}
```

**Response Success:**
```json
{
  "success": true,
  "message": "Tạo giải đấu và đặt 2 sân thành công",
  "data": {
    "tournament": {...},
    "bookings": [...],
    "total_bookings": 2,
    "total_price": 1100000,
    "payment_info": {
      "method": "transfer",
      "status": "pending",
      "total_amount": 1100000,
      "qr_code_url": "http://localhost:8000/storage/owner_qr_codes/owner_1_qr.jpg",
      "instruction": "Vui lòng quét mã QR để chuyển khoản ĐÚNG số tiền: 1,100,000 VNĐ và ghi nội dung: BOOK-T1",
      "transfer_note": "BOOK-T1"
    }
  }
}
```

**Response Error - Chưa có QR Code:**
```json
{
  "success": false,
  "message": "Cụm sân này chưa cấu hình QR code chuyển khoản. Vui lòng chọn phương thức \"Tiền mặt\" hoặc chọn cụm sân khác.",
  "data": null,
  "code": 400
}
```

---

## 🧪 CÁCH TEST BẰNG POSTMAN/INSOMNIA

### Bước 1: Login để lấy token
```
POST /api/login
Body: {
  "email": "owner@test.com",
  "password": "123456"
}
```
→ Lưu lại `access_token`

### Bước 2: Upload QR Code (Owner)
```
POST /api/owner/upload-qr
Headers: 
  Authorization: Bearer {token}
Body (form-data):
  qr_code: [Chọn file ảnh QR]
```

### Bước 3: Kiểm tra QR Code đã upload
```
GET /api/owner/qr-code
Headers: 
  Authorization: Bearer {token}
```

### Bước 4: Login user khác (để tạo giải)
```
POST /api/login
Body: {
  "email": "captain1@test.com",
  "password": "123456"
}
```

### Bước 5: Tạo giải đấu với payment
```
POST /api/tournaments
Headers:
  Authorization: Bearer {user_token}
Body: [Dùng JSON ở trên]
```

---

## 🎯 TEST SCENARIOS

### ✅ Test Case 1: Owner upload QR code thành công
1. Login với owner account
2. Upload ảnh QR → Kết quả: Success

### ✅ Test Case 2: User tạo giải với chuyển khoản thành công
1. Login với user account
2. POST /api/tournaments với `payment_method: "transfer"`
3. Kiểm tra response có `qr_code_url`

### ⚠️ Test Case 3: Tạo giải nhưng owner chưa có QR
1. Chọn field_cluster_id của owner chưa upload QR
2. POST /api/tournaments với `payment_method: "transfer"`
3. Kết quả: Error 400 - "Chưa cấu hình QR code"

### ✅ Test Case 4: Tạo giải với tiền mặt
1. POST /api/tournaments với `payment_method: "cash"`
2. Kết quả: Success (không cần QR code)

---

## 📊 DATABASE CHANGES

### Bảng `owner_profiles`
```sql
qr_code_image VARCHAR(255) NULL -- Đường dẫn ảnh QR code
```

### B ảng `tournaments`
```sql
payment_method ENUM('cash', 'transfer') NULL
payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending'
payment_date DATETIME NULL
payment_proof VARCHAR(255) NULL -- Ảnh bill CK (dùng sau)
```

---

## 🚀 NEXT FEATURES (Tương lai)

1. **Upload Bill Chuyển Khoản**
   - API: `POST /api/tournaments/{id}/upload-payment-proof`
   - Owner xác nhận thanh toán

2. **Owner xác nhận thanh toán**
   - API: `PUT /api/tournaments/{id}/confirm-payment`
   - Đổi `payment_status` từ `pending` → `paid`

3. **Admin quản lý thanh toán**
   - Xem danh sách giải chưa thanh toán
   - Thống kê doanh thu

---

## 📝 NOTES

- File QR code được lưu tại: `storage/app/public/owner_qr_codes/`
- URL public: `{domain}/storage/owner_qr_codes/filename.jpg`
- Max file size: 2MB
- Accepted formats: JPG, PNG, JPEG
- Mỗi owner chỉ có 1 QR code (upload mới sẽ ghi đè QR cũ)

---

Chúc test thành công! 🎉
