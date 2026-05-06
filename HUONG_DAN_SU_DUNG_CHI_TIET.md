# 🎯 HỆ THỐNG QUẢN LÝ SÂN BÓNG ĐÁ & GIẢI ĐẤU - HƯỚNG DẪN CHI TIẾT

## 📌 MỤC ĐÍCH PROJECT

Project này là một **HỆ THỐNG BACKEND API** hoàn chỉnh để quản lý:
1. **Cho thuê sân bóng đá** (giống như GoWabi, Mioto nhưng cho sân bóng)
2. **Tổ chức và quản lý giải đấu bóng đá** (giống như hệ thống quản lý giải nghiệp dư)

---

## 👥 CÁC NHÓM NGƯỜI DÙNG

### 1️⃣ **ADMIN (Quản trị viên hệ thống)**
**Ai là admin?** Người quản lý toàn bộ hệ thống (như CEO của công ty)

**Làm được gì?**
- ✅ Xem tất cả dữ liệu trong hệ thống
- ✅ Phê duyệt/Từ chối đơn đăng ký làm chủ sân
- ✅ Quản lý tất cả người dùng (xem, chỉnh sửa, khóa tài khoản)
- ✅ Tổ chức giải đấu lớn (là ban tổ chức chính thức)
- ✅ Xem tất cả booking trong hệ thống
- ✅ Xem thống kê doanh thu, hoạt động

**Ví dụ thực tế:**
- Có người muốn đăng ký làm chủ sân → Admin kiểm tra giấy tờ → Phê duyệt/Từ chối
- Tổ chức giải đấu cấp thành phố/quốc gia
- Xử lý khiếu nại, tranh chấp

---

### 2️⃣ **OWNER (Chủ sân bóng)**
**Ai là owner?** Người sở hữu cụm sân bóng (có thể 1 hoặc nhiều sân)

**Làm được gì?**
- ✅ Đăng ký làm chủ sân (cần admin phê duyệt)
- ✅ Quản lý thông tin cụm sân của mình (tên, địa chỉ, giờ mở/đóng cửa)
- ✅ Quản lý các sân bóng (thêm/sửa/xóa sân 5v5, 7v7, 11v11)
- ✅ Thiết lập giá thuê sân theo:
  - Loại sân (5v5, 7v7, 11v11)
  - Khung giờ (sáng, chiều, tối)
  - Ngày thường/cuối tuần
- ✅ Xem danh sách booking của sân mình
- ✅ Xác nhận/Hủy booking
- ✅ Quản lý nhân viên (staff)
- ✅ Tổ chức giải đấu tại sân của mình
- ✅ Thiết lập giá thuê sân cho giải đấu
- ✅ Xem doanh thu, thống kê booking

**Ví dụ thực tế:**
- Bạn có cụm sân "Sân Bóng Quận 1" với 3 sân 5v5, 2 sân 7v7
- Đặt giá: Sân 5v5 buổi tối 500k/giờ, cuối tuần 700k/giờ
- Khách đặt sân → Bạn xem và xác nhận
- Tổ chức giải đấu giao hữu tại sân của mình

---

### 3️⃣ **STAFF (Nhân viên sân)**
**Ai là staff?** Nhân viên được chủ sân thuê để quản lý hàng ngày

**Làm được gì?**
- ✅ Xem danh sách booking của cụm sân mình phụ trách
- ✅ Xác nhận khách đã đến sân (check-in)
- ✅ Hỗ trợ khách hàng tại sân
- ✅ Cập nhật trạng thái sân (bảo trì, không khả dụng)

**Ví dụ thực tế:**
- Khách đặt sân online → Staff check danh sách → Cho khách vào sân
- Sân bị hỏng → Staff đánh dấu sân không khả dụng tạm thời

---

### 4️⃣ **MEMBER (Người dùng/Cầu thủ)**
**Ai là member?** Người chơi bóng đá bình thường, muốn đặt sân hoặc tham gia giải

**Làm được gì?**

