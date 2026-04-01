# 🔍 DEBUG: Frontend không xem được giải đấu

## ✅ Backend đã kiểm tra - HOẠT ĐỘNG HOÀN TOÀN BÌNH THƯỜNG

### Test 1: Public Endpoint (không cần token) ✅
```bash
curl -X GET "http://127.0.0.1:8000/api/public/tournaments" \
  -H "Accept: application/json" \
  -H "Origin: http://localhost:8080"

# Kết quả: HTTP 200 OK
{
  "success": true,
  "message": "Danh sách giải đấu công khai",
  "data": { ... tournaments ... }
}
```

### Test 2: Protected Endpoint (cần token) ✅
```bash
curl -X GET "http://127.0.0.1:8000/api/tournaments" \
  -H "Accept: application/json"

# Kết quả: HTTP 401 Unauthorized (đúng như mong đợi)
{
  "success": false,
  "message": "Token không hợp lệ"
}
```

### Test 3: CORS Headers ✅
- Config: `'allowed_origins' => ['*']`
- Tested với Origin: `http://localhost:8080` 
- Kết quả: PASS

---

## ❌ VẤN ĐỀ: Frontend đang gọi SAI ENDPOINT

Từ screenshot lỗi: `Error fetching tournaments: AxiosError { code: "ERR_BAD_REQUEST" }`

### 🔴 Frontend đang làm SAI:
```javascript
// ❌ SAI - Endpoint này yêu cầu authentication
axios.get('http://api-url/api/tournaments')

// Lỗi: 401 Unauthorized
```

### ✅ Frontend cần sửa:
```javascript
// ✅ ĐÚNG - Endpoint public (không cần token)
axios.get('http://api-url/api/public/tournaments')

// Trả về: 200 OK với danh sách giải đấu
```

---

## 📋 HƯỚNG DẪN CHO FRONTEND

### 1. XEM DANH SÁCH GIẢI ĐẤU (User chưa login)
```javascript
// File: tournamentService.ts hoặc api.js

// ✅ ĐÚNG
const getTournaments = async () => {
  const response = await axios.get(
    `${API_BASE_URL}/api/public/tournaments`,
    {
      headers: {
        'Accept': 'application/json'
      }
    }
  );
  return response.data;
};
```

### 2. XEM CHI TIẾT GIẢI (User chưa login)
```javascript
// ✅ ĐÚNG
const getTournamentDetail = async (id) => {
  const response = await axios.get(
    `${API_BASE_URL}/api/public/tournaments/${id}`,
    {
      headers: {
        'Accept': 'application/json'
      }
    }
  );
  return response.data;
};
```

### 3. ĐĂNG KÝ GIẢI ĐẤU (User đã login)
```javascript
// ✅ ĐÚNG - Endpoint này yêu cầu token
const registerTournament = async (tournamentId, teamId, token) => {
  const response = await axios.post(
    `${API_BASE_URL}/api/tournament-registrations`,
    {
      tournament_id: tournamentId,
      team_id: teamId
    },
    {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    }
  );
  return response.data;
};
```

---

## 🧪 TEST TRỰC TIẾP TỪ BROWSER

Mở file này để test API trực tiếp:
```
http://127.0.0.1:8000/test-api.html
```

---

## 📍 SO SÁNH CÁC ENDPOINTS

| Endpoint | Auth | Mục đích | Status khi không có token |
|----------|------|----------|---------------------------|
| `/api/public/tournaments` | ❌ Không | Xem giải (guest) | ✅ 200 OK |
| `/api/tournaments` | ✅ Có | Xem giải (theo quyền) | ❌ 401 Unauthorized |
| `/api/tournament-registrations` | ✅ Có | Đăng ký giải | ❌ 401 Unauthorized |

---

## 🔧 CHECKLIST CHO FRONTEND DEV

- [ ] Đổi URL từ `/api/tournaments` → `/api/public/tournaments`
- [ ] Kiểm tra `API_BASE_URL` có đúng không (nên là `http://127.0.0.1:8000`)
- [ ] Xóa header `Authorization` khi gọi public endpoints
- [ ] Test lại bằng Postman/Insomnia để confirm
- [ ] Check network tab trong DevTools xem request URL thực tế là gì

---

## 💡 MẸO DEBUG

### Kiểm tra URL thực tế Frontend đang gọi:
1. Mở DevTools (F12)
2. Tab Network
3. Reload trang
4. Tìm request `tournaments`
5. Xem URL đầy đủ là gì?
   - Nếu là `/api/tournaments` → SAI, phải sửa
   - Nếu là `/api/public/tournaments` → ĐÚNG
6. Xem Response → Nếu 401 thì chắc chắn đang gọi sai endpoint

---

## 📞 KẾT LUẬN

**Backend: ✅ KHÔNG CÓ LỖI**
- Public routes hoạt động hoàn hảo
- Protected routes hoạt động đúng (trả 401 khi không có token)
- CORS đã config đúng

**Frontend: ⚠️ CẦN SỬA**
- Đang gọi endpoint protected (`/api/tournaments`) thay vì public (`/api/public/tournaments`)
- Solution: Đổi URL trong code FE
