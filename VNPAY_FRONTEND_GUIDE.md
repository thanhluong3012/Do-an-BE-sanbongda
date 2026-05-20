# 🎨 HƯỚNG DẪN TÍCH HỢP VNPAY QR CODE - FRONTEND

> Hướng dẫn chi tiết từng bước để tích hợp thanh toán VNPay QR Code vào frontend React/Vue

---

## 📋 Mục lục

1. [Yêu cầu chuẩn bị](#yêu-cầu-chuẩn-bị)
2. [Cài đặt thư viện](#cài-đặt-thư-viện)
3. [Cấu hình API](#cấu-hình-api)
4. [Flow thanh toán](#flow-thanh-toán)
5. [API Endpoints](#api-endpoints)
6. [Component React](#component-react)
7. [Component Vue](#component-vue)
8. [Styling CSS](#styling-css)
9. [Integration Examples](#integration-examples)
10. [Testing](#testing)
11. [Troubleshooting](#troubleshooting)

---

## 🎯 Yêu cầu chuẩn bị

### Backend đã setup:
- ✅ Migration đã chạy
- ✅ VNPay Service đã cài
- ✅ API endpoints đã sẵn sàng
- ✅ .env đã cấu hình VNPay credentials

### Frontend cần có:
- React 16.8+ hoặc Vue 3+
- Axios hoặc Fetch API
- Token authentication (JWT/Bearer)
- Node.js 14+ và npm/yarn

---

## 📦 Cài đặt thư viện

### Cho React:

```bash
# Cài thư viện QR Code
npm install qrcode.react

# Hoặc với yarn
yarn add qrcode.react

# Optional: Axios cho HTTP requests
npm install axios
```

### Cho Vue 3:

```bash
# Cài thư viện QR Code
npm install qrcode.vue3

# Hoặc với yarn
yarn add qrcode.vue3

# Optional: Axios cho HTTP requests
npm install axios
```

---

## ⚙️ Cấu hình API

### 1. Tạo/Cập nhật file `.env`

**React (Create React App):**
```env
REACT_APP_API_URL=http://localhost:8000/api
```

**React (Vite):**
```env
VITE_API_URL=http://localhost:8000/api
```

**Vue (Vite):**
```env
VITE_API_URL=http://localhost:8000/api
```

### 2. Tạo file cấu hình API

**`src/config/api.js`:**
```javascript
// Cho React (CRA)
export const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Cho React/Vue (Vite)
// export const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export const API_ENDPOINTS = {
  // VNPay endpoints
  vnpay: {
    createPayment: (bookingId) => `${API_URL}/vnpay/create-payment/${bookingId}`,
    checkStatus: (bookingId) => `${API_URL}/vnpay/check-status/${bookingId}`,
    queryTransaction: `${API_URL}/vnpay/query-transaction`,
  },
  
  // Booking endpoints
  booking: {
    list: `${API_URL}/books`,
    detail: (id) => `${API_URL}/books/${id}`,
    create: `${API_URL}/books`,
  }
};

// Helper để lấy token
export const getAuthToken = () => {
  return localStorage.getItem('token') || sessionStorage.getItem('token');
};

// Helper để tạo headers
export const getAuthHeaders = () => {
  const token = getAuthToken();
  return {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  };
};
```

---

## 🔄 Flow thanh toán

### Luồng xử lý đầy đủ:

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USER TẠO BOOKING                                         │
│    - Chọn sân, giờ, ngày                                    │
│    - Chọn payment_method = "vnpay"                          │
│    - POST /api/books                                         │
│    → Status: pending, payment_status: pending               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. OWNER XÁC NHẬN BOOKING                                   │
│    - Owner login và xem booking                              │
│    - POST /api/books/{id}/confirm-booking                    │
│    → Status: confirmed, payment_status: pending             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. USER TẠO QR CODE                                         │
│    - Click "Thanh toán VNPay"                                │
│    - POST /api/vnpay/create-payment/{bookingId}              │
│    → Nhận payment_url và thông tin QR                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. HIỂN THỊ QR CODE                                         │
│    - Render QR code từ payment_url                           │
│    - Hiển thị số tiền, thời gian còn lại                    │
│    - Start polling check status (mỗi 5s)                     │
│    - Start countdown timer (15 phút)                         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. USER QUÉT QR VÀ THANH TOÁN                               │
│    - Mở app VNPay/Banking                                    │
│    - Quét QR code                                            │
│    - Xác nhận thanh toán trên app                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. VNPAY XỬ LÝ                                              │
│    - VNPay nhận thanh toán                                   │
│    - VNPay gọi callback backend                              │
│    - Backend cập nhật payment_status = "paid"                │
│    - Backend gửi notification cho user & owner               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. FRONTEND NHẬN KẾT QUẢ                                    │
│    - Polling nhận payment_status = "paid"                    │
│    - Stop polling và countdown                               │
│    - Hiển thị "Thanh toán thành công"                        │
│    - Redirect về trang booking detail                        │
└─────────────────────────────────────────────────────────────┘
```

### Timeline:

```
0:00 ──────→ User tạo QR
0:05 ──────→ Poll 1st time (GET check-status)
0:10 ──────→ Poll 2nd time
0:15 ──────→ Poll 3rd time
  ...
0:30 ──────→ User quét QR và thanh toán
0:35 ──────→ Poll detect payment_status = "paid"
0:35 ──────→ Show success → Redirect
15:00 ─────→ QR code expired (nếu chưa thanh toán)
```

---

## 🌐 API Endpoints

### 1. Tạo QR thanh toán

**Request:**
```http
POST /api/vnpay/create-payment/{bookingId}
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Tạo link thanh toán thành công",
  "data": {
    "booking_id": 123,
    "payment_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?...",
    "qr_code_url": "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?...",
    "txn_ref": "123_1716096000",
    "amount": 500000,
    "order_info": "Thanh toan dat san #123",
    "expire_at": "2026-05-19 15:30:00",
    "expire_minutes": 15
  }
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Booking không tồn tại hoặc không thuộc về bạn"
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Booking chưa được xác nhận"
}
```

---

### 2. Kiểm tra trạng thái thanh toán

**Request:**
```http
GET /api/vnpay/check-status/{bookingId}
Authorization: Bearer YOUR_TOKEN
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Lấy trạng thái thanh toán thành công",
  "data": {
    "booking_id": 123,
    "payment_status": "paid",
    "payment_method": "vnpay",
    "transaction_ref": "123_1716096000",
    "transaction_no": "14380888",
    "bank_code": "NCB",
    "paid_at": "2026-05-19 14:20:30",
    "response_code": "00",
    "amount": 500000
  }
}
```

**Payment Status Values:**
- `"pending"` - Chưa thanh toán
- `"paid"` - Đã thanh toán thành công
- `"failed"` - Thanh toán thất bại

---

## ⚛️ Component React

### File: `src/components/VNPayPayment/VNPayPayment.jsx`

```jsx
import React, { useState, useEffect, useRef } from 'react';
import QRCode from 'qrcode.react';
import axios from 'axios';
import { API_ENDPOINTS, getAuthHeaders } from '../../config/api';
import './VNPayPayment.css';

const VNPayPayment = ({ 
  bookingId, 
  amount, 
  bookingInfo = {},
  onSuccess, 
  onError,
  onCancel 
}) => {
  // States
  const [paymentData, setPaymentData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState('idle'); // idle, waiting_payment, paid, failed, expired
  const [timeLeft, setTimeLeft] = useState(900); // 15 phút = 900 giây
  const [error, setError] = useState('');

  // Refs để cleanup intervals
  const statusIntervalRef = useRef(null);
  const timerIntervalRef = useRef(null);

  // Format số tiền
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND'
    }).format(value);
  };

  // Format thời gian còn lại
  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  // Tạo QR thanh toán
  const createPayment = async () => {
    setLoading(true);
    setError('');
    
    try {
      const response = await axios.post(
        API_ENDPOINTS.vnpay.createPayment(bookingId),
        {},
        { headers: getAuthHeaders() }
      );

      if (response.data.success) {
        setPaymentData(response.data.data);
        setStatus('waiting_payment');
        startStatusPolling();
        startTimer();
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Không thể tạo mã thanh toán. Vui lòng thử lại.';
      setError(errorMessage);
      if (onError) onError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  // Kiểm tra trạng thái thanh toán
  const checkPaymentStatus = async () => {
    try {
      const response = await axios.get(
        API_ENDPOINTS.vnpay.checkStatus(bookingId),
        { headers: getAuthHeaders() }
      );

      if (response.data.success) {
        const { payment_status } = response.data.data;
        
        if (payment_status === 'paid') {
          setStatus('paid');
          stopStatusPolling();
          stopTimer();
          if (onSuccess) {
            setTimeout(() => onSuccess(response.data.data), 1500);
          }
        } else if (payment_status === 'failed') {
          setStatus('failed');
          stopStatusPolling();
          stopTimer();
          if (onError) onError('Thanh toán thất bại');
        }
      }
    } catch (err) {
      console.error('Check status error:', err);
      // Không hiển thị error cho polling vì sẽ retry
    }
  };

  // Bắt đầu polling status
  const startStatusPolling = () => {
    // Check ngay lần đầu
    checkPaymentStatus();
    
    // Sau đó check mỗi 5 giây
    statusIntervalRef.current = setInterval(() => {
      checkPaymentStatus();
    }, 5000);
  };

  // Dừng polling status
  const stopStatusPolling = () => {
    if (statusIntervalRef.current) {
      clearInterval(statusIntervalRef.current);
      statusIntervalRef.current = null;
    }
  };

  // Bắt đầu countdown timer
  const startTimer = () => {
    timerIntervalRef.current = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev <= 1) {
          setStatus('expired');
          stopTimer();
          stopStatusPolling();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
  };

  // Dừng countdown timer
  const stopTimer = () => {
    if (timerIntervalRef.current) {
      clearInterval(timerIntervalRef.current);
      timerIntervalRef.current = null;
    }
  };

  // Hủy thanh toán
  const handleCancel = () => {
    stopStatusPolling();
    stopTimer();
    setStatus('idle');
    setPaymentData(null);
    setTimeLeft(900);
    if (onCancel) onCancel();
  };

  // Cleanup khi component unmount
  useEffect(() => {
    return () => {
      stopStatusPolling();
      stopTimer();
    };
  }, []);

  // Render loading state
  if (loading) {
    return (
      <div className="vnpay-payment">
        <div className="vnpay-loading">
          <div className="spinner"></div>
          <p>Đang tạo mã thanh toán...</p>
        </div>
      </div>
    );
  }

  // Render idle state (chưa tạo QR)
  if (status === 'idle' && !paymentData) {
    return (
      <div className="vnpay-payment">
        <div className="vnpay-start">
          <div className="payment-info">
            <h3>Thanh toán VNPay</h3>
            <div className="amount-display">
              <span className="label">Số tiền thanh toán:</span>
              <span className="amount">{formatCurrency(amount)}</span>
            </div>
            {bookingInfo.fieldName && (
              <div className="booking-info">
                <p><strong>Sân:</strong> {bookingInfo.fieldName}</p>
                <p><strong>Ngày:</strong> {bookingInfo.date}</p>
                <p><strong>Giờ:</strong> {bookingInfo.time}</p>
              </div>
            )}
          </div>
          
          {error && (
            <div className="vnpay-error">
              <p>{error}</p>
            </div>
          )}

          <button 
            className="btn-create-payment" 
            onClick={createPayment}
            disabled={loading}
          >
            <span className="icon">📱</span>
            Tạo mã QR thanh toán
          </button>

          <div className="payment-note">
            <p>✓ Quét mã QR bằng ứng dụng VNPay hoặc Banking App</p>
            <p>✓ Mã QR có hiệu lực trong 15 phút</p>
            <p>✓ Thanh toán an toàn và bảo mật</p>
          </div>
        </div>
      </div>
    );
  }

  // Render QR code (đang chờ thanh toán)
  if (status === 'waiting_payment' && paymentData) {
    const isExpiringSoon = timeLeft <= 120; // Còn 2 phút

    return (
      <div className="vnpay-payment">
        <div className="vnpay-qr-container">
          <h3>Quét mã QR để thanh toán</h3>
          
          <div className="qr-wrapper">
            <QRCode 
              value={paymentData.payment_url} 
              size={280}
              level="H"
              includeMargin={true}
              className="qr-code"
            />
          </div>

          <div className="payment-details">
            <div className="detail-row">
              <span className="label">Số tiền:</span>
              <span className="value amount">{formatCurrency(paymentData.amount)}</span>
            </div>
            <div className="detail-row">
              <span className="label">Mã giao dịch:</span>
              <span className="value">{paymentData.txn_ref}</span>
            </div>
          </div>

          <div className={`timer-display ${isExpiringSoon ? 'warning' : ''}`}>
            <span className="timer-icon">⏱️</span>
            <span className="timer-text">
              {isExpiringSoon ? 'Sắp hết hạn: ' : 'Thời gian còn lại: '}
            </span>
            <span className="timer-value">{formatTime(timeLeft)}</span>
          </div>

          <div className="instructions">
            <p className="instruction-title">Hướng dẫn thanh toán:</p>
            <ol>
              <li>Mở ứng dụng <strong>VNPay</strong> hoặc <strong>Banking App</strong> của bạn</li>
              <li>Chọn chức năng <strong>Quét mã QR</strong></li>
              <li>Quét mã QR code bên trên</li>
              <li>Xác nhận thông tin và hoàn tất thanh toán</li>
            </ol>
          </div>

          <div className="loading-indicator">
            <div className="spinner-small"></div>
            <span>Đang chờ thanh toán...</span>
          </div>

          <button 
            className="btn-cancel" 
            onClick={handleCancel}
          >
            Hủy thanh toán
          </button>
        </div>
      </div>
    );
  }

  // Render success state
  if (status === 'paid') {
    return (
      <div className="vnpay-payment">
        <div className="vnpay-success">
          <div className="success-icon">✓</div>
          <h3>Thanh toán thành công!</h3>
          <p>Đơn đặt sân của bạn đã được thanh toán.</p>
          <div className="success-amount">
            {formatCurrency(amount)}
          </div>
          <p className="success-note">
            Bạn sẽ được chuyển về trang chi tiết đặt sân...
          </p>
        </div>
      </div>
    );
  }

  // Render failed state
  if (status === 'failed') {
    return (
      <div className="vnpay-payment">
        <div className="vnpay-failed">
          <div className="failed-icon">✕</div>
          <h3>Thanh toán thất bại</h3>
          <p>Giao dịch không thành công. Vui lòng thử lại.</p>
          <button 
            className="btn-retry" 
            onClick={() => {
              setStatus('idle');
              setPaymentData(null);
              setTimeLeft(900);
              createPayment();
            }}
          >
            Thử lại
          </button>
          <button 
            className="btn-cancel" 
            onClick={handleCancel}
          >
            Hủy
          </button>
        </div>
      </div>
    );
  }

  // Render expired state
  if (status === 'expired') {
    return (
      <div className="vnpay-payment">
        <div className="vnpay-expired">
          <div className="expired-icon">⏰</div>
          <h3>Mã QR đã hết hạn</h3>
          <p>Mã thanh toán đã hết hiệu lực sau 15 phút.</p>
          <button 
            className="btn-retry" 
            onClick={() => {
              setStatus('idle');
              setPaymentData(null);
              setTimeLeft(900);
              createPayment();
            }}
          >
            Tạo mã mới
          </button>
          <button 
            className="btn-cancel" 
            onClick={handleCancel}
          >
            Hủy
          </button>
        </div>
      </div>
    );
  }

  return null;
};

export default VNPayPayment;
```

---

## 🎨 Component Vue 3

### File: `src/components/VNPayPayment.vue`

```vue
<template>
  <div class="vnpay-payment">
    <!-- Loading State -->
    <div v-if="loading" class="vnpay-loading">
      <div class="spinner"></div>
      <p>Đang tạo mã thanh toán...</p>
    </div>

    <!-- Idle State (Chưa tạo QR) -->
    <div v-else-if="status === 'idle' && !paymentData" class="vnpay-start">
      <div class="payment-info">
        <h3>Thanh toán VNPay</h3>
        <div class="amount-display">
          <span class="label">Số tiền thanh toán:</span>
          <span class="amount">{{ formatCurrency(amount) }}</span>
        </div>
        <div v-if="bookingInfo.fieldName" class="booking-info">
          <p><strong>Sân:</strong> {{ bookingInfo.fieldName }}</p>
          <p><strong>Ngày:</strong> {{ bookingInfo.date }}</p>
          <p><strong>Giờ:</strong> {{ bookingInfo.time }}</p>
        </div>
      </div>

      <div v-if="error" class="vnpay-error">
        <p>{{ error }}</p>
      </div>

      <button 
        class="btn-create-payment" 
        @click="createPayment"
        :disabled="loading"
      >
        <span class="icon">📱</span>
        Tạo mã QR thanh toán
      </button>

      <div class="payment-note">
        <p>✓ Quét mã QR bằng ứng dụng VNPay hoặc Banking App</p>
        <p>✓ Mã QR có hiệu lực trong 15 phút</p>
        <p>✓ Thanh toán an toàn và bảo mật</p>
      </div>
    </div>

    <!-- QR Code State (Đang chờ thanh toán) -->
    <div v-else-if="status === 'waiting_payment' && paymentData" class="vnpay-qr-container">
      <h3>Quét mã QR để thanh toán</h3>

      <div class="qr-wrapper">
        <qrcode-vue 
          :value="paymentData.payment_url" 
          :size="280"
          level="H"
          class="qr-code"
        />
      </div>

      <div class="payment-details">
        <div class="detail-row">
          <span class="label">Số tiền:</span>
          <span class="value amount">{{ formatCurrency(paymentData.amount) }}</span>
        </div>
        <div class="detail-row">
          <span class="label">Mã giao dịch:</span>
          <span class="value">{{ paymentData.txn_ref }}</span>
        </div>
      </div>

      <div :class="['timer-display', { 'warning': isExpiringSoon }]">
        <span class="timer-icon">⏱️</span>
        <span class="timer-text">
          {{ isExpiringSoon ? 'Sắp hết hạn: ' : 'Thời gian còn lại: ' }}
        </span>
        <span class="timer-value">{{ formatTime(timeLeft) }}</span>
      </div>

      <div class="instructions">
        <p class="instruction-title">Hướng dẫn thanh toán:</p>
        <ol>
          <li>Mở ứng dụng <strong>VNPay</strong> hoặc <strong>Banking App</strong> của bạn</li>
          <li>Chọn chức năng <strong>Quét mã QR</strong></li>
          <li>Quét mã QR code bên trên</li>
          <li>Xác nhận thông tin và hoàn tất thanh toán</li>
        </ol>
      </div>

      <div class="loading-indicator">
        <div class="spinner-small"></div>
        <span>Đang chờ thanh toán...</span>
      </div>

      <button class="btn-cancel" @click="handleCancel">
        Hủy thanh toán
      </button>
    </div>

    <!-- Success State -->
    <div v-else-if="status === 'paid'" class="vnpay-success">
      <div class="success-icon">✓</div>
      <h3>Thanh toán thành công!</h3>
      <p>Đơn đặt sân của bạn đã được thanh toán.</p>
      <div class="success-amount">
        {{ formatCurrency(amount) }}
      </div>
      <p class="success-note">
        Bạn sẽ được chuyển về trang chi tiết đặt sân...
      </p>
    </div>

    <!-- Failed State -->
    <div v-else-if="status === 'failed'" class="vnpay-failed">
      <div class="failed-icon">✕</div>
      <h3>Thanh toán thất bại</h3>
      <p>Giao dịch không thành công. Vui lòng thử lại.</p>
      <button class="btn-retry" @click="retryPayment">
        Thử lại
      </button>
      <button class="btn-cancel" @click="handleCancel">
        Hủy
      </button>
    </div>

    <!-- Expired State -->
    <div v-else-if="status === 'expired'" class="vnpay-expired">
      <div class="expired-icon">⏰</div>
      <h3>Mã QR đã hết hạn</h3>
      <p>Mã thanh toán đã hết hiệu lực sau 15 phút.</p>
      <button class="btn-retry" @click="retryPayment">
        Tạo mã mới
      </button>
      <button class="btn-cancel" @click="handleCancel">
        Hủy
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import QrcodeVue from 'qrcode.vue3';
import axios from 'axios';
import { API_ENDPOINTS, getAuthHeaders } from '../config/api';

// Props
const props = defineProps({
  bookingId: {
    type: Number,
    required: true
  },
  amount: {
    type: Number,
    required: true
  },
  bookingInfo: {
    type: Object,
    default: () => ({})
  }
});

// Emits
const emit = defineEmits(['success', 'error', 'cancel']);

// State
const paymentData = ref(null);
const loading = ref(false);
const status = ref('idle'); // idle, waiting_payment, paid, failed, expired
const timeLeft = ref(900); // 15 phút
const error = ref('');

// Intervals
let statusInterval = null;
let timerInterval = null;

// Computed
const isExpiringSoon = computed(() => timeLeft.value <= 120);

// Format currency
const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(value);
};

// Format time
const formatTime = (seconds) => {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins}:${secs.toString().padStart(2, '0')}`;
};

// Create payment
const createPayment = async () => {
  loading.value = true;
  error.value = '';

  try {
    const response = await axios.post(
      API_ENDPOINTS.vnpay.createPayment(props.bookingId),
      {},
      { headers: getAuthHeaders() }
    );

    if (response.data.success) {
      paymentData.value = response.data.data;
      status.value = 'waiting_payment';
      startStatusPolling();
      startTimer();
    }
  } catch (err) {
    const errorMessage = err.response?.data?.message || 'Không thể tạo mã thanh toán. Vui lòng thử lại.';
    error.value = errorMessage;
    emit('error', errorMessage);
  } finally {
    loading.value = false;
  }
};

// Check payment status
const checkPaymentStatus = async () => {
  try {
    const response = await axios.get(
      API_ENDPOINTS.vnpay.checkStatus(props.bookingId),
      { headers: getAuthHeaders() }
    );

    if (response.data.success) {
      const { payment_status } = response.data.data;

      if (payment_status === 'paid') {
        status.value = 'paid';
        stopStatusPolling();
        stopTimer();
        setTimeout(() => emit('success', response.data.data), 1500);
      } else if (payment_status === 'failed') {
        status.value = 'failed';
        stopStatusPolling();
        stopTimer();
        emit('error', 'Thanh toán thất bại');
      }
    }
  } catch (err) {
    console.error('Check status error:', err);
  }
};

// Start polling
const startStatusPolling = () => {
  checkPaymentStatus();
  statusInterval = setInterval(checkPaymentStatus, 5000);
};

// Stop polling
const stopStatusPolling = () => {
  if (statusInterval) {
    clearInterval(statusInterval);
    statusInterval = null;
  }
};

// Start timer
const startTimer = () => {
  timerInterval = setInterval(() => {
    timeLeft.value--;
    if (timeLeft.value <= 0) {
      status.value = 'expired';
      stopTimer();
      stopStatusPolling();
    }
  }, 1000);
};

// Stop timer
const stopTimer = () => {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
};

// Retry payment
const retryPayment = () => {
  status.value = 'idle';
  paymentData.value = null;
  timeLeft.value = 900;
  createPayment();
};

// Handle cancel
const handleCancel = () => {
  stopStatusPolling();
  stopTimer();
  status.value = 'idle';
  paymentData.value = null;
  timeLeft.value = 900;
  emit('cancel');
};

// Cleanup
onUnmounted(() => {
  stopStatusPolling();
  stopTimer();
});
</script>

<style scoped>
/* Style trong phần CSS bên dưới */
</style>
```

---

## 🎨 Styling CSS

### File: `src/components/VNPayPayment/VNPayPayment.css` (React) hoặc trong `<style scoped>` (Vue)

```css
/* Container chính */
.vnpay-payment {
  max-width: 500px;
  margin: 0 auto;
  padding: 24px;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

/* Loading State */
.vnpay-loading {
  text-align: center;
  padding: 40px 20px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Start State */
.vnpay-start {
  text-align: center;
}

.payment-info {
  margin-bottom: 24px;
}

.payment-info h3 {
  font-size: 24px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 16px;
}

.amount-display {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  margin-bottom: 16px;
}

.amount-display .label {
  font-size: 14px;
  color: #666;
}

.amount-display .amount {
  font-size: 28px;
  font-weight: 700;
  color: #007bff;
}

.booking-info {
  text-align: left;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  margin-top: 16px;
}

.booking-info p {
  margin: 8px 0;
  font-size: 14px;
  color: #333;
}

/* Error */
.vnpay-error {
  padding: 12px;
  background: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 6px;
  margin-bottom: 16px;
}

.vnpay-error p {
  margin: 0;
  color: #856404;
  font-size: 14px;
}

/* Buttons */
.btn-create-payment,
.btn-retry,
.btn-cancel {
  width: 100%;
  padding: 14px 24px;
  font-size: 16px;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-create-payment {
  background: #007bff;
  color: white;
  margin-bottom: 16px;
}

.btn-create-payment:hover {
  background: #0056b3;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.btn-create-payment:disabled {
  background: #ccc;
  cursor: not-allowed;
  transform: none;
}

.btn-retry {
  background: #28a745;
  color: white;
  margin-bottom: 12px;
}

.btn-retry:hover {
  background: #218838;
}

.btn-cancel {
  background: #6c757d;
  color: white;
}

.btn-cancel:hover {
  background: #5a6268;
}

.payment-note {
  margin-top: 24px;
  padding: 16px;
  background: #e7f3ff;
  border-left: 4px solid #007bff;
  border-radius: 4px;
  text-align: left;
}

.payment-note p {
  margin: 8px 0;
  font-size: 14px;
  color: #004085;
}

/* QR Container */
.vnpay-qr-container {
  text-align: center;
}

.vnpay-qr-container h3 {
  font-size: 22px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 24px;
}

.qr-wrapper {
  display: flex;
  justify-content: center;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 12px;
  margin-bottom: 20px;
}

.qr-code {
  border: 3px solid #007bff;
  border-radius: 8px;
  padding: 12px;
  background: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Payment Details */
.payment-details {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.detail-row:not(:last-child) {
  border-bottom: 1px solid #e9ecef;
}

.detail-row .label {
  font-size: 14px;
  color: #666;
}

.detail-row .value {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}

.detail-row .value.amount {
  font-size: 18px;
  font-weight: 700;
  color: #007bff;
}

/* Timer */
.timer-display {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  background: #e7f3ff;
  border-radius: 8px;
  margin-bottom: 16px;
}

.timer-display.warning {
  background: #fff3cd;
  border: 1px solid #ffc107;
}

.timer-icon {
  font-size: 20px;
}

.timer-text {
  font-size: 14px;
  color: #666;
}

.timer-value {
  font-size: 18px;
  font-weight: 700;
  color: #007bff;
}

.timer-display.warning .timer-value {
  color: #dc3545;
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

/* Instructions */
.instructions {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
  text-align: left;
}

.instruction-title {
  font-weight: 600;
  color: #333;
  margin-bottom: 12px;
}

.instructions ol {
  margin: 0;
  padding-left: 20px;
}

.instructions li {
  margin: 8px 0;
  font-size: 14px;
  color: #555;
  line-height: 1.5;
}

/* Loading Indicator */
.loading-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  margin-bottom: 16px;
  color: #666;
  font-size: 14px;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

/* Success State */
.vnpay-success {
  text-align: center;
  padding: 40px 20px;
}

.success-icon {
  width: 80px;
  height: 80px;
  line-height: 80px;
  font-size: 48px;
  color: white;
  background: #28a745;
  border-radius: 50%;
  margin: 0 auto 24px;
  animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.vnpay-success h3 {
  font-size: 24px;
  font-weight: 600;
  color: #28a745;
  margin-bottom: 12px;
}

.vnpay-success p {
  color: #666;
  margin-bottom: 16px;
}

.success-amount {
  font-size: 32px;
  font-weight: 700;
  color: #28a745;
  margin: 20px 0;
}

.success-note {
  font-size: 14px;
  color: #999;
  font-style: italic;
}

/* Failed State */
.vnpay-failed {
  text-align: center;
  padding: 40px 20px;
}

.failed-icon {
  width: 80px;
  height: 80px;
  line-height: 80px;
  font-size: 48px;
  color: white;
  background: #dc3545;
  border-radius: 50%;
  margin: 0 auto 24px;
}

.vnpay-failed h3 {
  font-size: 24px;
  font-weight: 600;
  color: #dc3545;
  margin-bottom: 12px;
}

.vnpay-failed p {
  color: #666;
  margin-bottom: 24px;
}

/* Expired State */
.vnpay-expired {
  text-align: center;
  padding: 40px 20px;
}

.expired-icon {
  width: 80px;
  height: 80px;
  line-height: 80px;
  font-size: 48px;
  color: white;
  background: #ffc107;
  border-radius: 50%;
  margin: 0 auto 24px;
}

.vnpay-expired h3 {
  font-size: 24px;
  font-weight: 600;
  color: #856404;
  margin-bottom: 12px;
}

.vnpay-expired p {
  color: #666;
  margin-bottom: 24px;
}

/* Responsive */
@media (max-width: 576px) {
  .vnpay-payment {
    padding: 16px;
  }

  .qr-wrapper {
    padding: 12px;
  }

  .qr-code {
    width: 220px !important;
    height: 220px !important;
  }

  .amount-display .amount {
    font-size: 24px;
  }

  .success-amount {
    font-size: 28px;
  }
}
```

---

## 🔗 Integration Examples

### 1. Tích hợp vào trang Booking Detail

**React:**
```jsx
// pages/BookingDetail.jsx
import React, { useState } from 'react';
import VNPayPayment from '../components/VNPayPayment/VNPayPayment';
import { useNavigate } from 'react-router-dom';

const BookingDetail = ({ booking }) => {
  const navigate = useNavigate();
  const [showPayment, setShowPayment] = useState(false);

  const handlePaymentSuccess = (paymentData) => {
    console.log('Payment success:', paymentData);
    // Refresh booking data
    // Show success notification
    setShowPayment(false);
    navigate(`/bookings/${booking.id}`);
  };

  const handlePaymentError = (error) => {
    console.error('Payment error:', error);
    alert(error);
  };

  return (
    <div className="booking-detail">
      <h1>Chi tiết đặt sân</h1>
      
      <div className="booking-info">
        <p>Mã booking: #{booking.id}</p>
        <p>Sân: {booking.field.name}</p>
        <p>Ngày: {booking.day_book}</p>
        <p>Giờ: {booking.start_time} - {booking.end_time}</p>
        <p>Tổng tiền: {booking.total_price.toLocaleString()} VNĐ</p>
        <p>Trạng thái: {booking.status}</p>
        <p>Thanh toán: {booking.payment_status}</p>
      </div>

      {booking.payment_method === 'vnpay' && 
       booking.payment_status === 'pending' &&
       booking.status === 'confirmed' && (
        <div className="payment-section">
          {!showPayment ? (
            <button onClick={() => setShowPayment(true)}>
              Thanh toán VNPay
            </button>
          ) : (
            <VNPayPayment
              bookingId={booking.id}
              amount={booking.total_price}
              bookingInfo={{
                fieldName: booking.field.name,
                date: booking.day_book,
                time: `${booking.start_time} - ${booking.end_time}`
              }}
              onSuccess={handlePaymentSuccess}
              onError={handlePaymentError}
              onCancel={() => setShowPayment(false)}
            />
          )}
        </div>
      )}
    </div>
  );
};

export default BookingDetail;
```

**Vue:**
```vue
<!-- pages/BookingDetail.vue -->
<template>
  <div class="booking-detail">
    <h1>Chi tiết đặt sân</h1>

    <div class="booking-info">
      <p>Mã booking: #{{ booking.id }}</p>
      <p>Sân: {{ booking.field.name }}</p>
      <p>Ngày: {{ booking.day_book }}</p>
      <p>Giờ: {{ booking.start_time }} - {{ booking.end_time }}</p>
      <p>Tổng tiền: {{ booking.total_price.toLocaleString() }} VNĐ</p>
      <p>Trạng thái: {{ booking.status }}</p>
      <p>Thanh toán: {{ booking.payment_status }}</p>
    </div>

    <div 
      v-if="booking.payment_method === 'vnpay' && 
            booking.payment_status === 'pending' &&
            booking.status === 'confirmed'"
      class="payment-section"
    >
      <button v-if="!showPayment" @click="showPayment = true">
        Thanh toán VNPay
      </button>

      <VNPayPayment
        v-else
        :booking-id="booking.id"
        :amount="booking.total_price"
        :booking-info="{
          fieldName: booking.field.name,
          date: booking.day_book,
          time: `${booking.start_time} - ${booking.end_time}`
        }"
        @success="handlePaymentSuccess"
        @error="handlePaymentError"
        @cancel="showPayment = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import VNPayPayment from '../components/VNPayPayment.vue';

const props = defineProps({
  booking: Object
});

const router = useRouter();
const showPayment = ref(false);

const handlePaymentSuccess = (paymentData) => {
  console.log('Payment success:', paymentData);
  showPayment.value = false;
  router.push(`/bookings/${props.booking.id}`);
};

const handlePaymentError = (error) => {
  console.error('Payment error:', error);
  alert(error);
};
</script>
```

### 2. Tích hợp vào trang Checkout

```jsx
// pages/Checkout.jsx
const Checkout = () => {
  const [paymentMethod, setPaymentMethod] = useState('cash');
  const [bookingId, setBookingId] = useState(null);

  const handleSubmitBooking = async () => {
    // Tạo booking với payment_method
    const response = await createBooking({
      field_id: selectedField.id,
      day_book: selectedDate,
      start_time: startTime,
      end_time: endTime,
      payment_method: paymentMethod
    });

    if (response.success) {
      setBookingId(response.data.id);
      
      // Nếu chọn VNPay, hiển thị component thanh toán
      if (paymentMethod === 'vnpay') {
        setShowPayment(true);
      }
    }
  };

  return (
    <div className="checkout">
      <h1>Đặt sân</h1>

      {/* Form đặt sân */}
      <div className="payment-methods">
        <label>
          <input 
            type="radio" 
            value="cash" 
            checked={paymentMethod === 'cash'}
            onChange={(e) => setPaymentMethod(e.target.value)}
          />
          Tiền mặt
        </label>
        <label>
          <input 
            type="radio" 
            value="transfer" 
            checked={paymentMethod === 'transfer'}
            onChange={(e) => setPaymentMethod(e.target.value)}
          />
          Chuyển khoản
        </label>
        <label>
          <input 
            type="radio" 
            value="vnpay" 
            checked={paymentMethod === 'vnpay'}
            onChange={(e) => setPaymentMethod(e.target.value)}
          />
          VNPay (Quét QR)
        </label>
      </div>

      <button onClick={handleSubmitBooking}>
        Xác nhận đặt sân
      </button>

      {/* Hiển thị VNPay payment nếu chọn vnpay */}
      {showPayment && paymentMethod === 'vnpay' && bookingId && (
        <VNPayPayment
          bookingId={bookingId}
          amount={totalPrice}
          onSuccess={handlePaymentSuccess}
          onError={handlePaymentError}
        />
      )}
    </div>
  );
};
```

---

## 🧪 Testing

### 1. Test với Sandbox VNPay

**Thông tin test (Sandbox):**
```
Thẻ test:
- Số thẻ: 9704198526191432198
- Tên: NGUYEN VAN A
- Ngày hết hạn: 07/15
- Mật khẩu OTP: 123456
```

### 2. Test Scenarios

#### ✅ Scenario 1: Thanh toán thành công
```
1. Tạo booking với payment_method = 'vnpay'
2. Owner confirm booking
3. Click "Thanh toán VNPay"
4. Quét QR code
5. Nhập thẻ test và xác nhận
6. Kiểm tra payment_status = 'paid'
```

#### ❌ Scenario 2: Thanh toán thất bại
```
1. Tạo QR
2. Quét và nhập thẻ không hợp lệ
3. Kiểm tra hiển thị lỗi
4. Test nút "Thử lại"
```

#### ⏰ Scenario 3: QR hết hạn
```
1. Tạo QR
2. Đợi 15 phút (hoặc set timer ngắn hơn để test)
3. Kiểm tra hiển thị "QR đã hết hạn"
4. Test nút "Tạo mã mới"
```

#### 🔄 Scenario 4: Polling
```
1. Tạo QR
2. Mở DevTools → Network
3. Kiểm tra request GET /check-status mỗi 5s
4. Thanh toán bằng app khác
5. Kiểm tra frontend tự động cập nhật
```

### 3. Debug Tips

**Bật console logs:**
```javascript
// Trong component, thêm logs
console.log('Payment data:', paymentData);
console.log('Status:', status);
console.log('Time left:', timeLeft);
```

**Kiểm tra network requests:**
```
1. Mở DevTools → Network tab
2. Filter: XHR/Fetch
3. Xem các request:
   - POST /vnpay/create-payment
   - GET /vnpay/check-status (polling)
```

---

## 🐛 Troubleshooting

### 1. QR Code không hiển thị

**Nguyên nhân:**
- Thư viện chưa cài đúng
- payment_url null hoặc undefined

**Giải pháp:**
```bash
# Cài lại thư viện
npm install qrcode.react --save

# Kiểm tra payment_url
console.log('Payment URL:', paymentData?.payment_url);
```

### 2. Polling không hoạt động

**Nguyên nhân:**
- Interval không được cleanup đúng
- Token hết hạn

**Giải pháp:**
```javascript
// Đảm bảo cleanup intervals
useEffect(() => {
  return () => {
    if (intervalRef.current) {
      clearInterval(intervalRef.current);
    }
  };
}, []);

// Kiểm tra token
console.log('Token:', localStorage.getItem('token'));
```

### 3. CORS Error

**Nguyên nhân:**
- Backend chưa cấu hình CORS

**Giải pháp:**
Backend Laravel đã có CORS middleware, đảm bảo:
```php
// config/cors.php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:3000', 'http://localhost:8080'],
```

### 4. Payment không cập nhật sau khi thanh toán

**Nguyên nhân:**
- Callback URL sai
- IPN không gọi được backend

**Giải pháp:**
```
1. Kiểm tra .env backend:
   VNPAY_RETURN_URL=http://localhost:8000/api/vnpay/callback

2. Kiểm tra logs backend:
   tail -f storage/logs/laravel.log

3. Test IPN endpoint:
   POST http://localhost:8000/api/vnpay/ipn
```

### 5. Timer không chạy

**Nguyên nhân:**
- setInterval không cleanup
- Component re-render liên tục

**Giải pháp:**
```javascript
// Dùng useRef để lưu interval ID
const timerRef = useRef(null);

const startTimer = () => {
  timerRef.current = setInterval(() => {
    setTimeLeft(prev => prev - 1);
  }, 1000);
};

// Cleanup
useEffect(() => {
  return () => {
    if (timerRef.current) {
      clearInterval(timerRef.current);
    }
  };
}, []);
```

---

## ✅ Checklist hoàn thành

### Backend:
- [x] Migration đã chạy
- [x] VNPay credentials đã cấu hình trong .env
- [x] API endpoints hoạt động
- [x] Test với Postman

### Frontend:
- [ ] Cài đặt thư viện QR Code
- [ ] Cấu hình API URL trong .env
- [ ] Tạo file config/api.js
- [ ] Copy component VNPayPayment (React/Vue)
- [ ] Copy file CSS
- [ ] Tích hợp vào trang Booking Detail
- [ ] Test tạo QR code
- [ ] Test polling
- [ ] Test countdown timer
- [ ] Test thanh toán thành công
- [ ] Test thanh toán thất bại
- [ ] Test QR hết hạn
- [ ] Test responsive mobile

### Production:
- [ ] Đổi API URL sang production
- [ ] Đổi VNPay credentials sang production
- [ ] Test với tiền thật (số tiền nhỏ)
- [ ] Setup monitoring
- [ ] Setup error tracking (Sentry)

---

## 📚 Tài liệu tham khảo

- **Backend Guide:** `VNPAY_INTEGRATION_GUIDE.md`
- **Quick Start:** `VNPAY_QUICK_START.md`
- **VNPay Docs:** https://sandbox.vnpayment.vn/apis/
- **React QR Code:** https://github.com/zpao/qrcode.react
- **Vue QR Code:** https://github.com/scopewu/qrcode.vue

---

## 🎯 Next Steps

1. **Cài đặt dependencies:**
   ```bash
   npm install qrcode.react axios  # React
   npm install qrcode.vue3 axios   # Vue
   ```

2. **Cấu hình API:**
   - Tạo file `.env` với `REACT_APP_API_URL` hoặc `VITE_API_URL`
   - Tạo file `src/config/api.js`

3. **Copy component:**
   - Copy code React hoặc Vue từ guide này
   - Copy file CSS

4. **Tích hợp:**
   - Import component vào trang cần dùng
   - Pass props đúng: `bookingId`, `amount`
   - Handle events: `onSuccess`, `onError`, `onCancel`

5. **Test:**
   - Test trên sandbox VNPay
   - Test các scenarios: success, fail, expire
   - Test responsive mobile

---

**🎉 Chúc bạn tích hợp thành công!**

Nếu có vấn đề, kiểm tra lại:
1. Backend logs: `storage/logs/laravel.log`
2. Browser console
3. Network tab trong DevTools
4. File `.env` frontend và backend