#### A. ĐẶT SÂN THÔNG THƯỜNG
- ✅ Xem danh sách các cụm sân (như xem danh sách khách sạn)
- ✅ Xem chi tiết sân (loại sân, giá, hình ảnh, đánh giá)
- ✅ Xem lịch trống của sân theo ngày, giờ
- ✅ Đặt sân theo giờ (ví dụ: Sân 5v5, ngày 10/05, 18:00-20:00)
- ✅ Thanh toán online hoặc tiền mặt
- ✅ Xem lịch sử đặt sân của mình
- ✅ Hủy booking (nếu còn thời gian)
- ✅ Nhận thông báo xác nhận/nhắc lịch

#### B. THAM GIA GIẢI ĐẤU
- ✅ Tạo đội bóng (làm đội trưởng)
- ✅ Thêm thành viên vào đội (CCCD, họ tên, vị trí, số áo)
- ✅ Xem danh sách giải đấu đang mở đăng ký
- ✅ Đăng ký đội vào giải
- ✅ Thanh toán phí tham gia giải
- ✅ Xem lịch thi đấu của đội
- ✅ Xem bảng xếp hạng
- ✅ Xem kết quả các trận đấu
- ✅ Xem bracket/sơ đồ vòng loại trực tiếp

**Ví dụ thực tế:**
- **Đặt sân thường:**
  - Chủ nhật này muốn chơi bóng → Vào app xem sân nào trống
  - Chọn "Sân Bóng Quận 1", Sân 5v5, 18:00-20:00
  - Thanh toán 500k → Nhận xác nhận → Đến sân chơi

- **Tham gia giải:**
  - Tạo đội "FC Pro" với 6 người
  - Đăng ký giải "Giải Mùa Xuân 2026" (5v5, 4 đội)
  - Nộp phí 2 triệu
  - Admin xếp lịch → Đội chơi theo lịch
  - Xem kết quả, bảng xếp hạng realtime

---

## 🔄 QUY TRÌNH HOẠT ĐỘNG CHI TIẾT

### 📍 QUY TRÌNH 1: ĐĂNG KÝ LÀM CHỦ SÂN

```
1. Owner điền form đăng ký:
   - Tên doanh nghiệp: "Cụm Sân ABC"
   - Địa chỉ: "123 Nguyễn Văn Linh, Q7"
   - CCCD/CMND
   - Giấy phép kinh doanh
   
2. Hệ thống gửi đơn đến Admin

3. Admin kiểm tra:
   - Giấy tờ hợp lệ? ✅
   - Địa chỉ thật? ✅
   - Phê duyệt ✅

4. Owner nhận email "Đơn được phê duyệt"

5. Owner đăng nhập → Thêm thông tin sân:
   - Sân 1: Sân 5v5, tên "Sân 1"
   - Sân 2: Sân 7v7, tên "Sân 2"
   - Giá: Sân 5v5 - 500k/giờ (tối), 400k/giờ (sáng)

6. Sân xuất hiện trong danh sách công khai
```

---

### 📍 QUY TRÌNH 2: ĐẶT SÂN THÔNG THƯỜNG

```
1. Member mở app → "Tìm sân"
   
2. Chọn:
   - Ngày: 10/05/2026
   - Loại sân: 5v5
   - Khu vực: Quận 7
   
3. Hệ thống hiển thị:
   - Cụm Sân ABC: 500k/giờ (18:00-20:00) ✅ Còn trống
   - Cụm Sân XYZ: 450k/giờ (18:00-20:00) ✅ Còn trống
   
4. Member chọn "Cụm Sân ABC" → "Đặt sân"

5. Điền thông tin:
   - Số điện thoại: 0909123456
   - Ghi chú: "Đá giao hữu"
   
6. Chọn thanh toán:
   - Online (chuyển khoản) hoặc
   - Tiền mặt (trả tại sân)
   
7. Nếu chọn online:
   - Quét mã QR
   - Chuyển khoản 500k
   - Upload ảnh chuyển khoản
   
8. Owner/Staff xác nhận thanh toán

9. Member nhận thông báo: "Đặt sân thành công"

10. Ngày 10/05, 17:45:
    - Member nhận nhắc lịch
    - Đến sân → Staff check-in → Chơi bóng
    
11. Sau khi chơi xong:
    - Booking chuyển trạng thái "Hoàn thành"
    - Member có thể đánh giá sân (5 sao)
```

