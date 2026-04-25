# QR Code Upload API Documentation

## ✅ Cập Nhật: Backend Chỉ Chấp Nhận Full URL

Backend đã được cập nhật để **BẮT BUỘC** sử dụng full URL từ Supabase, **TỪ CHỐI** relative path.

---

## 📋 API Endpoints

### 1. Upload QR Code (Owner Only)

```http
POST /api/owner/upload-qr
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "qr_code_url": "https://xxx.supabase.co/storage/v1/object/public/owner-qr-codes/1714089800000-xyz789.jpg"
}
```

**Validation Rules:**
- ✅ PHẢI là full URL (bắt đầu bằng `http://` hoặc `https://`)
- ✅ Max length: 1000 characters
- ✅ Valid URL format
- ❌ TỪ CHỐI relative path (`owner_qr_codes/xxx.jpg`)
- ❌ TỪ CHỐI absolute path (`/storage/xxx.jpg`)
- ❌ TỪ CHỐI protocols khác (`ftp://`, `file://`)

**Success Response (200):**
```json
{
  "status": true,
  "message": "Lưu QR code thành công",
  "data": {
    "qr_code_url": "https://xxx.supabase.co/storage/v1/object/public/owner-qr-codes/1714089800000-xyz789.jpg",
    "owner_profile": {
      "id": 5,
      "user_id": 10,
      "business_name": "Công ty ABC",
      "qr_code_image": "https://xxx.supabase.co/storage/v1/object/public/owner-qr-codes/1714089800000-xyz789.jpg"
    }
  }
}
```

**Error Response (422 Validation Failed):**
```json
{
  "status": false,
  "message": "Validation failed",
  "data": {
    "qr_code_url": [
      "The qr code url field must be a valid URL.",
      "The qr code url field format is invalid."
    ]
  }
}
```

**Error Response (422 Relative Path):**
```json
{
  "status": false,
  "message": "QR code URL phải là đường dẫn đầy đủ (http:// hoặc https://)",
  "data": null
}
```

---

### 2. Get QR Code (Owner Only)

```http
GET /api/owner/qr-code
Authorization: Bearer {jwt_token}
```

**Success Response (200):**
```json
{
  "status": true,
  "message": "Thông tin QR code",
  "data": {
    "qr_code_url": "https://xxx.supabase.co/storage/v1/object/public/owner-qr-codes/1714089800000-xyz789.jpg",
    "has_qr_code": true
  }
}
```

---

### 3. Update Owner Profile (Optional QR Code)

```http
PUT /api/owner/profile
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "business_name": "Công ty ABC",
  "address": "123 Đường XYZ",
  "qr_code_image": "https://xxx.supabase.co/storage/v1/object/public/owner-qr-codes/1714089800000-xyz789.jpg"
}
```

**Note:** `qr_code_image` là optional. Nếu truyền, phải là full URL.

---

## 🚀 Frontend Implementation Guide

### Step 1: Upload File to Supabase

```javascript
const uploadQRCode = async (file) => {
  const supabase = createClient(SUPABASE_URL, SUPABASE_KEY);
  
  // Upload to Supabase
  const fileName = `owner-qr-codes/${Date.now()}-${Math.random().toString(36).substring(7)}.${file.name.split('.').pop()}`;
  
  const { data, error } = await supabase.storage
    .from('images')
    .upload(fileName, file, {
      cacheControl: '3600',
      upsert: false
    });
  
  if (error) {
    throw new Error('Upload failed: ' + error.message);
  }
  
  // Get public URL
  const { data: { publicUrl } } = supabase.storage
    .from('images')
    .getPublicUrl(fileName);
  
  return publicUrl;
};
```

### Step 2: Send URL to Backend

```javascript
const saveQRCodeToBackend = async (publicUrl) => {
  const response = await fetch('http://localhost/api/owner/upload-qr', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('token')}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      qr_code_url: publicUrl
    })
  });
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Upload failed');
  }
  
  return await response.json();
};
```

### Step 3: Complete Flow

```javascript
const handleQRCodeUpload = async (file) => {
  try {
    // 1. Upload to Supabase
    const publicUrl = await uploadQRCode(file);
    console.log('Supabase URL:', publicUrl);
    
    // 2. Send to Backend
    const result = await saveQRCodeToBackend(publicUrl);
    console.log('Backend response:', result);
    
    alert('QR code đã được lưu thành công!');
    return result;
  } catch (error) {
    console.error('Error:', error);
    alert('Lỗi: ' + error.message);
  }
};
```

