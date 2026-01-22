# 🔐 HƯỚNG DẪN JWT AUTHENTICATION - DỰ ÁN SÂN BÓNG ĐÁ

## 📚 MỤC LỤC
1. [Tổng quan hệ thống](#tổng-quan-hệ-thống)
2. [Giải thích chi tiết từng phần](#giải-thích-chi-tiết-từng-phần)
3. [Cách hoạt động của JWT](#cách-hoạt-động-của-jwt)
4. [Hướng dẫn test API](#hướng-dẫn-test-api)
5. [Xử lý lỗi thường gặp](#xử-lý-lỗi-thường-gặp)

---

## 1️⃣ TỔNG QUAN HỆ THỐNG

### ✅ Đã hoàn thành:

```
📁 Project Structure:
│
├── 📂 app/Http/
│   ├── 📂 Controllers/Api/
│   │   ├── AuthController.php        ✅ Xử lý đăng ký, đăng nhập, logout
│   │   └── UserController.php        ✅ CRUD users (đã có sẵn)
│   │
│   ├── 📂 Middleware/
│   │   └── JwtMiddleware.php         ✅ Kiểm tra token có hợp lệ
│   │
│   └── 📂 Requests/Auth/
│       ├── RegisterRequest.php       ✅ Validate đăng ký
│       └── LoginRequest.php          ✅ Validate đăng nhập
│
├── 📂 routes/
│   └── api.php                       ✅ Định nghĩa routes (public & protected)
│
└── 📂 bootstrap/
    └── app.php                       ✅ Đăng ký middleware
```

---

## 2️⃣ GIẢI THÍCH CHI TIẾT TỪNG PHẦN

### 🛡️ A. JwtMiddleware - Bảo vệ Routes

**File:** `app/Http/Middleware/JwtMiddleware.php`

**Chức năng:** Kiểm tra token có hợp lệ trước khi cho phép truy cập API

```php
// Middleware này làm 3 việc:
1. Đọc token từ header "Authorization: Bearer {token}"
2. Kiểm tra token có hợp lệ không
3. Nếu hợp lệ → cho phép tiếp tục
   Nếu không → trả về lỗi 401
```

**Các trường hợp kiểm tra:**
- ✅ Token hợp lệ → Cho phép truy cập
- ❌ Token hết hạn → Trả lỗi "Token đã hết hạn"
- ❌ Token không hợp lệ → Trả lỗi "Token không hợp lệ"
- ❌ Không có token → Trả lỗi "Vui lòng cung cấp token"

---

### 📋 B. Request Validation Classes

#### **RegisterRequest.php** - Validate đăng ký

```php
Kiểm tra:
- name: Bắt buộc, tối đa 255 ký tự
- email: Bắt buộc, đúng format email, chưa tồn tại trong DB
- password: Bắt buộc, tối thiểu 6 ký tự, phải có password_confirmation
```

#### **LoginRequest.php** - Validate đăng nhập

```php
Kiểm tra:
- email: Bắt buộc, đúng format email
- password: Bắt buộc, tối thiểu 6 ký tự
```

**Lợi ích:** 
- Tách biệt logic validation khỏi controller
- Tự động trả lỗi nếu validation fail
- Messages lỗi tiếng Việt rõ ràng

---

### 🎮 C. AuthController - Xử lý Logic

**File:** `app/Http/Controllers/Api/AuthController.php`

#### **1. register() - Đăng ký tài khoản**

**Flow hoạt động:**
```
FE gửi data → RegisterRequest validate → Tạo user mới → Hash password 
→ Lưu vào DB → Tạo JWT token → Trả về user + token
```

**Request từ FE:**
```json
POST /api/auth/register
{
  "name": "Nguyen Van A",
  "email": "test@example.com",
  "password": "123456",
  "password_confirmation": "123456"
}
```

**Response thành công:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Nguyen Van A",
      "email": "test@example.com"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600
  },
  "status_code": 201
}
```

---

#### **2. login() - Đăng nhập**

**Flow hoạt động:**
```
FE gửi email+password → LoginRequest validate → Kiểm tra credentials
→ Nếu đúng: Tạo JWT token → Trả về user + token
→ Nếu sai: Trả lỗi 401
```

**Request từ FE:**
```json
POST /api/auth/login
{
  "email": "test@example.com",
  "password": "123456"
}
```

**Response thành công:**
```json
{
  "success": true,
  "message": "Login successfully",
  "data": {
    "user": { ... },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Response thất bại:**
```json
{
  "success": false,
  "message": "Login failed",
  "error": "Email hoặc mật khẩu không chính xác",
  "status_code": 401
}
```

---

#### **3. me() - Lấy thông tin user đang đăng nhập**

**⚠️ Route này CẦN token**

**Request từ FE:**
```
GET /api/auth/me
Headers:
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response:**
```json
{
  "success": true,
  "message": "User retrieved successfully",
  "data": {
    "id": 1,
    "name": "Nguyen Van A",
    "email": "test@example.com"
  }
}
```

---

#### **4. logout() - Đăng xuất**

**⚠️ Route này CẦN token**

Vô hiệu hóa token hiện tại. User cần đăng nhập lại để lấy token mới.

**Request từ FE:**
```
POST /api/auth/logout
Headers:
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

#### **5. refresh() - Làm mới token**

**⚠️ Route này CẦN token**

Khi token sắp hết hạn, FE gọi route này để lấy token mới mà không cần đăng nhập lại.

**Request từ FE:**
```
POST /api/auth/refresh
Headers:
  Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc... (token cũ)
```

**Response:**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc... (token mới)",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

---

### 🛣️ D. Routes API - Phân quyền rõ ràng

**File:** `routes/api.php`

```php
// ============= PUBLIC ROUTES (Không cần token) =============
POST /api/auth/register    → Đăng ký
POST /api/auth/login       → Đăng nhập

// ============= PROTECTED ROUTES (CẦN token) =============
POST   /api/auth/logout    → Đăng xuất
POST   /api/auth/refresh   → Làm mới token
GET    /api/auth/me        → Lấy thông tin user

GET    /api/users          → Danh sách users
POST   /api/users          → Tạo user mới
GET    /api/users/{id}     → Chi tiết user
PUT    /api/users/{id}     → Cập nhật user
DELETE /api/users/{id}     → Xóa user
```

---

## 3️⃣ CÁCH HOẠT ĐỘNG CỦA JWT

### 🔄 Quy trình hoàn chỉnh:

```
┌─────────────┐                           ┌─────────────┐
│   Frontend  │                           │   Backend   │
└─────────────┘                           └─────────────┘
      │                                          │
      │ 1. POST /api/auth/login                 │
      │    {email, password}                    │
      ├────────────────────────────────────────>│
      │                                          │
      │                            2. Kiểm tra credentials
      │                               Nếu đúng → Tạo JWT token
      │                                          │
      │ 3. Response: {user, token}              │
      │<────────────────────────────────────────┤
      │                                          │
      │ 4. Lưu token vào localStorage           │
      │                                          │
      │                                          │
      │ 5. Gọi API có bảo vệ                    │
      │    GET /api/users                       │
      │    Header: Authorization: Bearer token  │
      ├────────────────────────────────────────>│
      │                                          │
      │                            6. JwtMiddleware kiểm tra:
      │                               - Token có hợp lệ?
      │                               - Token hết hạn chưa?
      │                                          │
      │ 7. Response: {data}                     │
      │<────────────────────────────────────────┤
      │                                          │
```

### 🔑 Token Structure:

JWT token gồm 3 phần (cách nhau bởi dấu `.`):

```
eyJ0eXAiOiJKV1QiLCJhbGc.eyJpc3MiOiJodHRwOi8v.SflKxwRJSMeKKF2Q
    ↑ HEADER          ↑ PAYLOAD          ↑ SIGNATURE
```

1. **Header:** Loại token và thuật toán mã hóa
2. **Payload:** Thông tin user (id, email, exp time...)
3. **Signature:** Chữ ký để verify token

---

## 4️⃣ HƯỚNG DẪN TEST API

### 🧪 A. Test với Postman / Thunder Client

#### **Test 1: Đăng ký**
```
Method: POST
URL: http://localhost/api/auth/register
Body (JSON):
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "123456",
  "password_confirmation": "123456"
}

✅ Response: Nhận được token
```

#### **Test 2: Đăng nhập**
```
Method: POST
URL: http://localhost/api/auth/login
Body (JSON):
{
  "email": "test@example.com",
  "password": "123456"
}

✅ Response: Nhận được token
📝 Copy token này để test các API tiếp theo
```

#### **Test 3: Lấy thông tin user (CẦN token)**
```
Method: GET
URL: http://localhost/api/auth/me
Headers:
  Authorization: Bearer {paste token ở đây}

✅ Response: Thông tin user
```

#### **Test 4: Danh sách users (CẦN token)**
```
Method: GET
URL: http://localhost/api/users
Headers:
  Authorization: Bearer {paste token ở đây}

✅ Response: Danh sách users
```

#### **Test 5: Đăng xuất (CẦN token)**
```
Method: POST
URL: http://localhost/api/auth/logout
Headers:
  Authorization: Bearer {paste token ở đây}

✅ Response: Logout successfully
❌ Dùng token này nữa sẽ bị lỗi "Token invalid"
```

---

### 🎯 B. Test với JavaScript (Frontend)

#### **1. Đăng ký:**
```javascript
const register = async () => {
  try {
    const response = await fetch('http://localhost/api/auth/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        name: 'Test User',
        email: 'test@example.com',
        password: '123456',
        password_confirmation: '123456'
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Lưu token vào localStorage
      localStorage.setItem('token', data.data.access_token);
      console.log('Đăng ký thành công!', data.data.user);
    } else {
      console.error('Đăng ký thất bại:', data.error);
    }
  } catch (error) {
    console.error('Lỗi:', error);
  }
};
```

#### **2. Đăng nhập:**
```javascript
const login = async () => {
  try {
    const response = await fetch('http://localhost/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'test@example.com',
        password: '123456'
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Lưu token vào localStorage
      localStorage.setItem('token', data.data.access_token);
      console.log('Đăng nhập thành công!', data.data.user);
    } else {
      console.error('Đăng nhập thất bại:', data.error);
    }
  } catch (error) {
    console.error('Lỗi:', error);
  }
};
```

#### **3. Gọi API có bảo vệ (có token):**
```javascript
const getUserInfo = async () => {
  const token = localStorage.getItem('token');
  
  if (!token) {
    console.error('Chưa đăng nhập!');
    return;
  }
  
  try {
    const response = await fetch('http://localhost/api/auth/me', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      console.log('Thông tin user:', data.data);
    } else {
      if (data.status_code === 401) {
        // Token hết hạn hoặc không hợp lệ
        console.error('Token không hợp lệ, vui lòng đăng nhập lại');
        localStorage.removeItem('token');
      }
    }
  } catch (error) {
    console.error('Lỗi:', error);
  }
};
```

#### **4. Đăng xuất:**
```javascript
const logout = async () => {
  const token = localStorage.getItem('token');
  
  if (!token) {
    console.error('Chưa đăng nhập!');
    return;
  }
  
  try {
    const response = await fetch('http://localhost/api/auth/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Xóa token khỏi localStorage
      localStorage.removeItem('token');
      console.log('Đăng xuất thành công!');
    }
  } catch (error) {
    console.error('Lỗi:', error);
  }
};
```

#### **5. Refresh Token (làm mới token):**
```javascript
const refreshToken = async () => {
  const token = localStorage.getItem('token');
  
  if (!token) {
    console.error('Chưa đăng nhập!');
    return;
  }
  
  try {
    const response = await fetch('http://localhost/api/auth/refresh', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Lưu token mới
      localStorage.setItem('token', data.data.access_token);
      console.log('Đã làm mới token!');
    }
  } catch (error) {
    console.error('Lỗi:', error);
  }
};
```

---

## 5️⃣ XỬ LÝ LỖI THƯỜNG GẶP

### ❌ Lỗi 1: "Token absent - Vui lòng cung cấp token"

**Nguyên nhân:** 
- Không gửi token trong header
- Hoặc gửi sai format

**Giải pháp:**
```javascript
// ❌ SAI
headers: {
  'Authorization': 'eyJ0eXAiOiJKV1QiLCJhbGc...'
}

// ✅ ĐÚNG
headers: {
  'Authorization': 'Bearer eyJ0eXAiOiJKV1QiLCJhbGc...'
}
```

---

### ❌ Lỗi 2: "Token expired - Token đã hết hạn"

**Nguyên nhân:** Token hết hạn (mặc định 60 phút)

**Giải pháp:**
1. Gọi `/api/auth/refresh` để lấy token mới
2. Hoặc đăng nhập lại `/api/auth/login`

---

### ❌ Lỗi 3: "Token invalid - Token không hợp lệ"

**Nguyên nhân:** 
- Token bị sửa đổi
- Token đã logout
- Token không đúng format

**Giải pháp:** Đăng nhập lại để lấy token mới

---

### ❌ Lỗi 4: "Email hoặc mật khẩu không chính xác"

**Nguyên nhân:** Sai email hoặc password khi đăng nhập

**Giải pháp:** Kiểm tra lại credentials

---

### ❌ Lỗi 5: "Email đã được sử dụng"

**Nguyên nhân:** Email đã tồn tại trong database

**Giải pháp:** Dùng email khác hoặc đăng nhập với email đó

---

## 📝 CHECKLIST TRIỂN KHAI

Trước khi deploy production, kiểm tra:

- [ ] JWT_SECRET đã được set trong `.env`
- [ ] Đã chạy migration: `php artisan migrate`
- [ ] Đã test tất cả endpoints
- [ ] Đã xử lý CORS nếu FE khác domain
- [ ] Token expiry time phù hợp với yêu cầu
- [ ] Đã có rate limiting để chống brute force
- [ ] Đã log các attempt đăng nhập thất bại

---

## 🎓 KIẾN THỨC BỔ SUNG

### JWT vs Session Authentication

| JWT                          | Session                      |
|------------------------------|------------------------------|
| Stateless (không lưu server) | Stateful (lưu trên server)   |
| Token tự chứa thông tin      | Chỉ lưu session ID           |
| Phù hợp API, Mobile          | Phù hợp web truyền thống     |
| Khó revoke token             | Dễ dàng revoke session       |

### Cấu trúc ApiResponse Helper

```php
ApiResponse::success($message, $data, $code)
ApiResponse::error($message, $error, $code)
```

---

## 🚀 BÀI TẬP THỰC HÀNH

1. **Thêm role-based access control:**
   - Admin có thể xem/xóa tất cả users
   - User thường chỉ xem/sửa chính mình

2. **Thêm forgot password:**
   - Gửi email reset password
   - Verify token và đổi password

3. **Thêm email verification:**
   - User phải verify email trước khi login
   - Gửi email có link verify

4. **Thêm rate limiting:**
   - Giới hạn số lần đăng nhập thất bại
   - Block IP sau 5 lần sai

---

**🎉 CHÚC BẠN THÀNH CÔNG!**

Nếu có vấn đề gì, hãy kiểm tra lại từng bước theo tài liệu này.
