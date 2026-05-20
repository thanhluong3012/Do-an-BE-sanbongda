# 📱 HƯỚNG DẪN FRONTEND TÍCH HỢP VIETQR

> **Hệ thống đã chuyển từ VNPay sang VietQR**  
> Frontend cần cập nhật để tương thích với API và luồng thanh toán mới

---

## 📋 Tổng quan thay đổi

### Sự khác biệt chính:

| Feature | VNPay (Cũ) ❌ | VietQR (Mới) ✅ |
|---------|---------------|-----------------|
| **Cách hoạt động** | Redirect sang VNPay → User thanh toán → Callback về | Hiển thị QR trên trang → User CK → Upload bill → Owner xác nhận |
| **Có callback tự động** | ✅ Có | ❌ Không |
| **Cần upload bill** | ❌ Không | ✅ Bắt buộc |
| **Xác nhận thanh toán** | Tự động | Thủ công (owner) |
| **Expire time** | 15 phút | Không giới hạn |
| **Chi phí** | 1-2% phí | Miễn phí |

---

## 🔄 1. API ENDPOINTS CẦN THAY ĐỔI

### ❌ Xóa endpoints VNPay cũ:

```javascript
// ❌ XÓA TẤT CẢ endpoints VNPay
const OLD_ENDPOINTS = {
  createPayment: '/api/vnpay/create-payment/{bookingId}',
  checkStatus: '/api/vnpay/check-status/{bookingId}',
  callback: '/api/vnpay/callback',
  ipn: '/api/vnpay/ipn',
  queryTransaction: '/api/vnpay/query-transaction'
};
```

### ✅ Thêm endpoints VietQR mới:

```javascript
// ✅ THÊM endpoints VietQR
const API_ENDPOINTS = {
  // VietQR endpoints
  createQR: '/api/vietqr/create-payment/{bookingId}',      // POST, Auth required
  checkStatus: '/api/vietqr/check-status/{bookingId}',    // GET, Auth required
  getBanks: '/api/vietqr/banks',                          // GET, Public
  
  // Booking endpoints (không đổi)
  uploadBill: '/api/books/{id}/upload-payment-proof',     // POST, Auth required
  confirmPayment: '/api/books/{id}/confirm-payment',      // PUT, Auth required (Owner)
};
```

---

## 📦 2. RESPONSE STRUCTURE MỚI

### A. Tạo VietQR Code

**Request:**
```javascript
POST /api/vietqr/create-payment/123
Headers: {
  'Authorization': 'Bearer {token}',
  'Content-Type': 'application/json'
}
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

### B. Upload Bill

**Request:**
```javascript
POST /api/books/123/upload-payment-proof
Headers: {
  'Authorization': 'Bearer {token}',
  'Content-Type': 'application/json'
}
Body: {
  "payment_proof_url": "https://your-storage.com/bill123.jpg"
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
    "payment_proof": "https://your-storage.com/bill123.jpg"
  }
}
```

### C. Kiểm tra trạng thái

**Request:**
```javascript
GET /api/vietqr/check-status/123
Headers: {
  'Authorization': 'Bearer {token}'
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "booking_id": 123,
    "payment_status": "pending",
    "payment_method": "vietqr",
    "payment_proof": "https://storage.com/bill.jpg",
    "payment_confirmed_at": null,
    "payment_note": null,
    "amount": 500000,
    "status_message": "Đã upload bill, đang chờ xác nhận từ chủ sân"
  }
}
```

**Payment Status Values:**
- `pending` - Chờ thanh toán hoặc chờ xác nhận
- `paid` - Đã thanh toán thành công
- `failed` - Thanh toán thất bại hoặc bị từ chối

---

## 🎨 3. UI COMPONENTS MỚI

### A. VietQR Payment Component (React Example)

```jsx
// components/VietQRPayment.jsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

