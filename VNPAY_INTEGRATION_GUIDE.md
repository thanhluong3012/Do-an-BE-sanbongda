# 💳 HƯỚNG DẪN TÍCH HỢP VNPAY DYNAMIC QR CODE

## 📝 Tổng quan

Hệ thống đã tích hợp sẵn thanh toán VNPay Dynamic QR Code cho booking sân bóng. User có thể thanh toán bằng cách:
- Quét mã QR trên app VNPay/Banking
- Hoặc mở link thanh toán trên điện thoại

## 🔧 Cấu hình VNPay

### 1. Đăng ký tài khoản VNPay

1. Truy cập: https://vnpay.vn
2. Đăng ký tài khoản merchant (doanh nghiệp)
3. Sau khi được duyệt, bạn sẽ nhận được:
   - **TMN_CODE**: Mã merchant
   - **HASH_SECRET**: Chuỗi bí mật (giữ bí mật tuyệt đối)
   - **URL**: Link API VNPay
   - **Return URL**: URL callback sau thanh toán

### 2. Cấu hình trong Laravel

Thêm vào file `.env`:

```env
# VNPay Configuration
VNPAY_TMN_CODE=YOUR_TMN_CODE_HERE
VNPAY_HASH_SECRET=YOUR_HASH_SECRET_HERE

# Môi trường sandbox (test)
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_API_URL=https://sandbox.vnpayment.vn/merchant_webapi/api/transaction

# Môi trường production (sau khi test xong)
# VNPAY_URL=https://vnpayment.vn/paymentv2/vpcpay.html
# VNPAY_API_URL=https://vnpayment.vn/merchant_webapi/api/transaction

# Return URL - VNPay sẽ redirect về đây sau thanh toán
VNPAY_RETURN_URL=http://localhost:8000/api/vnpay/callback

# Frontend URL - Backend sẽ redirect về frontend
FRONTEND_URL=http://localhost:3000
```

**Lưu ý quan trọng:**
- Production phải dùng HTTPS cho `VNPAY_RETURN_URL`
- `FRONTEND_URL` là địa chỉ frontend của bạn (React/Vue/Angular)

### 3. Testing với Sandbox

VNPay cung cấp môi trường test:
- URL: https://sandbox.vnpayment.vn
- Tài khoản test: Liên hệ VNPay để được cấp

**Thẻ test:**
- Số thẻ: `9704198526191432198`
- Tên: `NGUYEN VAN A`
- Ngày hết hạn: `07/15`
- Mật khẩu OTP: `123456`

## 🚀 Cách sử dụng

### Flow thanh toán:

```
1. User tạo booking → POST /api/books
   - Chọn payment_method = 'vnpay'
   - Status: pending, payment_status: pending

2. Owner xác nhận → POST /api/books/{id}/confirm-booking
   - Status: confirmed, payment_status: pending

3. User tạo QR thanh toán → POST /api/vnpay/create-payment/{bookingId}
   Response:
   {
     "booking_id": 1,
     "payment_url": "https://sandbox.vnpayment.vn/...",
     "qr_code_url": "https://sandbox.vnpayment.vn/...",
     "txn_ref": "1_1715875200",
     "amount": 500000,
     "expire_at": "20240516123000",
     "expire_minutes": 15
   }

4. Frontend hiển thị QR Code:
   - Dùng thư viện: qrcode.react hoặc vue-qrcode
   - Hoặc redirect: window.location.href = payment_url

5. User quét QR → Thanh toán trên app

6. VNPay callback → GET /api/vnpay/callback
   - Backend tự động cập nhật:
     - payment_status = 'paid'
     - status = 'confirmed'
     - vnpay_transaction_no = 'xxx'

7. VNPay IPN (backup) → POST /api/vnpay/ipn
   - Webhook notification từ VNPay

8. Frontend check status → GET /api/vnpay/check-status/{bookingId}
   - Poll mỗi 5 giây để cập nhật UI
```

## 📱 API Endpoints

### 1. Tạo Payment URL (Authenticated)
```http
POST /api/vnpay/create-payment/{bookingId}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "message": "Tạo link thanh toán thành công",
  "data": {
    "booking_id": 1,
    "payment_url": "...",
    "qr_code_url": "...",
    "txn_ref": "1_1715875200",
    "amount": 500000,
    "expire_at": "20240516123000"
  }
}
```

### 2. Callback từ VNPay (Public)
```http
GET /api/vnpay/callback?vnp_Amount=50000000&vnp_BankCode=NCB&...

→ Redirect về:
- Success: {FRONTEND_URL}/payment/success?booking_id=1
- Error: {FRONTEND_URL}/payment/error?booking_id=1&code=24
```

### 3. IPN từ VNPay (Public Webhook)
```http
POST /api/vnpay/ipn
Content-Type: application/x-www-form-urlencoded

Response:
{
  "RspCode": "00",
  "Message": "Confirm Success"
}
```

