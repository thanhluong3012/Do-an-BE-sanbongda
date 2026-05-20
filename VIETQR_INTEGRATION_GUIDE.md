# 🎯 HƯỚNG DẪN TÍCH HỢP VIETQR CHO HỆ THỐNG ĐẶT SÂN BÓNG

## 📋 Tổng quan

VietQR là tiêu chuẩn mã QR thanh toán thống nhất của các ngân hàng Việt Nam, cho phép chuyển khoản nhanh chóng bằng cách quét mã QR.

### ✨ Ưu điểm
- ✅ Miễn phí, không cần đăng ký tài khoản gateway
- ✅ Không qua trung gian, tiền về thẳng tài khoản ngân hàng
- ✅ Hỗ trợ tất cả ngân hàng tại Việt Nam
- ✅ Tạo QR code dễ dàng qua API hoặc URL

### ⚠️ Lưu ý
- ❌ Không có callback tự động như VNPay/Momo
- ❌ User phải upload bill để chứng minh đã thanh toán
- ❌ Owner phải xác nhận thanh toán thủ công

---

## 🔧 Cài đặt

### 1. Cấu hình Environment

Mở file `.env` và thêm:

```env
# VietQR Payment Configuration
VIETQR_BANK_ID=970422                    # Mã ngân hàng (VD: 970422 = MB Bank)
VIETQR_ACCOUNT_NO=0123456789             # Số tài khoản nhận tiền
VIETQR_ACCOUNT_NAME=NGUYEN VAN A         # Tên chủ tài khoản

# Optional - VietQR API credentials (nếu muốn dùng API trả phí)
VIETQR_CLIENT_ID=
VIETQR_API_KEY=

# Template QR: compact, print, qr_only
VIETQR_TEMPLATE=compact

# Frontend URL
FRONTEND_URL=http://localhost:3000
```

### 2. Lấy mã ngân hàng (BANK_ID)

Danh sách mã ngân hàng phổ biến:

| Ngân hàng | Mã (BANK_ID) |
|-----------|--------------|
| Vietcombank | 970436 |
| Techcombank | 970407 |
| MB Bank | 970422 |
| VietinBank | 970415 |
| BIDV | 970418 |
| ACB | 970416 |
| Sacombank | 970403 |
| TPBank | 970423 |
| VPBank | 970432 |
| Agribank | 970405 |