---

### 📍 QUY TRÌNH 3: TỔ CHỨC GIẢI ĐẤU

```
BƯỚC 1: TẠO GIẢI (By Admin hoặc Owner)
=====================================
Admin tạo giải mới:
- Tên: "Giải Mùa Xuân 2026"
- Format: Đá vòng tròn (Round Robin)
- Loại sân: 5v5
- Số đội: Tối đa 4 đội, tối thiểu 4 đội
- Thời gian đăng ký: 01/05 - 05/05
- Thời gian thi đấu: 10/05 - 15/05
- Địa điểm: Cụm Sân ABC
- Phí tham gia: 2 triệu/đội
- Phương thức thanh toán: Chuyển khoản/Tiền mặt

BƯỚC 2: ĐĂNG KÝ THAM GIA (By Members)
======================================
Member A (Đội trưởng 1):
1. Tạo đội "FC Pro":
   - Đội trưởng: Nguyễn Văn A (tự động)
   - Thêm 5 thành viên:
     * Trần Văn B - CCCD: 001234567890 - Thủ môn - Số áo: 1
     * Lê Văn C - CCCD: 001234567891 - Hậu vệ - Số áo: 2
     * Phạm Văn D - CCCD: 001234567892 - Tiền vệ - Số áo: 3
     * ...
     
2. Đăng ký vào "Giải Mùa Xuân 2026"
   - Chọn đội: FC Pro
   - Upload giấy tờ đội (nếu cần)
   
3. Thanh toán phí 2 triệu
   - Quét QR → Chuyển khoản
   - Upload ảnh
   
4. Chờ admin phê duyệt

Tương tự: Member B, C, D tạo đội và đăng ký
→ Có 4 đội: FC Pro, FC Star, FC Dream, FC United

BƯỚC 3: PHÊ DUYỆT & XẾP LỊCH (By Admin)
========================================
Admin kiểm tra từng đội:
- FC Pro: Đủ thành viên ✅, Thanh toán ✅ → Phê duyệt
- FC Star: Đủ thành viên ✅, Thanh toán ✅ → Phê duyệt
- FC Dream: Đủ thành viên ✅, Thanh toán ✅ → Phê duyệt
- FC United: Đủ thành viên ✅, Thanh toán ✅ → Phê duyệt

Admin nhấn "Bắt đầu giải":
1. Hệ thống tự động phân bảng (nếu có vòng bảng)
2. Tạo lịch thi đấu:

   FORMAT VÒNG TRÒN (Round Robin):
   - 4 đội đá vòng tròn 1 lượt = 6 trận
   
   Lịch thi đấu:
   
   📅 Ngày 10/05 (Sáng):
   ⏰ 08:00 - Sân 1: FC Pro vs FC Star
   ⏰ 10:00 - Sân 1: FC Dream vs FC United
   
   📅 Ngày 10/05 (Chiều):
   ⏰ 14:00 - Sân 1: FC Pro vs FC Dream
   ⏰ 16:00 - Sân 1: FC Star vs FC United
   
   📅 Ngày 11/05 (Sáng):
   ⏰ 08:00 - Sân 1: FC Pro vs FC United
   ⏰ 10:00 - Sân 1: FC Star vs FC Dream

3. Hệ thống tự động book sân:
   - Book Sân 1, ngày 10/05, 08:00-10:00
   - Book Sân 1, ngày 10/05, 10:00-12:00
   - ...

4. Gửi thông báo lịch đấu cho tất cả đội

BƯỚC 4: THI ĐẤU & CẬP NHẬT KẾT QUẢ (By Admin/Staff)
===================================================
Trận 1: FC Pro vs FC Star (10/05, 08:00)
- 2 đội đến sân
- Thi đấu 40 phút
- Kết thúc: FC Pro 3-2 FC Star

Admin cập nhật kết quả:
- Nhập tỷ số: 3-2
- Nhập danh sách ghi bàn:
  * FC Pro: Trần Văn B (15'), Phạm Văn D (25', 38')
  * FC Star: Nguyễn Văn X (20'), Lê Văn Y (35')
- Nhập thẻ phạt (nếu có):
  * Lê Văn C (FC Pro): Thẻ vàng (30')

→ Hệ thống tự động cập nhật:
  - Bảng xếp hạng
  - Thống kê cá nhân (Trần Văn B: 1 bàn, Phạm Văn D: 2 bàn)

BƯỚC 5: XEM BXH REALTIME (By All Members)
=========================================
Sau trận 1, bảng xếp hạng:

| Hạng | Đội       | Trận | Thắng | Hòa | Bại | Bàn thắng | Bàn thua | Hiệu số | Điểm |
|------|-----------|------|-------|-----|-----|-----------|----------|---------|------|
| 1    | FC Pro    | 1    | 1     | 0   | 0   | 3         | 2        | +1      | 3    |
| 2    | FC Star   | 1    | 0     | 0   | 1   | 2         | 3        | -1      | 0    |
| 3    | FC Dream  | 0    | 0     | 0   | 0   | 0         | 0        | 0       | 0    |
| 4    | FC United | 0    | 0     | 0   | 0   | 0         | 0        | 0       | 0    |

Members có thể:
- Xem bảng xếp hạng trực tiếp
- Xem lịch sử các trận đã đấu
- Xem lịch thi đấu sắp tới
- Xem thống kê cá nhân (top ghi bàn, thẻ phạt)

BƯỚC 6: KẾT THÚC GIẢI (After all matches)
=========================================
Sau 6 trận, bảng xếp hạng cuối:

| Hạng | Đội       | Trận | Điểm |
|------|-----------|------|------|
| 🥇 1 | FC Pro    | 3    | 9    |
| 🥈 2 | FC Dream  | 3    | 6    |
| 🥉 3 | FC Star   | 3    | 3    |
| 4    | FC United | 3    | 0    |

Admin:
- Công bố kết quả chính thức
- Đóng giải
- Trao giải (ngoài hệ thống)

Members nhận:
- Thông báo kết thúc giải
- Giấy chứng nhận điện tử (nếu có)
- Thống kê chi tiết đội bóng
```

