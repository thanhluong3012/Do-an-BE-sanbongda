# HƯỚNG DẪN QUẢN LÝ NHÂN VIÊN (STAFF)

## Tổng Quan
Hệ thống quản lý nhân viên đã được cập nhật để đảm bảo:
- Mỗi nhân viên (Staff) thuộc về một chủ sân (Owner) cụ thể
- Chủ sân chỉ có thể quản lý nhân viên của chính mình
- Admin có thể quản lý tất cả nhân viên

## Cấu Trúc Database

### Bảng `staffs`
```sql
- id: Primary Key
- user_id: Foreign Key -> users.id (cascade delete)
- owner_id: Foreign Key -> owner_profiles.id (cascade delete)
- code_id: Căn cước công dân (nullable)
- avatar: Ảnh đại diện (nullable)
- timestamps
```

### Quan Hệ
- **Staff** belongs to **User** (1-1)
- **Staff** belongs to **OwnerProfile** (Many-1)
- **OwnerProfile** has many **Staffs** (1-Many)

---

## API Endpoints

### 1. Lấy Danh Sách Nhân Viên
**Endpoint:** `GET /api/staffs`  
**Auth:** Required (JWT Token)  
**Roles:** Admin, Owner

**Hành Vi:**
- **Admin:** Xem tất cả nhân viên trong hệ thống
- **Owner:** Chỉ xem nhân viên thuộc về mình

**Response:**
```json
{
  "status": true,
  "message": "Danh sách nhân viên",
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "owner_id": 2,
      "code_id": "0123456789",
      "avatar": "avatar.jpg",
      "created_at": "2026-02-18T10:00:00.000000Z",
      "updated_at": "2026-02-18T10:00:00.000000Z",
      "user": {
        "id": 5,
        "role_id": 3,
        "name": "Nguyễn Văn A",
        "email": "staff@example.com",
        "phone": "0123456789",
        "status": true
      },
      "owner": {
        "id": 2,
        "user_id": 3,
        "business_name": "Sân Bóng ABC",
        "address": "123 Đường XYZ",
        "user": {
          "id": 3,
          "name": "Chủ Sân ABC",
          "email": "owner@example.com"
        }
      }
    }
  ],
  "code": 200
}
```

---

### 2. Tạo Nhân Viên Mới
**Endpoint:** `POST /api/staffs`  
**Auth:** Required (JWT Token)  
**Roles:** Owner only

**Request Body:**
```json
{
  "name": "Nguyễn Văn A",
  "email": "staff@example.com",
  "phone": "0123456789",
  "password": "password123",
  "code_id": "0123456789",  // Optional
  "avatar": "avatar.jpg"    // Optional
}
```

**Lưu Ý:**
- `owner_id` tự động lấy từ owner đang đăng nhập
- `role_id` tự động set là "staff"
- `user_id` và `owner_id` KHÔNG được gửi từ FE

**Response:**
```json
{
  "status": true,
  "message": "Tạo nhân viên thành công",
  "data": {
    "id": 1,
    "user_id": 5,
    "owner_id": 2,
    "code_id": "0123456789",
    "avatar": "avatar.jpg",
    "user": {
      "id": 5,
      "name": "Nguyễn Văn A",
      "email": "staff@example.com"
    }
  },
  "code": 201
}
```

---

### 3. Xem Chi Tiết Nhân Viên
**Endpoint:** `GET /api/staffs/{id}`  
**Auth:** Required (JWT Token)  
**Roles:** Admin, Owner

**Hành Vi:**
- **Admin:** Xem bất kỳ nhân viên nào
- **Owner:** Chỉ xem nhân viên của mình

**Response:** Tương tự như item trong danh sách

---

### 4. Cập Nhật Nhân Viên
**Endpoint:** `PUT/PATCH /api/staffs/{id}`  
**Auth:** Required (JWT Token)  
**Roles:** Admin, Owner

**Request Body (Owner):**
```json
{
  "code_id": "9876543210",  // Optional
  "avatar": "new-avatar.jpg" // Optional
}
```

**Request Body (Admin):**
```json
{
  "user_id": 6,              // Optional - Chỉ Admin
  "owner_id": 3,             // Optional - Chỉ Admin
  "code_id": "9876543210",   // Optional
  "avatar": "new-avatar.jpg" // Optional
}
```

**Quyền Hạn:**
- **Owner:** Chỉ cập nhật `code_id` và `avatar` của nhân viên thuộc mình
- **Admin:** Có thể cập nhật tất cả fields, bao gồm `user_id` và `owner_id`

**Response:**
```json
{
  "status": true,
  "message": "Cập nhật thành công",
  "data": { /* staff object */ },
  "code": 200
}
```

---

### 5. Xóa Nhân Viên
**Endpoint:** `DELETE /api/staffs/{id}`  
**Auth:** Required (JWT Token)  
**Roles:** Admin, Owner

**Hành Vi:**
- **Admin:** Xóa bất kỳ nhân viên nào
- **Owner:** Chỉ xóa nhân viên của mình
- Khi xóa Staff, User tương ứng cũng tự động bị xóa (cascade delete)

