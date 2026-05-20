# 🎨 HƯỚNG DẪN FRONTEND - MULTI-OWNER VIETQR

> **Cập nhật:** 19/05/2026  
> **Tính năng:** Hỗ trợ mỗi Owner có tài khoản ngân hàng riêng để nhận thanh toán trực tiếp

---

## 📋 MỤC LỤC

1. [Tổng quan thay đổi](#tổng-quan-thay-đổi)
2. [API mới & thay đổi](#api-mới--thay-đổi)
3. [Form Owner Profile - Thêm Bank Info](#form-owner-profile)
4. [Component Payment QR](#component-payment-qr)
5. [Validation Rules](#validation-rules)
6. [Code Examples](#code-examples)
7. [Testing Guide](#testing-guide)

---

## 🎯 TỔNG QUAN THAY ĐỔI

### **Trước đây:**
```
User thanh toán → Tiền về 1 TK Platform (.env)
                → Admin chia tiền cho Owner
```

### **Bây giờ:**
```
User thanh toán → Nếu Owner có bank info: Tiền về TK Owner
                → Nếu không: Tiền về TK Platform (fallback)
```

**Lợi ích:**
- ✅ Owner nhận tiền trực tiếp, tự chủ tài chính
- ✅ Không cần Admin chia tiền thủ công
- ✅ Minh bạch, tự động hóa
- ✅ Platform không phải quản lý dòng tiền

---

## 🔄 API MỚI & THAY ĐỔI

### **1. API Lấy danh sách ngân hàng** 🆕

```http
GET /api/vietqr/banks
Content-Type: application/json
```

**Response:**
```json
{
  "success": true,
  "message": "Lấy danh sách ngân hàng thành công",
  "data": [
    {
      "id": 17,
      "name": "Ngân hàng TMCP Công thương Việt Nam",
      "code": "ICB",
      "bin": "970415",
      "shortName": "VietinBank",
      "logo": "https://api.vietqr.io/img/ICB.png"
    },
    {
      "id": 43,
      "name": "Ngân hàng TMCP Quân đội",
      "code": "MB",
      "bin": "970422",
      "shortName": "MBBank",
      "logo": "https://api.vietqr.io/img/MB.png"
    }
    // ... 50+ banks
  ]
}
```

**Sử dụng:**
- Dropdown chọn ngân hàng trong form Owner Profile
- Hiển thị logo bank trong payment page

---

### **2. API Cập nhật Owner Profile** 🔄 CẬP NHẬT

```http
PUT /api/owner/profile
Authorization: Bearer {owner_token}
Content-Type: application/json
```

**Request Body (các field MỚI):**
```json
{
  "business_name": "Sân Bóng Đá ABC",
  "address": "123 Đường XYZ",
  "code_id": "012345678901",
  
  // ✨ MỚI - Thông tin ngân hàng
  "bank_id": "970422",              // Mã BIN (6 số)
  "account_no": "1234567890",       // Số TK (6-20 ký tự, chỉ số)
  "account_name": "NGUYEN VAN A"    // Tên (CHỮ HOA không dấu)
}
```

**Response thành công:**
```json
{
  "success": true,
  "message": "Cập nhật thông tin chủ sân thành công. Hệ thống sẽ tự động tạo VietQR cho các booking của bạn.",
  "data": {
    "id": 1,
    "user_id": 5,
    "business_name": "Sân Bóng Đá ABC",
    "bank_id": "970422",
    "account_no": "1234567890",
    "account_name": "NGUYEN VAN A",
    "created_at": "2026-05-19T10:00:00.000000Z",
    "updated_at": "2026-05-19T10:30:00.000000Z"
  }
}
```

**Validation Errors:**
```json
{
  "success": false,
  "message": "Validation failed",
  "data": {
    "bank_id": ["Mã ngân hàng phải có đúng 6 số"],
    "account_no": ["Số tài khoản chỉ được chứa số"],
    "account_name": ["Tên chủ tài khoản phải viết HOA không dấu (VD: NGUYEN VAN A)"]
  }
}
```

---

### **3. API Tạo QR Thanh toán** 🔄 CẬP NHẬT RESPONSE

```http
POST /api/vietqr/create-payment/{bookingId}
Authorization: Bearer {user_token}
```

**Response CŨ:**
```json
{
  "account_info": {
    "bank_id": "970422",
    "account_no": "0123456789",
    "account_name": "NGUYEN VAN A"
  }
}
```

**Response MỚI (thêm field):**
```json
{
  "success": true,
  "message": "Tạo mã QR thanh toán thành công",
  "data": {
    "booking_id": 123,
    "qr_code_url": "https://img.vietqr.io/image/970422-1234567890-compact.png?amount=500000&addInfo=BOOK-123",
    "amount": 500000,
    "amount_formatted": "500.000 VNĐ",
    "transfer_content": "BOOK-123",
    "account_info": {
      "bank_id": "970422",
      "account_no": "1234567890",
      "account_name": "NGUYEN VAN A",
      "is_owner_account": true  // ✨ MỚI - Flag quan trọng
    },
    "instruction": "Vui lòng quét mã QR hoặc chuyển khoản đúng số tiền và nội dung...",
    "next_step": "upload_bill"
  }
}
```

**Ý nghĩa của `is_owner_account`:**
- `true` → Tiền về tài khoản Owner (thông tin TK là của owner)
- `false` → Tiền về tài khoản Platform (owner chưa cập nhật bank info)

---

## 📝 FORM OWNER PROFILE

### **1. Component Form** (React Example)

**File:** `src/components/OwnerProfile/BankInfoForm.jsx`

```jsx
import React, { useState, useEffect } from 'react';
import { VietQRService } from '../../services/vietqr.service';
import { toast } from 'react-toastify';
import './BankInfoForm.css';

const BankInfoForm = ({ currentBankInfo, onSuccess }) => {
  const [banks, setBanks] = useState([]);
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    bank_id: currentBankInfo?.bank_id || '',
    account_no: currentBankInfo?.account_no || '',
    account_name: currentBankInfo?.account_name || ''
  });
  const [errors, setErrors] = useState({});

  // Load danh sách ngân hàng khi component mount
  useEffect(() => {
    loadBanks();
  }, []);

  const loadBanks = async () => {
    try {
      const banksData = await VietQRService.getBanks();
      setBanks(banksData);
    } catch (error) {
      console.error('Load banks error:', error);
      toast.error('Không thể tải danh sách ngân hàng');
    }
  };

  // Validation
  const validateForm = () => {
    const newErrors = {};
    
    // Bank ID: 6 số
    if (!formData.bank_id) {
      newErrors.bank_id = 'Vui lòng chọn ngân hàng';
    } else if (!/^[0-9]{6}$/.test(formData.bank_id)) {
      newErrors.bank_id = 'Mã ngân hàng phải có đúng 6 số';
    }
    
    // Account No: 6-20 số
    if (!formData.account_no) {
      newErrors.account_no = 'Vui lòng nhập số tài khoản';
    } else if (!/^[0-9]{6,20}$/.test(formData.account_no)) {
      newErrors.account_no = 'Số tài khoản từ 6-20 số';
    }
    
    // Account Name: CHỮ HOA
    if (!formData.account_name) {
      newErrors.account_name = 'Vui lòng nhập tên chủ tài khoản';
    } else if (!/^[A-Z\s]+$/.test(formData.account_name)) {
      newErrors.account_name = 'Tên phải viết HOA không dấu (VD: NGUYEN VAN A)';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Handle input change
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    // Clear error khi user sửa
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  // Auto convert sang CHỮ HOA
  const handleAccountNameChange = (e) => {
    const { value } = e.target;
    setFormData(prev => ({
      ...prev,
      account_name: value.toUpperCase()
    }));
    if (errors.account_name) {
      setErrors(prev => ({ ...prev, account_name: '' }));
    }
  };

  // Submit form
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      toast.error('Vui lòng kiểm tra lại thông tin');
      return;
    }
    
    setLoading(true);
    try {
      const token = localStorage.getItem('token');
      const response = await fetch(`${process.env.REACT_APP_API_URL}/api/owner/profile`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(formData)
      });
      
      const result = await response.json();
      
      if (result.success) {
        toast.success(result.message);
        if (onSuccess) onSuccess(result.data);
      } else {
        toast.error(result.message || 'Cập nhật thất bại');
        if (result.data) {
          setErrors(result.data);
        }
      }
    } catch (error) {
      console.error('Submit error:', error);
      toast.error('Có lỗi xảy ra. Vui lòng thử lại');
    } finally {
      setLoading(false);
    }
  };

  // Lấy thông tin bank đã chọn
  const selectedBank = banks.find(b => b.bin === formData.bank_id);

  return (
    <form onSubmit={handleSubmit} className="bank-info-form">
      <div className="form-section">
        <h3>💳 Thông tin tài khoản ngân hàng</h3>
        <p className="section-note">
          Cập nhật để nhận thanh toán trực tiếp từ khách hàng qua VietQR
        </p>
        
        {/* Chọn ngân hàng */}
        <div className="form-group">
          <label htmlFor="bank_id">
            Ngân hàng <span className="required">*</span>
          </label>
          <select
            id="bank_id"
            name="bank_id"
            value={formData.bank_id}
            onChange={handleChange}
            className={errors.bank_id ? 'error' : ''}
          >
            <option value="">-- Chọn ngân hàng --</option>
            {banks.map(bank => (
              <option key={bank.bin} value={bank.bin}>
                {bank.code} - {bank.shortName}
              </option>
            ))}
          </select>
          {errors.bank_id && (
            <span className="error-message">{errors.bank_id}</span>
          )}
          {selectedBank && (
            <div className="bank-preview">
              <img src={selectedBank.logo} alt={selectedBank.code} />
              <span>{selectedBank.name}</span>
            </div>
          )}
        </div>
        
        {/* Số tài khoản */}
        <div className="form-group">
          <label htmlFor="account_no">
            Số tài khoản <span className="required">*</span>
          </label>
          <input
            type="text"
            id="account_no"
            name="account_no"
            value={formData.account_no}
            onChange={handleChange}
            placeholder="0123456789"
            pattern="[0-9]{6,20}"
            maxLength="20"
            className={errors.account_no ? 'error' : ''}
          />
          {errors.account_no && (
            <span className="error-message">{errors.account_no}</span>
          )}
          <small className="hint">Chỉ nhập số, từ 6-20 ký tự</small>
        </div>
        
        {/* Tên chủ tài khoản */}
        <div className="form-group">
          <label htmlFor="account_name">
            Tên chủ tài khoản <span className="required">*</span>
          </label>
          <input
            type="text"
            id="account_name"
            name="account_name"
            value={formData.account_name}
            onChange={handleAccountNameChange}
            placeholder="NGUYEN VAN A"
            pattern="[A-Z\s]+"
            maxLength="255"
            className={errors.account_name ? 'error' : ''}
          />
          {errors.account_name && (
            <span className="error-message">{errors.account_name}</span>
          )}
          <small className="hint">
            ⚠️ Viết HOA không dấu, giống trên thẻ ATM/ngân hàng
          </small>
        </div>
        
        {/* Nút submit */}
        <div className="form-actions">
          <button type="submit" disabled={loading} className="btn btn-primary">
            {loading ? 'Đang lưu...' : '💾 Lưu thông tin ngân hàng'}
          </button>
        </div>
      </div>
    </form>
  );
};

export default BankInfoForm;
```

---

### **2. CSS Styling**

**File:** `src/components/OwnerProfile/BankInfoForm.css`

```css
.bank-info-form {
  max-width: 600px;
  margin: 20px auto;
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-section h3 {
  color: #333;
  margin-bottom: 8px;
}

.section-note {
  color: #666;
  font-size: 14px;
  margin-bottom: 20px;
  padding: 10px;
  background: #f0f8ff;
  border-left: 4px solid #4a90e2;
  border-radius: 4px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
  color: #333;
}

.form-group .required {
  color: #e74c3c;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #4a90e2;
  box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
}

.form-group input.error,
.form-group select.error {
  border-color: #e74c3c;
}

.error-message {
  display: block;
  color: #e74c3c;
  font-size: 13px;
  margin-top: 5px;
}

.hint {
  display: block;
  color: #666;
  font-size: 12px;
  margin-top: 5px;
  font-style: italic;
}

.bank-preview {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 10px;
  padding: 10px;
  background: #f9f9f9;
  border-radius: 4px;
}

.bank-preview img {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.bank-preview span {
  font-size: 14px;
  color: #333;
}

.form-actions {
  margin-top: 30px;
  text-align: center;
}

.btn {
  padding: 12px 30px;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background: #4a90e2;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #357abd;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
```

---

## 💳 COMPONENT PAYMENT QR

### **PaymentQRDisplay.jsx**

```jsx
import React, { useState, useEffect } from 'react';
import { VietQRService } from '../../services/vietqr.service';
import { toast } from 'react-toastify';
import './PaymentQRDisplay.css';

const PaymentQRDisplay = ({ bookingId, onPaymentCreated }) => {
  const [loading, setLoading] = useState(false);
  const [paymentData, setPaymentData] = useState(null);

  useEffect(() => {
    createPayment();
  }, [bookingId]);

  const createPayment = async () => {
    setLoading(true);
    try {
      const token = localStorage.getItem('token');
      const result = await VietQRService.createPayment(bookingId, token);
      
      if (result.success) {
        setPaymentData(result.data);
        if (onPaymentCreated) onPaymentCreated(result.data);
      } else {
        toast.error(result.message || 'Không thể tạo mã QR');
      }
    } catch (error) {
      console.error('Create payment error:', error);
      toast.error(error.message || 'Có lỗi xảy ra');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="payment-loading">
        <div className="spinner"></div>
        <p>Đang tạo mã QR thanh toán...</p>
      </div>
    );
  }

  if (!paymentData) {
    return null;
  }

  const { account_info } = paymentData;
  const isOwnerAccount = account_info?.is_owner_account;

  return (
    <div className="payment-qr-display">
      {/* Hiển thị thông báo về loại tài khoản */}
      <div className={`account-type-badge ${isOwnerAccount ? 'owner' : 'platform'}`}>
        {isOwnerAccount ? (
          <>
            <span className="icon">👤</span>
            <span>Thanh toán cho chủ sân</span>
          </>
        ) : (
          <>
            <span className="icon">🏢</span>
            <span>Thanh toán qua hệ thống</span>
          </>
        )}
      </div>

      {/* QR Code */}
      <div className="qr-code-container">
        <img 
          src={paymentData.qr_code_url} 
          alt="VietQR Code" 
          className="qr-code-image"
        />
      </div>

      {/* Thông tin thanh toán */}
      <div className="payment-info">
        <div className="info-row">
          <span className="label">Số tiền:</span>
          <span className="value amount">{paymentData.amount_formatted}</span>
        </div>
        
        <div className="info-row">
          <span className="label">Nội dung CK:</span>
          <span className="value transfer-content">{paymentData.transfer_content}</span>
        </div>
        
        <div className="info-divider"></div>
        
        <div className="info-row">
          <span className="label">Ngân hàng:</span>
          <span className="value">{account_info.bank_id}</span>
        </div>
        
        <div className="info-row">
          <span className="label">Số TK:</span>
          <span className="value">{account_info.account_no}</span>
        </div>
        
        <div className="info-row">
          <span className="label">Chủ TK:</span>
          <span className="value">{account_info.account_name}</span>
        </div>
      </div>

      {/* Hướng dẫn */}
      <div className="payment-instruction">
        <h4>📱 Hướng dẫn thanh toán:</h4>
        <ol>
          <li>Mở app ngân hàng của bạn</li>
          <li>Chọn tính năng "Quét QR" hoặc "Chuyển tiền"</li>
          <li>Quét mã QR phía trên</li>
          <li><strong>KIỂM TRA kỹ số tiền và nội dung CK</strong></li>
          <li>Xác nhận chuyển tiền</li>
          <li>Chụp màn hình bill và upload lên hệ thống</li>
        </ol>
        
        <div className="warning-box">
          ⚠️ <strong>Lưu ý:</strong> Nhập đúng nội dung <code>{paymentData.transfer_content}</code> 
          để hệ thống xác nhận thanh toán tự động.
        </div>
      </div>

      {/* Nút upload bill */}
      <div className="payment-actions">
        <button 
          className="btn btn-upload"
          onClick={() => {/* Handle upload bill */}}
        >
          📤 Upload Bill Chuyển Khoản
        </button>
      </div>
    </div>
  );
};

export default PaymentQRDisplay;
```

---

## ✅ VALIDATION RULES

### **Frontend Validation**

```javascript
// Validation cho Bank Info
const bankInfoValidation = {
  // Bank ID: Phải 6 số
  bank_id: {
    required: true,
    pattern: /^[0-9]{6}$/,
    message: 'Mã ngân hàng phải có đúng 6 số'
  },
  
  // Account No: 6-20 số
  account_no: {
    required: true,
    pattern: /^[0-9]{6,20}$/,
    message: 'Số tài khoản từ 6-20 số, chỉ chứa số'
  },
  
  // Account Name: CHỮ HOA và khoảng trắng
  account_name: {
    required: true,
    pattern: /^[A-Z\s]+$/,
    message: 'Tên phải viết HOA không dấu (VD: NGUYEN VAN A)'
  }
};

// Hàm helper để validate
const validateBankInfo = (data) => {
  const errors = {};
  
  Object.keys(bankInfoValidation).forEach(field => {
    const rule = bankInfoValidation[field];
    const value = data[field];
    
    if (rule.required && !value) {
      errors[field] = `${field} là bắt buộc`;
    } else if (value && rule.pattern && !rule.pattern.test(value)) {
      errors[field] = rule.message;
    }
  });
  
  return errors;
};
```

### **Input Helpers**

```javascript
// Auto convert sang CHỮ HOA không dấu
const normalizeAccountName = (input) => {
  return input
    .toUpperCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // Bỏ dấu
    .replace(/[^A-Z\s]/g, ''); // Chỉ giữ chữ HOA và space
};

// Usage trong component
const handleAccountNameChange = (e) => {
  const normalized = normalizeAccountName(e.target.value);
  setFormData(prev => ({
    ...prev,
    account_name: normalized
  }));
};
```

---

## 🧪 TESTING GUIDE

### **Test Case 1: Owner cập nhật Bank Info**

```javascript
// Test data
const testBankInfo = {
  bank_id: '970422',           // MB Bank
  account_no: '1234567890',
  account_name: 'NGUYEN VAN A'
};

// Test validation errors
const invalidData = [
  {
    bank_id: '97042',          // Chỉ 5 số → Error
    expected: 'Mã ngân hàng phải có đúng 6 số'
  },
  {
    account_no: '123abc',      // Có chữ → Error
    expected: 'Số tài khoản chỉ được chứa số'
  },
  {
    account_name: 'nguyen van a', // Chữ thường → Error
    expected: 'Tên phải viết HOA không dấu'
  }
];
```

### **Test Case 2: User thanh toán**

```javascript
// Mock response khi Owner CÓ bank info
const mockResponseWithOwnerAccount = {
  success: true,
  data: {
    qr_code_url: 'https://img.vietqr.io/...',
    account_info: {
      bank_id: '970422',
      account_no: '1234567890',
      account_name: 'NGUYEN VAN A',
      is_owner_account: true  // ← Owner account
    }
  }
};

// Mock response khi Owner CHƯA có bank info
const mockResponseWithPlatformAccount = {
  success: true,
  data: {
    account_info: {
      bank_id: '970422',
      account_no: '0123456789',  // Platform account
      account_name: 'PLATFORM',
      is_owner_account: false  // ← Platform account
    }
  }
};
```

---

## 📦 SERVICE FILE

**File:** `src/services/vietqr.service.js`

```javascript
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

export const VietQRService = {
  /**
   * Lấy danh sách ngân hàng (Public API)
   */
  getBanks: async () => {
    try {
      const response = await axios.get(`${API_URL}/vietqr/banks`);
      return response.data.data || [];
    } catch (error) {
      console.error('Get banks error:', error);
      throw error;
    }
  },

  /**
   * Tạo QR thanh toán
   */
  createPayment: async (bookingId, token) => {
    try {
      const response = await axios.post(
        `${API_URL}/vietqr/create-payment/${bookingId}`,
        {},
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );
      return response.data;
    } catch (error) {
      console.error('Create payment error:', error);
      throw error.response?.data || error;
    }
  },

  /**
   * Kiểm tra trạng thái thanh toán
   */
  checkStatus: async (bookingId, token) => {
    try {
      const response = await axios.get(
        `${API_URL}/vietqr/check-status/${bookingId}`,
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );
      return response.data;
    } catch (error) {
      console.error('Check status error:', error);
      throw error.response?.data || error;
    }
  }
};
```

---

## 🎨 UI/UX RECOMMENDATIONS

### **1. Badge hiển thị loại tài khoản**

```jsx
{paymentData.account_info.is_owner_account ? (
  <div className="badge badge-owner">
    👤 Thanh toán trực tiếp cho chủ sân
  </div>
) : (
  <div className="badge badge-platform">
    🏢 Thanh toán qua hệ thống
  </div>
)}
```

### **2. Tooltip giải thích**

```jsx
<Tooltip content="Tiền sẽ được chuyển trực tiếp vào tài khoản của chủ sân">
  <InfoIcon />
</Tooltip>
```

### **3. Loading states**

```jsx
{loading ? (
  <div className="skeleton-loader">
    <div className="skeleton skeleton-qr"></div>
    <div className="skeleton skeleton-text"></div>
  </div>
) : (
  <QRDisplay />
)}
```

---

## 📱 RESPONSIVE DESIGN

```css
/* Mobile first */
.payment-qr-display {
  padding: 15px;
}

.qr-code-image {
  max-width: 100%;
  height: auto;
}

/* Tablet */
@media (min-width: 768px) {
  .payment-qr-display {
    padding: 30px;
  }
  
  .qr-code-image {
    max-width: 400px;
  }
}

/* Desktop */
@media (min-width: 1024px) {
  .payment-qr-display {
    max-width: 600px;
    margin: 0 auto;
  }
}
```

---

## 🚀 DEPLOYMENT CHECKLIST

- [ ] Cập nhật `.env` với API_URL production
- [ ] Test form Owner Profile với data thật
- [ ] Test payment flow end-to-end
- [ ] Verify validation messages
- [ ] Test responsive trên mobile
- [ ] Test error handling
- [ ] Check console logs (không có error)
- [ ] Test với nhiều browsers
- [ ] Verify QR code hiển thị đúng
- [ ] Test upload bill functionality

---

## 📞 HỖ TRỢ

**Backend API Docs:** `/VIETQR_INTEGRATION_GUIDE.md`
**Quick Start:** `/VIETQR_QUICK_START.md`

**Liên hệ:** support@example.com