const VietQRPayment = () => {
  const { bookingId } = useParams();
  const navigate = useNavigate();
  const [qrData, setQrData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    createQRCode();
  }, [bookingId]);

  const createQRCode = async () => {
    try {
      setLoading(true);
      
      const response = await fetch(
        `http://localhost:8000/api/vietqr/create-payment/${bookingId}`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        }
      );

      const data = await response.json();

      if (data.success) {
        setQrData(data.data);
      } else {
        setError(data.message);
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert('Đã copy!');
  };

  if (loading) {
    return (
      <div className="loading">
        <p>Đang tạo mã QR thanh toán...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="error">
        <p>Lỗi: {error}</p>
        <button onClick={() => navigate(-1)}>Quay lại</button>
      </div>
    );
  }

  return (
    <div className="vietqr-payment">
      <h1>Thanh toán VietQR</h1>

      {/* QR Code */}
      <div className="qr-container">
        <img 
          src={qrData.qr_code_url} 
          alt="VietQR Code" 
          className="qr-image"
          style={{ width: '300px', height: 'auto' }}
        />
      </div>

      {/* Thông tin thanh toán */}
      <div className="payment-info">
        <div className="info-row">
          <span className="label">Số tiền:</span>
          <span className="value">{qrData.amount_formatted}</span>
        </div>

        <div className="info-row">
          <span className="label">Nội dung CK:</span>
          <div className="value-with-copy">
            <span className="value">{qrData.transfer_content}</span>
            <button 
              className="btn-copy"
              onClick={() => copyToClipboard(qrData.transfer_content)}
            >
              📋 Copy
            </button>
          </div>
        </div>

        <div className="info-row">
          <span className="label">Ngân hàng:</span>
          <span className="value">{qrData.account_info.bank_id}</span>
        </div>

        <div className="info-row">
          <span className="label">Số tài khoản:</span>
          <div className="value-with-copy">
            <span className="value">{qrData.account_info.account_no}</span>
            <button 
              className="btn-copy"
              onClick={() => copyToClipboard(qrData.account_info.account_no)}
            >
              📋 Copy
            </button>
          </div>
        </div>

        <div className="info-row">
          <span className="label">Tên tài khoản:</span>
          <span className="value">{qrData.account_info.account_name}</span>
        </div>
      </div>

      {/* Hướng dẫn */}
      <div className="instructions">
        <h3>📱 Hướng dẫn thanh toán</h3>
        <ol>
          <li>Mở app ngân hàng của bạn</li>
          <li>Chọn chức năng quét QR Code</li>
          <li>Quét mã QR phía trên</li>
          <li>Kiểm tra kỹ thông tin (số tiền, nội dung chuyển khoản)</li>
          <li>Xác nhận chuyển khoản</li>
          <li>Chụp ảnh bill (biên lai) sau khi chuyển thành công</li>
          <li>Nhấn nút "Đã chuyển khoản" bên dưới để upload bill</li>
        </ol>
        <p className="note">
          ⚠️ <strong>Lưu ý:</strong> Bạn PHẢI upload bill để chủ sân xác nhận thanh toán. 
          Không upload bill = không thể xác nhận thanh toán.
        </p>
      </div>

      {/* Actions */}
      <div className="actions">
        <button 
          className="btn-primary"
          onClick={() => navigate(`/upload-bill/${bookingId}`)}
        >
          ✅ Đã chuyển khoản, Upload Bill
        </button>

        <button 
          className="btn-secondary"
          onClick={() => navigate('/my-bookings')}
        >
          ⬅️ Quay lại
        </button>
      </div>
    </div>
  );
};

export default VietQRPayment;
```

### B. Upload Bill Component (MỚI - BẮT BUỘC)

```jsx
// components/UploadBill.jsx
import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