---

### 📍 QUY TRÌNH 4: THANH TOÁN

```
HỆ THỐNG HỖ TRỢ 2 PHƯƠNG THỨC:

1. CHUYỂN KHOẢN (Transfer):
   ===========================
   - Owner/Admin cung cấp mã QR hoặc STK
   - User quét mã → Chuyển khoản
   - Upload ảnh chuyển khoản
   - Owner/Admin xác nhận → Booking active

2. TIỀN MẶT (Cash):
   ===================
   - User đặt sân trước
   - Đến sân → Trả tiền mặt cho Staff
   - Staff xác nhận đã nhận tiền
   - Booking active
```

---

## 🎮 CÁC TÍNH NĂNG KỸ THUẬT

### 1. Quản lý Sân Thông Minh
```
- Check sân trống theo khung giờ
- Tự động khóa lịch khi có booking
- Không cho đặt trùng giờ
- Hỗ trợ đặt liên tục (2-3 tiếng)
```

### 2. Giá Linh Hoạt
```
Giá thuê sân tính theo:
- Loại sân (5v5 < 7v7 < 11v11)
- Khung giờ:
  * Sáng (6h-12h): 400k
  * Chiều (12h-18h): 450k  
  * Tối (18h-23h): 500k
- Ngày trong tuần vs cuối tuần
```

### 3. Tự Động Xếp Lịch Giải Đấu
```
Format Round Robin (Vòng tròn):
- 4 đội: 6 trận (đá 1 lượt)
- 8 đội: 28 trận (đá 1 lượt)
- Tự động phân chia ca đấu
- Tự động book sân

Format Knockout (Loại trực tiếp):
- 8 đội: Tứ kết (4 trận) → Bán kết (2 trận) → Chung kết (1 trận)
- Hệ thống tạo bracket tự động
```

