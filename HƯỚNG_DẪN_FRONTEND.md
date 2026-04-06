# 📘 HƯỚNG DẪN FRONTEND - API CHECK AVAILABILITY VỚI BOOKINGS CHI TIẾT

## 🎯 API ENDPOINT

```
GET /api/public/field-clusters/{cluster_id}/availability-with-bookings?date=2026-04-25&field_type=5v5
```

**Parameters:**
- `cluster_id` (required): ID cụm sân
- `date` (required): Ngày cần check (format: YYYY-MM-DD)
- `field_type` (optional): Loại sân (5v5, 7v7, 11v11)

---

## 📤 REQUEST EXAMPLE

```javascript
const checkAvailability = async (clusterId, date, fieldType) => {
  const response = await fetch(
    `/api/public/field-clusters/${clusterId}/availability-with-bookings?date=${date}&field_type=${fieldType}`
  );
  const data = await response.json();
  return data;
};

// Usage
const result = await checkAvailability(1, '2026-04-25', '5v5');
```

---

## 📥 RESPONSE STRUCTURE

```json
{
  "success": true,
  "message": "Thông tin khả dụng các sân (kèm bookings)",
  "data": {
    "field_cluster": {
      "id": 1,
      "name": "Cụm Sân ABC",
      "address": "123 Đường XYZ"
    },
    "date": "2026-04-25",
    "day_type": "weekday",
    "fields_availability": [
      {
        "field": {
          "id": 1,
          "name": "Sân 5v5 (1)",
          "field_type": "5v5",
          "status": "available"
        },
        "date": "2026-04-25",
        "day_type": "weekday",
        "sessions": {
          "morning": {
            "available": false,
            "time": "06:00:00-11:00:00",
            "price": null,
            "reason": "Đã có 2 lịch đặt trong khung giờ này",
            "existing_bookings": [
              {
                "id": 123,
                "user": {
                  "id": 5,
                  "name": "Nguyễn Văn A",
                  "email": "nguyenvana@gmail.com"
                },
                "booking_type": "regular",
                "tournament": null,
                "start_time": "06:00:00",
                "end_time": "08:00:00",
                "total_price": 300000
              },
              {
                "id": 124,
                "user": {
                  "id": 8,
                  "name": "Trần Văn B",
                  "email": "tranvanb@gmail.com"
                },
                "booking_type": "tournament",
                "tournament": {
                  "id": 5,
                  "name": "Giải Bóng Đá Mùa Xuân"
                },
                "start_time": "09:00:00",
                "end_time": "11:00:00",
                "total_price": 500000
              }
            ]
          },
          "afternoon": {
            "available": true,
            "time": "13:00:00-17:00:00",
            "price": 600000,
            "reason": null,
            "existing_bookings": []
          },
          "evening": {
            "available": true,
            "time": "18:00:00-22:00:00",
            "price": 700000,
            "reason": null,
            "existing_bookings": []
          },
          "full_day": {
            "available": false,
            "time": "06:00:00-22:00:00",
            "price": 1500000,
            "reason": "Đã có 2 lịch đặt trong khung giờ này",
            "existing_bookings": [...]
          }
        }
      }
    ]
  }
}
```

---

## 🎨 UI IMPLEMENTATION (React/Next.js)

### Component: FieldAvailabilityChecker