**Xem danh sách đầy đủ**: [https://api.vietqr.io/v2/banks](https://api.vietqr.io/v2/banks)

hoặc gọi API:
```bash
GET /api/vietqr/banks
```

### 3. Chạy Migration

```bash
php artisan migrate
```

Migration sẽ xóa các trường VNPay cũ (nếu có) khỏi bảng `books`.

---

## 🚀 Cách sử dụng

### Luồng thanh toán VietQR

```
1. User tạo booking → status: pending
2. Owner xác nhận booking → status: confirmed
3. User chọn phương thức "vietqr"
4. Backend tạo QR code VietQR
5. User quét QR → chuyển khoản qua app ngân hàng
6. User upload ảnh bill (chứng từ)
7. Owner xác nhận thanh toán → payment_status: paid
```

---

## 📡 API Endpoints

### 1. Tạo VietQR Code

**POST** `/api/vietqr/create-payment/{bookingId}`

**Headers:**
```
Authorization: Bearer {USER_TOKEN}
```

**Response:**
```json
{
  "success": true,
  "message": "Tạo mã QR thanh toán thành công",
  "data": {
    "booking_id": 123,
    "qr_code_url": "https://img.vietqr.io/image/970422-0123456789-compact.png?amount=500000&addInfo=BOOK-123&accountName=NGUYEN%20VAN%20A",
    "amount": 500000,
    "amount_formatted": "500.000 VNĐ",
    "transfer_content": "BOOK-123",
    "account_info": {
      "bank_id": "970422",
      "account_no": "0123456789",
      "account_name": "NGUYEN VAN A"
    },
    "instruction": "Vui lòng quét mã QR hoặc chuyển khoản đúng số tiền và nội dung. Sau đó upload bill để xác nhận thanh toán.",
    "next_step": "upload_bill"
  }
}
```

### 2. Upload Bill Thanh toán

**POST** `/api/books/{id}/upload-payment-proof`

**Headers:**
```
Authorization: Bearer {USER_TOKEN}
```

**Body:**
```json
{
  "payment_proof_url": "https://your-storage.com/bill.jpg"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Upload bill thành công. Vui lòng chờ chủ sân xác nhận thanh toán.",
  "data": {
    "booking_id": 123,
    "payment_status": "pending",
    "payment_proof": "https://your-storage.com/bill.jpg"
  }
}
```

### 3. Owner xác nhận thanh toán

**PUT** `/api/books/{id}/confirm-payment`

**Headers:**
```
Authorization: Bearer {OWNER_TOKEN}
```

**Body:**
```json
{
  "action": "approve",
  "note": "Đã nhận tiền"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Xác nhận thanh toán thành công",
  "data": {
    "booking_id": 123,
    "payment_status": "paid",
    "payment_confirmed_at": "2026-05-19T14:30:00.000000Z"
  }
}
```

### 4. Kiểm tra trạng thái thanh toán

**GET** `/api/vietqr/check-status/{bookingId}`

**Headers:**
```
Authorization: Bearer {TOKEN}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "booking_id": 123,
    "payment_status": "pending",
    "payment_method": "vietqr",
    "payment_proof": "https://...",
    "amount": 500000,
    "status_message": "Đã upload bill, đang chờ xác nhận từ chủ sân"
  }
}
```

### 5. Lấy danh sách ngân hàng (Public API)

**GET** `/api/vietqr/banks`

**Response:**
```json
{
  "success": true,
  "data": {
    "banks": [
      {
        "id": 1,
        "name": "Ngân hàng TMCP Á Châu",
        "code": "ACB",
        "bin": "970416",
        "shortName": "ACB"
      },
      ...
    ],
    "total": 50
  }
}
```

---

## 🎨 Tích hợp Frontend

### 1. Hiển thị QR Code

```jsx
// React example
import QRCode from 'react-qr-code';

function PaymentPage() {
  const [qrData, setQrData] = useState(null);

  const generateQR = async (bookingId) => {
    const response = await fetch(`/api/vietqr/create-payment/${bookingId}`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });
    
    const data = await response.json();
    setQrData(data.data);
  };

  return (
    <div>
      {qrData && (
        <div>
          {/* Hiển thị QR từ URL */}
          <img src={qrData.qr_code_url} alt="VietQR" />
          
          {/* Hoặc dùng thư viện QR */}
          <QRCode value={qrData.qr_code_url} size={256} />
          
          <p>Số tiền: {qrData.amount_formatted}</p>
          <p>Nội dung CK: {qrData.transfer_content}</p>
          <p>Số TK: {qrData.account_info.account_no}</p>
          <p>Tên TK: {qrData.account_info.account_name}</p>
        </div>
      )}
    </div>
  );
}
```

### 2. Upload Bill

```jsx
// Upload lên Supabase/Cloudinary/S3 trước, rồi gửi URL về backend
const uploadBill = async (file, bookingId) => {
  // 1. Upload lên storage
  const url = await uploadToSupabase(file);
  
  // 2. Gửi URL về backend
  const response = await fetch(`/api/books/${bookingId}/upload-payment-proof`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ payment_proof_url: url }),
  });
  
  return response.json();
};
```

---

## 🔐 Bảo mật

### 1. Validation
- ✅ Kiểm tra booking thuộc về user
- ✅ Kiểm tra booking status = 'confirmed'
- ✅ Kiểm tra payment_status = 'pending'
- ✅ Validate URL khi upload bill

### 2. Authorization
- User chỉ tạo QR cho booking của mình
- User chỉ upload bill cho booking của mình
- Owner chỉ xác nhận thanh toán cho sân của mình
- Admin có quyền xác nhận tất cả

---

## 🎯 Testing

### Test với Postman

1. **Tạo booking**
```
POST /api/books
Body: {
  "field_id": 1,
  "day_book": "2026-05-20",
  "start_time": "18:00:00",
  "end_time": "20:00:00"
}
```

2. **Owner xác nhận booking**
```
POST /api/books/{id}/confirm-booking
Body: {
  "action": "approve"
}
```

3. **User tạo QR VietQR**
```
POST /api/vietqr/create-payment/{bookingId}
```

4. **User upload bill**
```
POST /api/books/{id}/upload-payment-proof
Body: {
  "payment_proof_url": "https://..."
}
```

5. **Owner xác nhận thanh toán**
```
PUT /api/books/{id}/confirm-payment
Body: {
  "action": "approve"
}
```

---

## 🆚 So sánh VietQR vs VNPay

| Feature | VietQR | VNPay |
|---------|--------|-------|
| Chi phí | Miễn phí | 1-2% phí giao dịch |
| Đăng ký | Không cần | Cần đăng ký, KYC |
| Callback tự động | ❌ | ✅ |
| Upload bill | Bắt buộc | Không cần |
| Xác nhận thủ công | Có | Không |
| Tốc độ nhận tiền | Tức thì | 1-3 ngày làm việc |
| Hỗ trợ | Tự xử lý | Có support team |

---

## 📚 Tài liệu tham khảo

- VietQR API: https://www.vietqr.io/
- Danh sách ngân hàng: https://api.vietqr.io/v2/banks
- VietQR Specification: https://vietqr.net/

---

## ❓ FAQ

**Q: Tại sao phải upload bill?**  
A: Vì VietQR không có webhook/callback tự động, nên cần bill để chứng minh đã chuyển khoản.

**Q: Có thể tự động xác nhận thanh toán không?**  
A: Có, bằng cách tích hợp API truy vấn sao kê ngân hàng (tốn phí và phức tạp).

**Q: QR code hết hạn không?**  
A: QR code không hết hạn, nhưng booking có thể bị hủy nếu quá lâu không thanh toán.

**Q: User chuyển sai số tiền thì sao?**  
A: Owner sẽ thấy bill và có thể từ chối, yêu cầu chuyển lại đúng số tiền.

---

## 🔄 Migration từ VNPay sang VietQR

Nếu bạn đã dùng VNPay trước đó:

1. ✅ Chạy migration: `php artisan migrate`
2. ✅ Cập nhật `.env` với thông tin VietQR
3. ✅ Routes đã được cập nhật tự động
4. ✅ Các booking cũ vẫn hoạt động bình thường

---

Hệ thống đã sẵn sàng sử dụng VietQR! 🎉