---

## ⚠️ Important Notes

### ❌ KHÔNG ĐƯỢC GỬI:
```javascript
// ❌ Relative path
{
  "qr_code_url": "owner_qr_codes/qr.jpg"
}

// ❌ Absolute path without domain
{
  "qr_code_url": "/storage/owner_qr_codes/qr.jpg"
}

// ❌ asset() helper result
{
  "qr_code_url": "http://localhost/storage/owner_qr_codes/qr.jpg"
}
```

### ✅ PHẢI GỬI:
```javascript
// ✅ Full Supabase URL
{
  "qr_code_url": "https://xxx.supabase.co/storage/v1/object/public/images/owner-qr-codes/1714089800000-xyz789.jpg"
}
```

---

## 🔧 Testing

Run validation test:
```bash
php test-qr-code-validation.php
```

Expected output: All tests should PASS ✅

---

## 📊 Database Schema

**Table:** `owner_profiles`

**Column:** `qr_code_image`
- Type: `VARCHAR(1000)`
- Nullable: `YES`
- Content: **Full URL only** (e.g., `https://xxx.supabase.co/...`)

**Migration (if needed):**
```sql
ALTER TABLE owner_profiles 
MODIFY COLUMN qr_code_image VARCHAR(1000) NULL;
```

---

## 🎯 Use Cases

### Use Case 1: Owner Upload QR Code lần đầu
1. Owner vào trang Profile
2. Click "Upload QR Code"
3. Chọn file ảnh
4. Frontend upload lên Supabase → nhận publicUrl
5. Frontend gửi publicUrl đến `/api/owner/upload-qr`
6. Backend validate và lưu vào `owner_profiles.qr_code_image`

### Use Case 2: Owner Update QR Code
1. Owner upload file mới lên Supabase
2. Frontend gửi URL mới đến `/api/owner/upload-qr`
3. Backend **ghi đè** URL cũ bằng URL mới

### Use Case 3: User tạo giải đấu và xem QR Code
1. User tạo giải đấu tại cụm sân của Owner X
2. Frontend gọi `GET /api/tournaments/{id}`
3. Backend trả về `payment_info.qr_code_url` (từ `owner_profiles.qr_code_image`)
4. Frontend hiển thị QR code để user thanh toán

---

## 🐛 Troubleshooting

### Problem: Backend trả về relative path
**Cause:** Owner đã upload QR bằng cách cũ (file upload)

**Solution:**
1. Owner upload lại QR code bằng component ImageUpload mới
2. Frontend đảm bảo gửi full URL từ Supabase

### Problem: QR code không hiển thị
**Cause 1:** Backend chưa có owner relationship trong API response

**Solution:** Check `/api/public/field-clusters/{id}` có trả về `owner.qr_code_image` không

**Cause 2:** Owner chưa upload QR code

**Solution:** Owner vào Profile và upload QR code

### Problem: Validation error "invalid URL"
**Cause:** Frontend gửi relative path

**Solution:** Đảm bảo frontend gửi full URL từ Supabase:
```javascript
// ✅ Correct
const { data: { publicUrl } } = supabase.storage
  .from('images')
  .getPublicUrl(fileName);

// Send publicUrl to backend
```

---

## ✅ Checklist

### Backend:
- [x] `uploadQRCode()` validates full URL only
- [x] `updateOwnerProfile()` validates full URL for qr_code_image
- [x] `publicShow()` includes owner relationship
- [x] Validation rules: url, max:1000, regex:/^https?:\/\//
- [x] Test script created and passed

### Frontend:
- [ ] ImageUpload component uploads to Supabase
- [ ] Gets publicUrl from Supabase
- [ ] Sends full URL to `/api/owner/upload-qr`
- [ ] Displays QR code in tournament payment page
- [ ] Handles errors (validation, upload failed)

---

## 📞 Contact

Nếu có vấn đề, check:
1. Test validation: `php test-qr-code-validation.php`
2. API logs: `storage/logs/laravel.log`
3. Frontend console: Browser DevTools → Network tab
