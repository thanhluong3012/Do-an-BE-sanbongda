# 📋 QUYỀN HẠN OWNER TRÊN HỆ THỐNG SÂN BÓNG

## ✅ TẤT CẢ QUYỀN OWNER CÓ THỂ THỰC HIỆN

### 🏢 QUẢN LÝ CỤM SÂN (Field Clusters)

Owner có **TOÀN QUYỀN** trên cụm sân của mình:

| Hành động | Endpoint | Method | Quyền |
|-----------|----------|--------|-------|
| Xem danh sách cụm sân của mình | `/api/field-clusters` | GET | ✅ Có (chỉ thấy cluster của mình) |
| Xem chi tiết cụm sân | `/api/field-clusters/{id}` | GET | ✅ Có (nếu là cluster của mình) |
| Tạo cụm sân mới | `/api/field-clusters` | POST | ✅ Có |
| Cập nhật cụm sân | `/api/field-clusters/{id}` | PUT | ✅ Có (nếu là cluster của mình) |
| Xóa cụm sân | `/api/field-clusters/{id}` | DELETE | ✅ Có (nếu là cluster của mình) |

**Middleware:** `check.role:admin,owner`

---

### ⚽ QUẢN LÝ SÂN CON (Fields)

Owner có **TOÀN QUYỀN** trên tất cả sân thuộc cụm sân của mình:

| Hành động | Endpoint | Method | Quyền |
|-----------|----------|--------|-------|
| Xem danh sách sân | `/api/fields` | GET | ✅ Có (chỉ thấy sân trong clusters của mình) |
| Xem chi tiết sân | `/api/fields/{id}` | GET | ✅ Có (nếu sân thuộc cluster của mình) |
| **Tạo sân mới** | `/api/fields` | POST | ✅ Có (chỉ trong clusters của mình) |
| **Cập nhật sân** | `/api/fields/{id}` | PUT | ✅ Có (nếu sân thuộc cluster của mình) |
| **Đổi trạng thái sân** | `/api/fields/{id}` | PUT | ✅ Có (`status`: available/maintenance/inactive) |
| **Xóa sân** | `/api/fields/{id}` | DELETE | ✅ Có (nếu sân thuộc cluster của mình) |

**Middleware:** `check.role:admin,owner`

#### 📝 Các trường có thể update:

```json
{
  "field_cluster_id": 1,        // ✅ Đổi sang cluster khác (của mình)
  "field_type": "5v5",          // ✅ Đổi loại sân: 5v5, 7v7, 11v11
  "status": "maintenance"       // ✅ Đổi trạng thái: available, maintenance, inactive
}
```

---

### 💰 QUẢN LÝ GIÁ THUÊ SÂN (Field Hourly Prices)

Owner có quyền quản lý giá thuê sân:

| Hành động | Endpoint | Method | Quyền |
|-----------|----------|--------|-------|
| Xem giá | `/api/field-hourly-prices` | GET | ✅ Có |
| Tạo giá mới | `/api/field-hourly-prices` | POST | ✅ Có |
| Cập nhật giá | `/api/field-hourly-prices/{id}` | PUT | ✅ Có |
| Xóa giá | `/api/field-hourly-prices/{id}` | DELETE | ✅ Có |

**Middleware:** `check.role:admin,owner`

---

### 👥 QUẢN LÝ NHÂN VIÊN (Staffs)

Owner có quyền quản lý nhân viên của mình:

| Hành động | Endpoint | Method | Quyền |
|-----------|----------|--------|-------|
| Xem danh sách nhân viên | `/api/staffs` | GET | ✅ Có |
| Thêm nhân viên | `/api/staffs` | POST | ✅ Có |
| Cập nhật nhân viên | `/api/staffs/{id}` | PUT | ✅ Có |
| Xóa nhân viên | `/api/staffs/{id}` | DELETE | ✅ Có |

**Middleware:** `check.role:admin,owner`

---

### 📅 XEM LỊCH ĐẶT SÂN (Bookings)

Owner có thể xem lịch đặt sân:

| Hành động | Endpoint | Method | Quyền |
|-----------|----------|--------|-------|
| Xem tất cả bookings | `/api/books` | GET | ✅ Có (xem bookings trên sân của mình) |
| Xem chi tiết booking | `/api/books/{id}` | GET | ✅ Có |

---

### 🏆 GIẢI ĐẤU (Tournaments)

Owner có thể tổ chức giải đấu:

| Hành động | Endpoint | Method | Quyền |
|-----------|----------|--------|-------|
| Tạo giải đấu | `/api/tournaments` | POST | ✅ Có |
| Cập nhật giải đấu | `/api/tournaments/{id}` | PUT | ✅ Có (nếu là người tạo) |
| Xóa giải đấu | `/api/tournaments/{id}` | DELETE | ✅ Có (nếu là người tạo) |
| Đặt sân cho giải | `/api/tournaments/{id}/book-fields` | POST | ✅ Có |

---

## ❌ NHỮNG GÌ OWNER KHÔNG ĐƯỢC LÀM

| Hành động | Lý do |
|-----------|-------|
| Quản lý users & roles | ❌ Chỉ admin |
| Duyệt đơn đăng ký owner | ❌ Chỉ admin |
| Xem/Sửa/Xóa **sân của owner khác** | ❌ Chỉ được quản lý sân của mình |
| Xem/Sửa/Xóa **cluster của owner khác** | ❌ Chỉ được quản lý cluster của mình |