```jsx
import { useState, useEffect } from 'react';

function FieldAvailabilityChecker({ clusterId, selectedDate, fieldType }) {
  const [availability, setAvailability] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (clusterId && selectedDate) {
      fetchAvailability();
    }
  }, [clusterId, selectedDate, fieldType]);

  const fetchAvailability = async () => {
    setLoading(true);
    try {
      const url = `/api/public/field-clusters/${clusterId}/availability-with-bookings?date=${selectedDate}&field_type=${fieldType}`;
      const response = await fetch(url);
      const data = await response.json();
      
      if (data.success) {
        setAvailability(data.data);
      }
    } catch (error) {
      console.error('Error fetching availability:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Đang kiểm tra khả dụng...</div>;
  if (!availability) return null;

  return (
    <div className="availability-container">
      <h2>{availability.field_cluster.name}</h2>
      <p>Ngày: {availability.date} ({availability.day_type === 'weekend' ? 'Cuối tuần' : 'Ngày thường'})</p>

      {availability.fields_availability.map(fieldAvail => (
        <FieldCard key={fieldAvail.field.id} fieldAvail={fieldAvail} />
      ))}
    </div>
  );
}

function FieldCard({ fieldAvail }) {
  const [expandedSession, setExpandedSession] = useState(null);

  const getSessionName = (session) => {
    const names = {
      morning: '🌅 Sáng',
      afternoon: '☀️ Chiều',
      evening: '🌙 Tối',
      full_day: '📅 Cả ngày'
    };
    return names[session] || session;
  };

  return (
    <div className="field-card">
      <h3>{fieldAvail.field.name}</h3>

      <div className="sessions-grid">
        {Object.entries(fieldAvail.sessions).map(([sessionType, info]) => (
          <div key={sessionType} className="session-slot">
            <button
              className={`session-btn ${info.available ? 'available' : 'booked'}`}
              onClick={() => setExpandedSession(expandedSession === sessionType ? null : sessionType)}
              disabled={!info.available}
            >
              <div className="session-header">
                <span className="session-name">{getSessionName(sessionType)}</span>
                <span className="session-time">{info.time}</span>
              </div>

              {info.available ? (
                <span className="price">✅ {info.price?.toLocaleString()}đ</span>
              ) : (
                <span className="status">🔒 {info.reason}</span>
              )}
            </button>

            {/* Hiển thị chi tiết bookings */}
            {expandedSession === sessionType && info.existing_bookings.length > 0 && (
              <div className="existing-bookings">
                <h4>Lịch đặt hiện có:</h4>
                {info.existing_bookings.map(booking => (
                  <BookingDetail key={booking.id} booking={booking} />
                ))}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}

function BookingDetail({ booking }) {
  return (
    <div className="booking-detail">
      <div className="booking-header">
        <span className={`booking-type ${booking.booking_type}`}>
          {booking.booking_type === 'tournament' ? '🏆 Giải đấu' : '⚽ Đặt thường'}
        </span>
        <span className="booking-time">
          {booking.start_time.slice(0, 5)} - {booking.end_time.slice(0, 5)}
        </span>
      </div>

      {booking.tournament && (
        <p className="tournament-name">🏆 {booking.tournament.name}</p>
      )}

      <p className="user-info">👤 {booking.user.name}</p>
      <p className="price">💰 {booking.total_price.toLocaleString()}đ</p>
    </div>
  );
}

export default FieldAvailabilityChecker;
```

---

## 🎨 CSS STYLING

```css
.availability-container {
  padding: 20px;
}

.field-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.sessions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 15px;
  margin-top: 15px;
}

.session-slot {
  position: relative;
}

.session-btn {
  width: 100%;
  padding: 15px;
  border-radius: 8px;
  border: 2px solid #ddd;
  cursor: pointer;
  transition: all 0.3s;
}

.session-btn.available {
  background: #e8f5e9;
  border-color: #4caf50;
}

.session-btn.available:hover {
  background: #c8e6c9;
  transform: translateY(-2px);
}

.session-btn.booked {
  background: #ffebee;
  border-color: #f44336;
  cursor: not-allowed;
  opacity: 0.7;
}

.session-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.session-name {
  font-weight: bold;
  font-size: 16px;
}

.session-time {
  color: #666;
  font-size: 14px;
}

.price {
  color: #4caf50;
  font-weight: bold;
}

.status {
  color: #f44336;
  font-size: 14px;
}

.existing-bookings {
  margin-top: 10px;
  padding: 15px;
  background: #fff3e0;
  border-radius: 8px;
  border-left: 4px solid #ff9800;
}

.existing-bookings h4 {
  margin: 0 0 10px 0;
  color: #e65100;
}

.booking-detail {
  background: white;
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 10px;
  border: 1px solid #ffe0b2;
}

.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.booking-type {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.booking-type.tournament {
  background: #e3f2fd;
  color: #1976d2;
}

.booking-type.regular {
  background: #f3e5f5;
  color: #7b1fa2;
}

.tournament-name {
  color: #1976d2;
  font-weight: 600;
  margin: 5px 0;
}

.user-info {
  color: #666;
  font-size: 14px;
  margin: 5px 0;
}
```

---

## 📊 FLOW HOÀN CHỈNH

```
1. User chọn cụm sân → Set clusterId
2. User chọn ngày → Call API availability
3. API trả về availability + existing bookings
4. Frontend hiển thị:
   - ✅ Sân available → Button xanh, có giá
   - 🔒 Sân đã đặt → Button đỏ, disabled
5. User click vào sân đã đặt → Expand chi tiết bookings
6. User chọn sân available → Add to selected bookings
7. User submit form → POST /api/tournaments với bookings array
```

---

## ✅ TÍNH NĂNG

✅ **Real-time check** - Kiểm tra booking thực tế  
✅ **Chi tiết booking** - Biết ai đã đặt, giờ nào  
✅ **Phân biệt loại** - Tournament vs Regular booking  
✅ **Giá theo buổi** - Weekday/weekend pricing  
✅ **UX tốt** - Click để xem chi tiết conflict  

---

## 🚀 READY TO USE

API đã sẵn sàng sau khi thêm code vào backend!
Frontend chỉ việc implement UI theo hướng dẫn trên.
