# 🔧 HƯỚNG DẪN FIX LỖI OWNER PERMISSIONS - 403 FORBIDDEN

## 🔍 VẤN ĐỀ

Khi đăng nhập bằng `owner1@sanbongda.com`, frontend hiển thị fields nhưng khi edit/delete nhận lỗi **403 Forbidden**:
```json
{"success":false,"message":"Bạn không có quyền chỉnh sửa sân con này","errors":null}
```

## 🎯 NGUYÊN NHÂN GỐC

1. **Bảng `fields` thiếu column `name`** - Migration đã tồn tại nhưng chưa được chạy
2. **FieldSeeder có lỗi cú pháp** - Tạo fields không đúng format
3. **Owner không có fields** - Do seeders chưa chạy đủ hoặc chạy sai thứ tự

## ✅ GIẢI PHÁP - CHẠY TỪNG BƯỚC

### Bước 1: Kiểm tra tình trạng hiện tại

```bash
php test-all-permissions.php
```

Script sẽ hiển thị:
- ✅/❌ Column 'name' có tồn tại không
- ✅/❌ Owner1 có clusters không
- ✅/❌ Owner1 có fields không
- Danh sách tất cả owners và fields của họ

### Bước 2: Chạy migration (nếu thiếu column 'name')

```bash
php artisan migrate
```

**Kết quả mong đợi:**
```
Migrating: 2026_03_28_090922_add_name_to_fields_table
Migrated:  2026_03_28_090922_add_name_to_fields_table
```

### Bước 3: Chạy FieldSeeder (tạo fields cho tất cả owners)

```bash
php artisan db:seed --class=FieldSeeder
```

**Kết quả mong đợi:**
```
Tạo fields cho 2 clusters...
  ✓ Cluster 'Cụm sân Thể Thao Hùng - Cơ sở 1': 4 sân
  ✓ Cluster 'Cụm sân Mai Sport - Cơ sở 1': 4 sân
✅ Tạo tổng 8 fields thành công!
Mỗi cluster có 4 sân: 2 sân 5v5, 1 sân 7v7, 1 sân 11v11
```

### Bước 4: Verify lại

```bash
php test-all-permissions.php
```

**Nếu thấy:**
```
✅✅✅ BACKEND HOÀN TOÀN OK - KHÔNG CÓ LỖI!
```

→ **Backend đã OK!**

## 🔍 NẾU VẪN LỖI 403 SAU KHI FIX

### Trường hợp 1: Backend OK nhưng FE vẫn lỗi

**Nguyên nhân:** Frontend đang gọi sai API hoặc cache sai data

**Kiểm tra:**
1. FE phải gọi `GET /api/fields` (authenticated) - KHÔNG phải `/api/public/fields`
2. FE phải truyền **JWT token** trong header: `Authorization: Bearer {token}`
3. FE đang hiển thị fields của owner khác (từ cache)

**Fix Frontend:**
```javascript
// SAI - Gọi public endpoint
fetch('/api/public/fields')

// ĐÚNG - Gọi authenticated endpoint
fetch('/api/fields', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
```

### Trường hợp 2: Owner không có fields

**Chạy lại từ đầu:**
```bash
# Reset toàn bộ database
php artisan migrate:fresh --seed

# Kiểm tra
php test-all-permissions.php
```

## 📊 CẤU TRÚC DATA SAU KHI FIX

```
Owner1 (owner1@sanbongda.com / owner123)
├── Owner Profile ID: 1
├── Cluster ID 1: "Cụm sân Thể Thao Hùng - Cơ sở 1"
│   ├── Field: Sân 5v5 (1) - available
│   ├── Field: Sân 5v5 (2) - available
│   ├── Field: Sân 7v7 (1) - available
│   └── Field: Sân 11v11 (1) - maintenance

Owner2 (owner2@sanbongda.com / owner123)
├── Owner Profile ID: 2
├── Cluster ID 2: "Cụm sân Mai Sport - Cơ sở 1"
│   ├── Field: Sân 5v5 (1) - available
│   ├── Field: Sân 5v5 (2) - available
│   ├── Field: Sân 7v7 (1) - available
│   └── Field: Sân 11v11 (1) - maintenance
```

## 🎯 QUYỀN HẠN ĐÚNG CỦA OWNER

✅ **Owner CHỈ được:**
- Xem/Tạo/Sửa/Xóa **fields thuộc clusters của mình**
- Kiểm tra bằng: `cluster->owner_id === user->ownerProfile->id`

❌ **Owner KHÔNG được:**
- Xem/Sửa/Xóa fields của owner khác
- Nếu cố làm → 403 Forbidden

## 📝 TÓM TẮT CÁC FILE ĐÃ SỬA

### 1. `app/Models/OwnerProfile.php`
✅ Thêm relationship `fieldClusters()`

### 2. `database/seeders/FieldSeeder.php`
✅ Tạo fields với column 'name' đúng cách
✅ Mỗi cluster: 2 sân 5v5, 1 sân 7v7, 1 sân 11v11

### 3. `database/seeders/TestDataSeeder.php`
✅ Không xóa fields của clusters khác
✅ Chỉ tạo nếu chưa có

### 4. `test-all-permissions.php`
✅ Script kiểm tra comprehensive
✅ Tự động phát hiện vấn đề
✅ Đưa ra hướng dẫn fix

## 🚀 LỆNH FIX NHANH (All-in-One)

```bash
# Chạy tất cả trong 1 lệnh
php artisan migrate && php artisan db:seed --class=FieldSeeder && php test-all-permissions.php
```

## ✅ KẾT QUẢ MONG ĐỢI

Sau khi fix:
1. ✅ Owner1 login thành công với `owner1@sanbongda.com / owner123`
2. ✅ GET `/api/fields` trả về 4 fields của owner1
3. ✅ PUT `/api/fields/{id}` → 200 OK (nếu field thuộc owner1)
4. ✅ DELETE `/api/fields/{id}` → 200 OK (nếu field thuộc owner1)
5. ❌ PUT `/api/fields/{other_owner_field}` → 403 Forbidden

---

**Ghi chú:** Nếu vẫn gặp vấn đề, chạy `php test-all-permissions.php` và gửi kết quả để debug tiếp.