---

## 🔍 TẠI SAO BỊ LỖI 403 KHI UPDATE FIELD?

### Nguyên nhân phổ biến:

#### 1️⃣ **Owner không có Owner Profile**

```json
{
  "success": false,
  "message": "Tài khoản owner chưa có owner profile",
  "errors": null
}
```

**Fix:**
```bash
php artisan db:seed --class=OwnerSeeder
```

---

#### 2️⃣ **Field thuộc về owner khác**

```json
{
  "success": false,
  "message": "Bạn không có quyền cập nhật sân con này. Sân này thuộc cụm sân của owner khác.",
  "errors": {
    "field_id": 5,
    "cluster_owner_id": 2,
    "your_owner_profile_id": 1
  }
}
```

**Giải thích:**
- `cluster_owner_id: 2` - Sân thuộc owner profile ID 2
- `your_owner_profile_id: 1` - Bạn là owner profile ID 1
- ❌ **KHÔNG KHỚP** → Không có quyền

**Fix:** Chỉ update fields thuộc clusters của bạn!

---

#### 3️⃣ **Frontend đang gọi sai field_id**

Frontend có thể đang cache hoặc hiển thị fields từ **public API** (không cần auth), dẫn đến hiển thị sân của owner khác.

**Check:**
- ❌ **SAI:** Gọi `GET /api/public/fields` (xem tất cả sân, không filter owner)
- ✅ **ĐÚNG:** Gọi `GET /api/fields` với JWT token (chỉ thấy sân của mình)

---

## 🧪 CÁCH KIỂM TRA QUYỀN

### Test 1: Chạy script debug

```bash
php debug-owner-update.php
```

Script sẽ hỏi email owner và hiển thị:
- ✅/❌ Owner có profile không
- ✅/❌ Owner có clusters không
- ✅/❌ Clusters có fields không
- ✅/❌ Permission check cho từng field

### Test 2: Kiểm tra qua API

```bash
# 1. Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"owner1@sanbongda.com","password":"owner123"}'

# Lưu token từ response

# 2. Xem fields của mình
curl http://localhost:8000/api/fields \
  -H "Authorization: Bearer YOUR_TOKEN"

# 3. Update field status
curl -X PUT http://localhost:8000/api/fields/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"maintenance"}'
```

---

## 🎯 LOGIC KIỂM TRA QUYỀN

```php
// Trong FieldController::update()

// Bước 1: Lấy field
$field = Field::find($id);

// Bước 2: Lấy cluster của field
$currentCluster = FieldCluster::find($field->field_cluster_id);

// Bước 3: Kiểm tra owner
if ($currentUser->role->name_role === 'owner') {
    
    // Check 1: Owner có profile không?
    if (!$currentUser->ownerProfile) {
        return 403; // ❌ Không có owner profile
    }
    
    // Check 2: Cluster có tồn tại không?
    if (!$currentCluster) {
        return 404; // ❌ Cluster không tồn tại
    }
    
    // Check 3: Cluster có thuộc owner không?
    if ($currentCluster->owner_id !== $currentUser->ownerProfile->id) {
        return 403; // ❌ Field thuộc owner khác
    }
}

// ✅ Passed all checks → Có quyền update
```

---

## 📊 VÍ DỤ CỤ THỂ

### Scenario 1: Owner1 update field của mình ✅

```
Owner1:
├─ email: owner1@sanbongda.com
├─ ownerProfile->id: 1
├─ Cluster ID 1 (owner_id: 1)
    ├─ Field ID 1 ✅ (có quyền)
    ├─ Field ID 2 ✅ (có quyền)
    └─ Field ID 3 ✅ (có quyền)
```

```bash
PUT /api/fields/1
{
  "status": "maintenance"  # ✅ SUCCESS 200
}
```

---

### Scenario 2: Owner1 cố update field của Owner2 ❌

```
Owner1 (ownerProfile: 1) cố update Field ID 10

Field ID 10:
├─ field_cluster_id: 2
└─ Cluster ID 2:
    └─ owner_id: 2  ❌ (khác ownerProfile 1)
```

```bash
PUT /api/fields/10
{
  "status": "maintenance"
}

# Response: 403 Forbidden
{
  "success": false,
  "message": "Bạn không có quyền cập nhật sân con này...",
  "errors": {
    "cluster_owner_id": 2,
    "your_owner_profile_id": 1
  }
}
```

---

## ✅ GIẢI PHÁP CUỐI CÙNG

### Nếu bạn BỊ LỖI 403 khi update field status:

1. **Chạy script debug:**
   ```bash
   php debug-owner-update.php
   ```

2. **Kiểm tra kết quả:**
   - Nếu "Owner không có ownerProfile" → Chạy `php artisan db:seed --class=OwnerSeeder`
   - Nếu "Cluster->owner_id không match" → Field không thuộc về bạn!
   - Nếu "Field thuộc owner khác" → Frontend đang gọi sai field_id

3. **Fix data nếu cần:**
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Verify lại:**
   - Login với owner email
   - Gọi `GET /api/fields` (authenticated)
   - Chỉ update fields trong response
   - **KHÔNG** update fields từ `GET /api/public/fields`

---

## 📞 LIÊN HỆ DEBUG

Nếu vẫn lỗi sau khi làm theo hướng dẫn, chạy:

```bash
php debug-owner-update.php > debug-result.txt
```

Gửi file `debug-result.txt` để được hỗ trợ chi tiết.