### 4. Thống Kê Chi Tiết
```
Cho Owner:
- Doanh thu theo ngày/tháng
- Sân nào hot nhất
- Khung giờ nào đặt nhiều
- Tỷ lệ lấp đầy sân

Cho Member:
- Lịch sử đặt sân
- Số tiền đã chi
- Thống kê đội bóng (nếu có)

Cho Đội bóng:
- Số trận thắng/thua/hòa
- Tổng số bàn thắng/thua
- Top ghi bàn trong đội
- Thẻ vàng/đỏ
```

### 5. Thông Báo Realtime
```
- Booking được xác nhận
- Sắp đến giờ đá (nhắc trước 1 giờ)
- Có trận đấu mới trong giải
- Kết quả trận đấu được cập nhật
- Giải đấu mới mở đăng ký
- Đơn đăng ký được phê duyệt/từ chối
```

---

## 💡 VÍ DỤ THỰC TẾ TOÀN DIỆN

### Kịch bản: Nhóm bạn muốn tổ chức giải đá banh giao hữu

**Bối cảnh:**
- 4 nhóm bạn (mỗi nhóm 6 người) muốn tổ chức giải mini
- Giải 5v5, đá vòng tròn
- Tổ chức vào cuối tuần

**Cách thực hiện:**

```
TUẦN 1: CHUẨN BỊ
================
Ngày 1: 
- Bạn A (admin của nhóm) đăng ký tài khoản member
- Tạo đội "Team A" trên hệ thống
- Thêm 5 bạn vào đội (CCCD + ảnh)

- Bạn B, C, D làm tương tự
→ Có 4 đội: Team A, B, C, D

Ngày 2-3:
- Admin hệ thống tạo giải "Giải Giao Hữu 2026"
  * Loại: 5v5
  * Phí: 500k/đội
  * Thời gian: Thứ 7-CN tuần sau
  
- 4 đội trưởng đăng ký vào giải
- Chuyển khoản 500k phí tham gia
- Admin phê duyệt

Ngày 4:
- Admin nhấn "Bắt đầu giải"
- Hệ thống tạo lịch:
  
  LỊCH THI ĐẤU:
  
  Thứ 7 (Sáng):
  08:00: Team A vs Team B (Sân 1)
  10:00: Team C vs Team D (Sân 1)
  
  Thứ 7 (Chiều):
  14:00: Team A vs Team C (Sân 1)
  16:00: Team B vs Team D (Sân 1)
  
  Chủ nhật (Sáng):
  08:00: Team A vs Team D (Sân 1)
  10:00: Team B vs Team C (Sân 1)
  
- Tất cả nhận thông báo lịch đấu

TUẦN 2: THI ĐẤU
================
Thứ 7, 07:45:
- 12 người (Team A + Team B) đến sân
- Check-in với staff
- Khởi động

08:00:
- Trận 1 bắt đầu: Team A vs Team B
- Thi đấu 40 phút
- Kết thúc: Team A 4-3 Team B

08:45:
- Admin cập nhật kết quả ngay trên app
- Tất cả members xem được tỷ số realtime

10:00:
- Trận 2: Team C vs Team D
- Kết quả: Team C 2-1 Team D
- Admin cập nhật

→ Bảng xếp hạng sau 2 trận:
1. Team A: 3 điểm
2. Team C: 3 điểm  
3. Team B: 0 điểm
4. Team D: 0 điểm

... (Tiếp tục 4 trận còn lại)

Chủ nhật, 11:00 (Sau trận cuối):
→ BXH cuối cùng:
1. 🥇 Team A: 9 điểm (3 trận toàn thắng)
2. 🥈 Team C: 6 điểm  
3. 🥉 Team B: 3 điểm
4. Team D: 0 điểm

- Tất cả xem kết quả trên app
- Admin công bố chính thức
- Trao giải (cúp, huy chương) ngoài app
- Chụp ảnh kỷ niệm

SAU GIẢI:
=========
- Members xem lại:
  * Kết quả từng trận
  * Top ghi bàn: Nguyễn Văn A (Team A) - 5 bàn
  * Thẻ phạt
  * Video highlight (nếu quay)
  
- Owner nhận:
  * 4 đội × 500k = 2 triệu (phí tham gia)
  * Chi phí thuê sân: 6 trận × giá thuê
  * Lợi nhuận
```