const UploadBill = () => {
  const { bookingId } = useParams();
  const navigate = useNavigate();
  const [file, setFile] = useState(null);
  const [preview, setPreview] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState(null);

  const handleFileChange = (e) => {
    const selectedFile = e.target.files[0];
    
    if (!selectedFile) return;

    // Validate file
    if (!selectedFile.type.startsWith('image/')) {
      setError('Vui lòng chọn file ảnh (jpg, png, ...)');
      return;
    }

    if (selectedFile.size > 5 * 1024 * 1024) { // 5MB
      setError('File quá lớn. Vui lòng chọn file nhỏ hơn 5MB');
      return;
    }

    setFile(selectedFile);
    setError(null);

    // Create preview
    const reader = new FileReader();
    reader.onloadend = () => {
      setPreview(reader.result);
    };
    reader.readAsDataURL(selectedFile);
  };

  const uploadToStorage = async (file) => {
    // TODO: Replace with your actual storage service
    // Example: Supabase, Cloudinary, AWS S3, etc.
    
    const formData = new FormData();
    formData.append('file', file);

    // Example with Supabase
    const response = await fetch('YOUR_STORAGE_API_URL/upload', {
      method: 'POST',
      body: formData,
      headers: {
        // Add your storage auth headers here
      }
    });

    const data = await response.json();
    return data.url; // Return the public URL of uploaded file
  };

  const handleUpload = async () => {
    if (!file) {
      setError('Vui lòng chọn ảnh bill');
      return;
    }

    try {
      setUploading(true);
      setError(null);

      // 1. Upload file to storage service
      const billUrl = await uploadToStorage(file);

      // 2. Send URL to backend
      const response = await fetch(
        `http://localhost:8000/api/books/${bookingId}/upload-payment-proof`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            payment_proof_url: billUrl
          })
        }
      );

      const data = await response.json();

      if (data.success) {
        alert('✅ Upload thành công! Vui lòng chờ chủ sân xác nhận thanh toán.');
        navigate(`/payment-status/${bookingId}`);
      } else {
        setError(data.message || 'Upload thất bại');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="upload-bill">
      <h1>Upload Bill Chuyển Khoản</h1>

      <div className="upload-container">
        {/* File input */}
        <div className="file-input-wrapper">
          <input
            type="file"
            id="bill-file"
            accept="image/*"
            onChange={handleFileChange}
            disabled={uploading}
          />
          <label htmlFor="bill-file" className="file-input-label">
            📸 Chọn ảnh bill
          </label>
        </div>

        {/* Preview */}
        {preview && (
          <div className="preview">
            <h3>Preview:</h3>
            <img src={preview} alt="Bill preview" />
          </div>
        )}

        {/* Error message */}
        {error && (
          <div className="error-message">
            ⚠️ {error}
          </div>
        )}

        {/* Instructions */}
        <div className="instructions">
          <h3>💡 Lưu ý khi chụp bill:</h3>
          <ul>
            <li>✅ Đảm bảo ảnh rõ ràng, không bị mờ</li>
            <li>✅ Hiển thị đầy đủ thông tin: số tiền, nội dung, thời gian</li>
            <li>✅ File dưới 5MB</li>
            <li>✅ Định dạng: JPG, PNG, HEIC</li>
          </ul>
        </div>

        {/* Actions */}
        <div className="actions">
          <button
            className="btn-primary"
            onClick={handleUpload}
            disabled={!file || uploading}
          >
            {uploading ? '⏳ Đang upload...' : '📤 Upload Bill'}
          </button>

          <button
            className="btn-secondary"
            onClick={() => navigate(-1)}
            disabled={uploading}
          >
            ⬅️ Quay lại
          </button>
        </div>
      </div>
    </div>
  );
};

export default UploadBill;
```

### C. Payment Status Component

```jsx
// components/PaymentStatus.jsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';