**Response:**
```json
{
  "status": true,
  "message": "Xóa nhân viên thành công",
  "data": null,
  "code": 200
}
```

---

## Lỗi Thường Gặp

### 1. Không Có Quyền Truy Cập (403)
```json
{
  "status": false,
  "message": "Bạn không có quyền xem nhân viên này",
  "data": null,
  "code": 403
}
```
**Nguyên nhân:** Owner đang cố truy cập nhân viên không thuộc về mình

### 2. Chưa Có Owner Profile (400)
```json
{
  "status": false,
  "message": "Bạn chưa có owner profile",
  "data": null,
  "code": 400
}
```
**Nguyên nhân:** User có role "owner" nhưng chưa có owner_profile

### 3. Không Tìm Thấy Nhân Viên (404)
```json
{
  "status": false,
  "message": "Không tìm thấy nhân viên",
  "data": null,
  "code": 404
}
```

### 4. Validation Failed (422)
```json
{
  "status": false,
  "message": "Validation failed",
  "data": {
    "email": ["The email has already been taken."]
  },
  "code": 422
}
```

---

## Hướng Dẫn Frontend

### 1. Hiển Thị Danh Sách Nhân Viên Của Owner
```javascript
// Gọi API (token tự động gửi trong header)
const response = await fetch('/api/staffs', {
  method: 'GET',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
});

const data = await response.json();
// data.data sẽ chỉ chứa nhân viên thuộc owner đang login
```

### 2. Tạo Nhân Viên Mới
```javascript
const newStaff = {
  name: "Nguyễn Văn A",
  email: "staff@example.com",
  phone: "0123456789",
  password: "password123",
  code_id: "0123456789",
  avatar: "avatar.jpg"
};

const response = await fetch('/api/staffs', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify(newStaff)
});
```

### 3. Cập Nhật Thông Tin Nhân Viên
```javascript
const updates = {
  code_id: "9876543210",
  avatar: "new-avatar.jpg"
};

const response = await fetch(`/api/staffs/${staffId}`, {
  method: 'PUT', // hoặc 'PATCH'
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify(updates)
});
```

### 4. Xóa Nhân Viên
```javascript
const response = await fetch(`/api/staffs/${staffId}`, {
  method: 'DELETE',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
});
```

---

## Bảo Mật

### 1. Authentication
- Tất cả endpoints đều yêu cầu JWT token
- Token được gửi trong header: `Authorization: Bearer {token}`

### 2. Authorization
- **Owner:** Chỉ thao tác được với nhân viên của chính mình
- **Admin:** Có toàn quyền với mọi nhân viên
- Backend tự động kiểm tra quyền, FE không cần quan tâm

### 3. Data Protection
- `owner_id` tự động lấy từ user đang login khi tạo staff
- Owner không thể thay đổi `owner_id` hoặc `user_id` khi update
- Password được hash trước khi lưu vào database

---

## Testing

### Test với Postman

#### 1. Login để lấy token
```
POST /api/auth/login
Body: {
  "email": "owner@example.com",
  "password": "password"
}
```

#### 2. Lấy danh sách nhân viên
```
GET /api/staffs
Headers: {
  "Authorization": "Bearer {token}",
  "Accept": "application/json"
}
```

#### 3. Tạo nhân viên mới
```
POST /api/staffs
Headers: {
  "Authorization": "Bearer {token}",
  "Content-Type": "application/json",
  "Accept": "application/json"
}
Body: {
  "name": "Test Staff",
  "email": "teststaff@example.com",
  "phone": "0987654321",
  "password": "password123",
  "code_id": "123456789"
}
```

---

## Các Files Đã Xóa

Các file sau đã được xóa khỏi project vì không cần thiết cho API-only project:

1. **Frontend Assets:**
   - `resources/views/welcome.blade.php`
   - `resources/css/app.css`
   - `resources/js/` (toàn bộ thư mục)
   - `package.json`
   - `vite.config.js`

2. **Incomplete Features:**
   - `app/Http/Controllers/Api/FieldController.php`
   - `app/Http/Resources/FieldResource.php`
   - `app/Http/Requests/FieldRequest.php`

3. **Test Examples:**
   - `tests/Feature/ExampleTest.php`
   - `tests/Unit/ExampleTest.php`

4. **Unused Migrations:**
   - `database/migrations/2026_01_19_090542_create_personal_access_tokens_table.php`

---

## Lưu Ý Quan Trọng

1. **Relationship đã được thiết lập:**
   - Staff model có relationship `owner()` -> OwnerProfile
   - OwnerProfile model có relationship `staffs()` -> Many Staffs

2. **Cascade Delete:**
   - Khi xóa Staff, User tương ứng tự động bị xóa
   - Khi xóa Owner, tất cả Staff của owner đó bị xóa

3. **Frontend chỉ cần:**
   - Gọi `GET /api/staffs` để lấy danh sách (đã tự động filter theo owner)
   - Không cần gửi `owner_id` khi tạo staff mới
   - Không cần filter thủ công trên FE

4. **Middleware đã được áp dụng:**
   - `jwt.auth`: Yêu cầu đăng nhập
   - `check.role:admin,owner`: Chỉ Admin và Owner mới truy cập được