---

## 📊 LỢI ÍCH CỦA HỆ THỐNG

### Cho Chủ Sân (Owner):
- ✅ Quản lý booking dễ dàng, không bị nhầm lịch
- ✅ Tăng doanh thu nhờ hiển thị công khai
- ✅ Tự động tính tiền, không lo sót
- ✅ Quản lý nhiều sân cùng lúc
- ✅ Thống kê doanh thu rõ ràng
- ✅ Tổ chức giải đấu thu thêm thu nhập

### Cho Người Chơi (Member):
- ✅ Đặt sân nhanh, không cần gọi điện
- ✅ So sánh giá nhiều sân
- ✅ Xem lịch trống realtime
- ✅ Thanh toán online tiện lợi
- ✅ Nhận nhắc lịch tự động
- ✅ Tham gia giải đấu dễ dàng
- ✅ Theo dõi thống kê đội bóng

### Cho Ban Tổ Chức Giải:
- ✅ Xếp lịch tự động, không bị trùng
- ✅ Quản lý đội, cầu thủ chặt chẽ
- ✅ Cập nhật kết quả nhanh
- ✅ Bảng xếp hạng tự động
- ✅ Giảm 90% công việc giấy tờ
- ✅ Minh bạch thanh toán

---

## 🔐 BẢO MẬT & KIỂM SOÁT

### 1. Phân Quyền Chặt Chẽ
```
- Admin: Full quyền
- Owner: Chỉ xem/sửa sân của mình
- Staff: Chỉ check-in booking
- Member: Chỉ xem/sửa dữ liệu của mình
```

### 2. Xác Thực JWT
```
- Mỗi request phải có token
- Token hết hạn sau 24h
- Refresh token để gia hạn
- Logout xóa token
```

### 3. Validation Dữ Liệu
```
- Không cho đặt sân quá khứ
- Không cho đặt trùng giờ
- Kiểm tra CCCD trùng trong giải
- Validate email, phone
```

### 4. Thanh Toán An Toàn
```
- Upload ảnh chuyển khoản
- Admin/Owner xác nhận thủ công
- Không lưu thông tin thẻ
```

---

## 🚀 MỞ RỘNG TRONG TƯƠNG LAI

### Có thể thêm:
1. **Livestream trận đấu**
2. **Chat giữa các đội**
3. **Đánh giá & Review sân**
4. **Thuê trọng tài qua app**
5. **Thuê quay phim**
6. **Mua đồ bóng đá (áo, giày)**
7. **Bảo hiểm khi chơi bóng**
8. **Tìm đối thủ giao hữu**
9. **AI phân tích kỹ thuật**
10. **Tích điểm thành viên**

---

## 📱 TÍCH HỢP MOBILE APP

Backend API này được thiết kế để:
- Frontend Web (React, Vue, Angular)
- Mobile App (React Native, Flutter)
- Desktop App (Electron)

Mọi nền tảng đều gọi API RESTful giống nhau.

---

## 📞 LIÊN HỆ & HỖ TRỢ

**Email Admin:** admin@test.com  
**Password Demo:** 123456

**Test Accounts:**
- Admin: admin@test.com / 123456
- Owner: owner@test.com / 123456
- Captain 1: captain1@test.com / 123456
- Captain 2: captain2@test.com / 123456

---

## 🎯 KẾT LUẬN

Hệ thống này giải quyết bài toán:
1. **Quản lý sân bóng hiệu quả** (như Airbnb cho sân bóng)
2. **Tổ chức giải đấu chuyên nghiệp** (như EPL nhưng cho giải nghiệp dư)
3. **Kết nối người chơi và chủ sân** (marketplace 2 chiều)

**Mục tiêu cuối cùng:**  
Làm cho việc đá bóng và tổ chức giải trở nên **DỄ DÀNG - NHANH CHÓNG - CHUYÊN NGHIỆP** như đặt Grab, đặt khách sạn trên Booking.com!

---

**Ngày cập nhật:** 05/05/2026  
**Version:** 1.0 - Chi tiết đầy đủ