const PaymentStatus = () => {
  const { bookingId } = useParams();
  const navigate = useNavigate();
  const [status, setStatus] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchStatus = async () => {
    try {
      const response = await fetch(
        `http://localhost:8000/api/vietqr/check-status/${bookingId}`,
        {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        }
      );

      const data = await response.json();

      if (data.success) {
        setStatus(data.data);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchStatus();

    // Auto refresh every 10 seconds
    const interval = setInterval(fetchStatus, 10000);

    return () => clearInterval(interval);
  }, [bookingId]);

  if (loading) {
    return <div className="loading">Đang kiểm tra trạng thái...</div>;
  }

  if (!status) {
    return <div className="error">Không thể tải thông tin</div>;
  }

  const getStatusIcon = (paymentStatus) => {
    switch (paymentStatus) {
      case 'paid': return '✅';
      case 'pending': return '⏳';
      case 'failed': return '❌';
      default: return '❓';
    }
  };

  const getStatusClass = (paymentStatus) => {
    switch (paymentStatus) {
      case 'paid': return 'status-success';
      case 'pending': return 'status-pending';
      case 'failed': return 'status-failed';
      default: return 'status-unknown';
    }
  };

  return (
    <div className="payment-status">
      <h1>Trạng thái thanh toán</h1>

      <div className={`status-card ${getStatusClass(status.payment_status)}`}>
        <div className="status-icon">
          {getStatusIcon(status.payment_status)}
        </div>
        <h2>{status.status_message}</h2>
      </div>

      {/* Pending - Chưa upload bill */}
      {status.payment_status === 'pending' && !status.payment_proof && (
        <div className="status-detail">
          <p>⚠️ Bạn chưa upload bill chuyển khoản</p>
          <button
            className="btn-primary"
            onClick={() => navigate(`/upload-bill/${bookingId}`)}
          >
            📤 Upload Bill Ngay
          </button>
        </div>
      )}

      {/* Pending - Đã upload bill */}
      {status.payment_status === 'pending' && status.payment_proof && (
        <div className="status-detail">
          <p>✅ Đã upload bill thành công</p>
          <div className="bill-preview">
            <h3>Bill của bạn:</h3>
            <img src={status.payment_proof} alt="Payment Bill" />
          </div>
          <p className="waiting-message">
            ⏳ Vui lòng chờ chủ sân xác nhận thanh toán. 
            Chúng tôi sẽ thông báo khi có kết quả.
          </p>
          <p className="refresh-note">
            🔄 Trang này tự động làm mới mỗi 10 giây
          </p>
        </div>
      )}

      {/* Paid */}
      {status.payment_status === 'paid' && (
        <div className="status-detail success">
          <p>✅ Thanh toán thành công!</p>
          <p>Xác nhận lúc: {new Date(status.payment_confirmed_at).toLocaleString('vi-VN')}</p>
          {status.payment_note && (
            <p className="note">Ghi chú: {status.payment_note}</p>
          )}
        </div>
      )}

      {/* Failed */}
      {status.payment_status === 'failed' && (
        <div className="status-detail failed">
          <p>❌ Thanh toán thất bại hoặc bị từ chối</p>
          {status.payment_note && (
            <p className="note">Lý do: {status.payment_note}</p>
          )}
          <button
            className="btn-primary"
            onClick={() => navigate(`/payment/vietqr/${bookingId}`)}
          >
            🔄 Thử lại
          </button>
        </div>
      )}

      <div className="actions">
        <button
          className="btn-secondary"
          onClick={() => navigate('/my-bookings')}
        >
          ⬅️ Về danh sách booking
        </button>
      </div>
    </div>
  );
};

export default PaymentStatus;
```

---

## 🗺️ 4. ROUTING

```jsx
// App.jsx hoặc routes.jsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import VietQRPayment from './components/VietQRPayment';
import UploadBill from './components/UploadBill';
import PaymentStatus from './components/PaymentStatus';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* ❌ XÓA routes VNPay cũ */}
        {/* <Route path="/payment/vnpay/callback" element={<VNPayCallback />} /> */}
        
        {/* ✅ THÊM routes VietQR mới */}
        <Route path="/payment/vietqr/:bookingId" element={<VietQRPayment />} />
        <Route path="/upload-bill/:bookingId" element={<UploadBill />} />
        <Route path="/payment-status/:bookingId" element={<PaymentStatus />} />
        
        {/* Other routes... */}
      </Routes>
    </BrowserRouter>
  );
}
```

---

## 🎨 5. CSS EXAMPLE

```css
/* VietQR Payment Styles */
.vietqr-payment {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

.qr-container {
  text-align: center;
  margin: 30px 0;
  padding: 20px;
  background: #f5f5f5;
  border-radius: 12px;
}

.qr-image {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
}

.payment-info {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  margin: 20px 0;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
  border-bottom: none;
}

.label {
  font-weight: 600;
  color: #666;
}

.value {
  font-weight: 700;
  color: #333;
}

.value-with-copy {
  display: flex;
  gap: 10px;
  align-items: center;
}

.btn-copy {
  padding: 6px 12px;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.btn-copy:hover {
  background: #45a049;
}

.instructions {
  background: #fff3cd;
  border: 1px solid #ffc107;
  border-radius: 8px;
  padding: 20px;
  margin: 20px 0;
}

.instructions h3 {
  margin-top: 0;
  color: #856404;
}

.instructions ol {
  padding-left: 20px;
}

.instructions li {
  margin: 8px 0;
  line-height: 1.6;
}

.note {
  background: #fff;
  padding: 15px;
  border-left: 4px solid #ff9800;
  margin-top: 15px;
}

.actions {
  display: flex;
  gap: 15px;
  margin-top: 30px;
}

.btn-primary {
  flex: 1;
  padding: 15px 30px;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.btn-primary:hover {
  background: #45a049;
}

.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.btn-secondary {
  flex: 1;
  padding: 15px 30px;
  background: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 16px;
  cursor: pointer;
}

.btn-secondary:hover {
  background: #e0e0e0;
}

/* Upload Bill Styles */
.upload-bill {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

.upload-container {
  background: white;
  border-radius: 12px;
  padding: 30px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.file-input-wrapper {
  text-align: center;
  margin: 30px 0;
}

.file-input-wrapper input[type="file"] {
  display: none;
}

.file-input-label {
  display: inline-block;
  padding: 15px 40px;
  background: #2196F3;
  color: white;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  transition: background 0.3s;
}

.file-input-label:hover {
  background: #1976D2;
}

.preview {
  margin: 30px 0;
  text-align: center;
}

.preview img {
  max-width: 100%;
  max-height: 400px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.error-message {
  background: #f44336;
  color: white;
  padding: 15px;
  border-radius: 8px;
  margin: 20px 0;
}

/* Payment Status Styles */
.payment-status {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
}

.status-card {
  text-align: center;
  padding: 40px;
  border-radius: 12px;
  margin: 30px 0;
}

.status-card .status-icon {
  font-size: 80px;
  margin-bottom: 20px;
}

.status-success {
  background: #e8f5e9;
  border: 2px solid #4CAF50;
}

.status-pending {
  background: #fff3e0;
  border: 2px solid #ff9800;
}

.status-failed {
  background: #ffebee;
  border: 2px solid #f44336;
}

.status-detail {
  background: white;
  border-radius: 8px;
  padding: 30px;
  margin: 20px 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.bill-preview {
  margin: 20px 0;
}

.bill-preview img {
  max-width: 100%;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.waiting-message {
  font-size: 16px;
  color: #666;
  text-align: center;
  margin: 20px 0;
}

.refresh-note {
  font-size: 14px;
  color: #999;
  text-align: center;
  font-style: italic;
}

.loading, .error {
  text-align: center;
  padding: 40px;
  font-size: 18px;
}
```

---

## 📱 6. LUỒNG UX MỚI

```
┌─────────────────────────────────────────────────────────────┐
│  1. User tạo booking                                        │
│     POST /api/books                                         │
│     → status: pending                                       │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Owner xác nhận booking                                  │
│     POST /api/books/{id}/confirm-booking                    │
│     → status: confirmed                                     │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  3. User chọn "Thanh toán VietQR"                           │
│     Navigate to: /payment/vietqr/{bookingId}                │
│     → POST /api/vietqr/create-payment/{bookingId}           │
│     → Hiển thị QR code + thông tin                          │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  4. User quét QR bằng app ngân hàng                         │
│     → Chuyển khoản                                          │
│     → Chụp ảnh bill (biên lai)                              │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  5. User click "Đã chuyển khoản"                            │
│     Navigate to: /upload-bill/{bookingId}                   │
│     → Upload ảnh bill lên storage                           │
│     → POST /api/books/{id}/upload-payment-proof             │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  6. Redirect to: /payment-status/{bookingId}                │
│     → GET /api/vietqr/check-status/{bookingId}              │
│     → Hiển thị: "Đang chờ xác nhận..."                      │
│     → Auto refresh mỗi 10s                                  │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  7. Owner xem bill và xác nhận                              │
│     PUT /api/books/{id}/confirm-payment                     │
│     → payment_status: paid                                  │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  8. User nhận thông báo "Thanh toán thành công"            │
│     → Page tự động cập nhật: ✅ Đã thanh toán               │
└─────────────────────────────────────────────────────────────┘
```

---

## ✅ 7. CHECKLIST

### Bắt buộc:
- [ ] Đổi tất cả API endpoints từ `/api/vnpay/*` → `/api/vietqr/*`
- [ ] Tạo component **VietQRPayment** (hiển thị QR)
- [ ] Tạo component **UploadBill** (upload bill - BẮT BUỘC)
- [ ] Tạo component **PaymentStatus** (hiển thị trạng thái)
- [ ] Cập nhật routing
- [ ] Xóa VNPay callback/redirect components
- [ ] Tích hợp storage service (Supabase/Cloudinary/S3) cho upload ảnh
- [ ] Test luồng end-to-end

### Optional:
- [ ] Thêm component chọn ngân hàng
- [ ] Thêm button copy thông tin
- [ ] Thêm auto-refresh status
- [ ] Thêm notification khi owner xác nhận
- [ ] Thêm countdown timer
- [ ] Thêm preview ảnh bill trước khi upload

---

## 🔧 8. STORAGE SERVICES ĐỀ XUẤT

### Option 1: Supabase Storage (Recommended)

```bash
npm install @supabase/supabase-js
```

```javascript
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  'YOUR_SUPABASE_URL',
  'YOUR_SUPABASE_ANON_KEY'
);

async function uploadToSupabase(file) {
  const fileName = `bill_${Date.now()}.${file.name.split('.').pop()}`;
  
  const { data, error } = await supabase.storage
    .from('bills')
    .upload(fileName, file);

  if (error) throw error;

  const { data: urlData } = supabase.storage
    .from('bills')
    .getPublicUrl(fileName);

  return urlData.publicUrl;
}
```

### Option 2: Cloudinary

```bash
npm install cloudinary
```

```javascript
async function uploadToCloudinary(file) {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('upload_preset', 'YOUR_PRESET');

  const response = await fetch(
    'https://api.cloudinary.com/v1_1/YOUR_CLOUD_NAME/image/upload',
    {
      method: 'POST',
      body: formData
    }
  );

  const data = await response.json();
  return data.secure_url;
}
```

---

## 🐛 9. TROUBLESHOOTING

### Lỗi thường gặp:

**1. "payment_proof_url is required"**
- Kiểm tra đã upload file lên storage chưa
- Kiểm tra URL trả về từ storage có hợp lệ không

**2. "Booking chưa được xác nhận"**
- Owner phải xác nhận booking trước khi user thanh toán

**3. QR code không hiển thị**
- Kiểm tra VIETQR_ACCOUNT_NO trong .env backend đã đúng chưa
- Kiểm tra response từ API có qr_code_url không

**4. Upload bill thất bại**
- Kiểm tra file size (< 5MB)
- Kiểm tra format (jpg, png, heic)
- Kiểm tra storage credentials

---

## 📞 10. LIÊN HỆ & HỖ TRỢ

Nếu gặp vấn đề khi tích hợp, tham khảo:

- **Backend API Docs:** `VIETQR_INTEGRATION_GUIDE.md`
- **Quick Start:** `VIETQR_QUICK_START.md`
- **Migration Summary:** `MIGRATION_SUMMARY.md`

---

**Chúc bạn tích hợp thành công! 🎉**