### 4. Check Status (Authenticated)
```http
GET /api/vnpay/check-status/{bookingId}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "booking_id": 1,
    "payment_status": "paid",
    "payment_method": "vnpay",
    "transaction_ref": "1_1715875200",
    "transaction_no": "14123456",
    "bank_code": "NCB",
    "paid_at": "2024-05-16 12:30:00",
    "amount": 500000
  }
}
```

### 5. Query Transaction (Authenticated)
```http
POST /api/vnpay/query-transaction
Authorization: Bearer {token}
Content-Type: application/json

{
  "booking_id": 1
}

Response: (Từ VNPay)
{
  "vnp_ResponseCode": "00",
  "vnp_Message": "Success",
  "vnp_TransactionNo": "14123456",
  ...
}
```

## 🎨 Frontend Integration

### React Example

```jsx
import React, { useState, useEffect } from 'react';
import QRCode from 'qrcode.react';
import axios from 'axios';

function VNPayPayment({ bookingId, onSuccess }) {
  const [paymentData, setPaymentData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState('idle');

  // Tạo payment QR
  const createPayment = async () => {
    setLoading(true);
    try {
      const response = await axios.post(
        `/api/vnpay/create-payment/${bookingId}`,
        {},
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`
          }
        }
      );

      setPaymentData(response.data.data);
      setStatus('waiting');
    } catch (error) {
      alert('Lỗi: ' + error.response?.data?.message);
    } finally {
      setLoading(false);
    }
  };

  // Check status
  const checkStatus = async () => {
    try {
      const response = await axios.get(
        `/api/vnpay/check-status/${bookingId}`,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`
          }
        }
      );

      if (response.data.data.payment_status === 'paid') {
        setStatus('success');
        onSuccess && onSuccess();
      }
    } catch (error) {
      console.error('Check status error:', error);
    }
  };

  // Poll status mỗi 5 giây
  useEffect(() => {
    if (status === 'waiting') {
      const interval = setInterval(checkStatus, 5000);
      return () => clearInterval(interval);
    }
  }, [status]);

  // Auto timeout sau 15 phút
  useEffect(() => {
    if (status === 'waiting') {
      const timeout = setTimeout(() => {
        setStatus('timeout');
      }, 15 * 60 * 1000);
      return () => clearTimeout(timeout);
    }
  }, [status]);

  if (status === 'success') {
    return (
      <div className="success-message">
        <h3>✅ Thanh toán thành công!</h3>
        <p>Booking của bạn đã được xác nhận</p>
      </div>
    );
  }

  if (status === 'timeout') {
    return (
      <div className="error-message">
        <h3>⏰ Hết thời gian thanh toán</h3>
        <button onClick={createPayment}>Tạo mã QR mới</button>
      </div>
    );
  }

  return (
    <div className="vnpay-payment">
      {!paymentData ? (
        <button onClick={createPayment} disabled={loading}>
          {loading ? 'Đang tạo...' : '💳 Thanh toán VNPay'}
        </button>
      ) : (
        <div className="qr-container">
          <h3>Quét mã QR để thanh toán</h3>
          
          {/* QR Code */}
          <div className="qr-code">
            <QRCode 
              value={paymentData.payment_url} 
              size={256}
              level="H"
            />
          </div>

          {/* Thông tin */}
          <div className="payment-info">
            <p>Số tiền: <strong>{paymentData.amount.toLocaleString()} VNĐ</strong></p>
            <p>Mã GD: {paymentData.txn_ref}</p>
            <p>Hết hạn sau: 15 phút</p>
          </div>

          {/* Actions */}
          <div className="actions">
            <a 
              href={paymentData.payment_url} 
              target="_blank" 
              className="btn btn-primary"
            >
              📱 Mở app VNPay
            </a>
            <button onClick={checkStatus} className="btn btn-secondary">
              🔄 Kiểm tra trạng thái
            </button>
          </div>

          {/* Status */}
          {status === 'waiting' && (
            <p className="status">
              <span className="spinner">⏳</span> Đang chờ thanh toán...
            </p>
          )}
        </div>
      )}
    </div>
  );
}

export default VNPayPayment;
```

### Vue Example

```vue
<template>
  <div class="vnpay-payment">
    <button v-if="!paymentData" @click="createPayment" :disabled="loading">
      {{ loading ? 'Đang tạo...' : '💳 Thanh toán VNPay' }}
    </button>

    <div v-else class="qr-container">
      <h3>Quét mã QR để thanh toán</h3>
      
      <qrcode-vue 
        :value="paymentData.payment_url" 
        :size="256" 
        level="H"
      />

      <div class="payment-info">
        <p>Số tiền: <strong>{{ formatCurrency(paymentData.amount) }}</strong></p>
        <p>Mã GD: {{ paymentData.txn_ref }}</p>
        <p>Hết hạn sau: 15 phút</p>
      </div>

      <div class="actions">
        <a :href="paymentData.payment_url" target="_blank" class="btn btn-primary">
          📱 Mở app VNPay
        </a>
        <button @click="checkStatus" class="btn btn-secondary">
          🔄 Kiểm tra trạng thái
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import QrcodeVue from 'qrcode.vue';
import axios from 'axios';

export default {
  components: { QrcodeVue },
  props: ['bookingId'],
  data() {
    return {
      paymentData: null,
      loading: false,
      status: 'idle',
      interval: null
    };
  },
  methods: {
    async createPayment() {
      this.loading = true;
      try {
        const response = await axios.post(
          `/api/vnpay/create-payment/${this.bookingId}`,
          {},
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem('token')}`
            }
          }
        );
        this.paymentData = response.data.data;
        this.startPolling();
      } catch (error) {
        alert('Lỗi: ' + error.response?.data?.message);
      } finally {
        this.loading = false;
      }
    },
    async checkStatus() {
      try {
        const response = await axios.get(
          `/api/vnpay/check-status/${this.bookingId}`,
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem('token')}`
            }
          }
        );

        if (response.data.data.payment_status === 'paid') {
          this.stopPolling();
          this.$emit('success');
        }
      } catch (error) {
        console.error('Check status error:', error);
      }
    },
    startPolling() {
      this.interval = setInterval(this.checkStatus, 5000);
    },
    stopPolling() {
      if (this.interval) {
        clearInterval(this.interval);
      }
    },
    formatCurrency(amount) {
      return amount.toLocaleString('vi-VN') + ' VNĐ';
    }
  },
  beforeUnmount() {
    this.stopPolling();
  }
};
</script>
```

## 🔒 Bảo mật

1. **Validate Secure Hash**: Luôn kiểm tra `vnp_SecureHash` từ VNPay
2. **HTTPS**: Production phải dùng HTTPS cho callback URL
3. **IP Whitelist**: Thêm IP của VNPay vào whitelist (tùy chọn)
4. **Log mọi giao dịch**: Để audit và debug
5. **Timeout**: Xử lý case user không thanh toán sau 15 phút
6. **Idempotency**: Kiểm tra trùng lặp giao dịch trong IPN

## 📊 VNPay Response Codes

| Code | Ý nghĩa |
|------|---------|
| 00 | Giao dịch thành công |
| 07 | Trừ tiền thành công. Giao dịch bị nghi ngờ (gian lận) |
| 09 | Chưa đăng ký Internet Banking |
| 10 | Xác thực thông tin sai quá 3 lần |
| 11 | Đã hết hạn chờ thanh toán (15 phút) |
| 12 | Thẻ/Tài khoản bị khóa |
| 13 | Sai mật khẩu OTP |
| 24 | Khách hàng hủy giao dịch |
| 51 | Tài khoản không đủ số dư |
| 65 | Vượt quá hạn mức giao dịch trong ngày |
| 75 | Ngân hàng đang bảo trì |
| 79 | Nhập sai mật khẩu quá số lần quy định |
| 99 | Các lỗi khác |

## 🐛 Troubleshooting

### Lỗi: Invalid signature
- Kiểm tra `VNPAY_HASH_SECRET` có đúng không
- Kiểm tra params có bị thay đổi trên đường truyền không

### Lỗi: Callback không hoạt động
- Kiểm tra `VNPAY_RETURN_URL` có đúng và accessible không
- Production phải dùng HTTPS
- Kiểm tra firewall/nginx có block không

### QR Code không hiển thị
- Kiểm tra `vnp_BankCode` = 'VNPAYQR'
- Kiểm tra payment_url có hợp lệ không

### Thanh toán thành công nhưng DB chưa update
- Kiểm tra IPN endpoint có hoạt động không
- Kiểm tra log trong `storage/logs/laravel.log`
- VNPay có thể gọi IPN delayed, cần retry mechanism

## 📞 Hỗ trợ

- VNPay Hotline: 1900 55 55 77
- Email: support@vnpay.vn
- Tài liệu: https://sandbox.vnpayment.vn/apis/
- Môi trường test: https://sandbox.vnpayment.vn

## ✅ Checklist Go-Live

- [ ] Đã test đầy đủ trên sandbox
- [ ] Đã chuyển sang production credentials
- [ ] Đã đổi URL sang production (https://vnpayment.vn)
- [ ] Callback URL dùng HTTPS
- [ ] Đã setup logging và monitoring
- [ ] Đã test IPN webhook
- [ ] Đã test các case: thành công, thất bại, timeout
- [ ] Đã thông báo cho VNPay về IP server (nếu cần)

---

**Lưu ý**: File này chỉ dùng để hướng dẫn. Không commit `VNPAY_HASH_SECRET` lên Git!
