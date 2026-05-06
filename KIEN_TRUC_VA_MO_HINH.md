# 🏗️ KIẾN TRÚC & MÔ HÌNH THIẾT KẾ CỦA PROJECT

## 📐 TÓM TẮT NHANH

Project này được xây dựng theo:
1. **Kiến trúc chính:** MVC (Model-View-Controller)
2. **Phong cách API:** RESTful Architecture
3. **ORM Pattern:** Active Record (Eloquent)
4. **Authentication:** JWT Token-based Authentication
5. **Design Patterns:** Repository, Factory, Facade, Middleware, Observer...

---

## 1️⃣ KIẾN TRÚC MVC (Model-View-Controller)

### 📚 Định nghĩa:
MVC là mô hình kiến trúc chia ứng dụng thành 3 thành phần chính:
- **Model:** Quản lý dữ liệu và business logic
- **View:** Hiển thị dữ liệu cho người dùng
- **Controller:** Xử lý request và điều khiển luồng dữ liệu

### 🔍 Trong project này:

```
┌─────────────────────────────────────────────────────┐
│                   CLIENT (App/Web)                   │
│              (React, Flutter, Postman...)            │
└───────────────────┬─────────────────────────────────┘
                    │
                    │ HTTP Request (JSON)
                    │
                    ▼
┌─────────────────────────────────────────────────────┐
│                 ROUTER (routes/api.php)              │
│          - Nhận request                              │
│          - Kiểm tra authentication (JWT)             │
│          - Chuyển đến Controller tương ứng           │
└───────────────────┬─────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────┐
│          MIDDLEWARE (app/Http/Middleware/)           │
│          - JwtMiddleware: Xác thực JWT token         │
│          - CheckRole: Kiểm tra quyền (RBAC)          │
└───────────────────┬─────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────┐
│         CONTROLLER (app/Http/Controllers/Api/)       │
│                                                      │
│   BookController::index()                           │
│   {                                                  │
│       1. Nhận request từ client                     │
│       2. Validate dữ liệu đầu vào                   │
│       3. Gọi Model để lấy/xử lý dữ liệu             │
│       4. Format response (JSON)                     │
│       5. Trả về cho client                          │
│   }                                                  │
└───────────────────┬─────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────┐
│              MODEL (app/Models/)                     │
│                                                      │
│   Book::class                                       │
│   {                                                  │
│       - $fillable: Các field có thể gán             │
│       - $casts: Chuyển đổi kiểu dữ liệu             │
│       - relationships: belongsTo, hasMany...        │
│       - scopes: Query builders                      │
│       - accessors/mutators: Get/Set attributes      │
│   }                                                  │
└───────────────────┬─────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────┐
│           DATABASE (MySQL/SQLite)                    │
│                                                      │
│   Tables:                                           │
│   - books                                           │
│   - users                                           │
│   - tournaments                                     │
│   - ...                                             │
└─────────────────────────────────────────────────────┘
```

### 📂 Cấu trúc thư mục MVC trong Laravel:

```
app/
├── Models/                    ← MODEL
│   ├── User.php
│   ├── Book.php
│   ├── Tournament.php
│   └── ...
│
├── Http/
│   ├── Controllers/           ← CONTROLLER
│   │   └── Api/
│   │       ├── BookController.php
│   │       ├── TournamentController.php
│   │       └── ...
│   │
│   └── Middleware/            ← MIDDLEWARE (bổ sung)
│       ├── JwtMiddleware.php
│       └── CheckRole.php
│
resources/
└── views/                     ← VIEW (không dùng nhiều vì là API)
    └── emails/                ← Chỉ dùng view cho email
        ├── owner-approved.blade.php
        └── ...
```

---

## 2️⃣ MÔ HÌNH RESTFUL API ARCHITECTURE

### 📚 Định nghĩa:
REST (Representational State Transfer) là phong cách kiến trúc cho API:
- Sử dụng HTTP methods (GET, POST, PUT, DELETE)
- Stateless (không lưu trạng thái giữa các request)
- Resource-based URLs
- JSON response format

### 🔍 Ví dụ trong project:

```php
// routes/api.php

// Resource: Books (Đặt sân)
Route::get('/books', [BookController::class, 'index']);           // Lấy danh sách
Route::get('/books/{id}', [BookController::class, 'show']);       // Xem chi tiết
Route::post('/books', [BookController::class, 'store']);          // Tạo mới
Route::put('/books/{id}', [BookController::class, 'update']);     // Cập nhật
Route::delete('/books/{id}', [BookController::class, 'destroy']); // Xóa

// Resource: Tournaments (Giải đấu)
Route::get('/tournaments', [TournamentController::class, 'index']);
Route::post('/tournaments', [TournamentController::class, 'store']);
// ...
```

### 📊 RESTful Response Format:

```json
// Success Response
{
  "success": true,
  "message": "Đặt sân thành công",
  "data": {
    "id": 1,
    "field_id": 5,
    "day_book": "2026-05-10",
    "start_time": "18:00:00",
    "end_time": "20:00:00",
    "total_price": 500000
  },
  "meta": {
    "page": 1,
    "per_page": 10
  }
}

// Error Response
{
  "success": false,
  "message": "Sân đã được đặt trong khung giờ này",
  "errors": {
    "field_id": ["Sân không khả dụng"]
  }
}
```

---

## 3️⃣ ORM PATTERN: ACTIVE RECORD (ELOQUENT)

### 📚 Định nghĩa:
Active Record là pattern mà mỗi Model đại diện cho 1 bảng trong database và chứa cả business logic lẫn database operations.

### 🔍 Ví dụ cụ thể:

```php
// app/Models/Tournament.php

class Tournament extends Model
{
    // 1. TABLE MAPPING
    protected $table = 'tournaments'; // (optional, Laravel tự động map)
    
    // 2. MASS ASSIGNMENT PROTECTION
    protected $fillable = [
        'name',
        'description',
        'format',
        'status',
        // ...
    ];
    
    // 3. TYPE CASTING
    protected $casts = [
        'registration_start' => 'datetime',
        'start_date' => 'date',
        'schedule_published' => 'boolean',
    ];
    
    // 4. RELATIONSHIPS (Quan hệ giữa các bảng)
    
    // Một giải đấu thuộc về một ban tổ chức (User)
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
    
    // Một giải đấu được tổ chức tại một cụm sân
    public function fieldCluster(): BelongsTo
    {
        return $this->belongsTo(FieldCluster::class);
    }
    
    // Một giải đấu có nhiều đội đăng ký
    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class);
    }
    
    // Một giải đấu có nhiều trận đấu
    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }
    
    // 5. QUERY SCOPES (Truy vấn tái sử dụng)
    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }
    
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }
}
```

### 💡 Sử dụng trong Controller:

```php
// Thay vì viết SQL thuần:
$result = DB::select('SELECT * FROM tournaments WHERE status = ? AND organizer_id = ?', ['open', $userId]);

// Dùng Eloquent (Active Record):
$tournaments = Tournament::where('status', 'open')
    ->where('organizer_id', $userId)
    ->with(['organizer', 'fieldCluster']) // Eager loading
    ->get();

// Hoặc sử dụng scope:
$tournaments = Tournament::active()->upcoming()->get();
```

---

## 4️⃣ DESIGN PATTERNS ĐƯỢC SỬ DỤNG

### 🎯 Pattern 1: REPOSITORY PATTERN (qua Eloquent)

**Mục đích:** Tách biệt logic truy vấn database khỏi business logic

```php
// Eloquent Model hoạt động như Repository

class BookController extends Controller
{
    // Repository: Book Model
    public function index()
    {
        // Query qua repository (Eloquent)
        $books = Book::with(['user', 'field'])
            ->where('status', 'confirmed')
            ->orderBy('day_book', 'desc')
            ->get();
            
        return ApiResponse::success('Danh sách booking', $books);
    }
}
```

---

### 🎯 Pattern 2: FACADE PATTERN

**Mục đích:** Cung cấp interface đơn giản cho hệ thống phức tạp

```php
// Laravel Facades - Interface đơn giản cho các service phức tạp

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// Thay vì:
$hasher = app('hash');
$hasher->make('password');

// Dùng Facade:
Hash::make('password'); // Đơn giản hơn nhiều!

// Ví dụ khác:
Auth::user();
DB::table('users')->get();
Mail::to($user)->send(new WelcomeEmail());
```

---

### 🎯 Pattern 3: MIDDLEWARE PATTERN (Chain of Responsibility)

**Mục đích:** Xử lý request qua chuỗi các middleware trước khi đến controller

```php
// app/Http/Middleware/JwtMiddleware.php

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        // 1. Kiểm tra token
        if (!$token = $request->bearerToken()) {
            return response()->json(['error' => 'Token not provided'], 401);
        }
        
        // 2. Validate token
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
        
        // 3. Cho phép tiếp tục
        return $next($request);
    }
}

// app/Http/Middleware/CheckRole.php

class CheckRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();
        
        if (!in_array($user->role->name_role, $roles)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return $next($request);
    }
}

// Sử dụng trong routes:
Route::middleware(['jwt.auth', 'role:admin,owner'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
});
```

**Flow:**
```
Request 
  → JwtMiddleware (kiểm tra token)
  → CheckRole (kiểm tra quyền)
  → Controller (xử lý logic)
  → Response
```

---

### 🎯 Pattern 4: FACTORY PATTERN

**Mục đích:** Tạo objects mà không cần chỉ định class cụ thể

```php
// database/factories/UserFactory.php

class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role_id' => 4, // member
        ];
    }
}

// Sử dụng:
$users = User::factory()->count(50)->create(); // Tạo 50 users test
```

---

### 🎯 Pattern 5: OBSERVER PATTERN (Events & Listeners)

**Mục đích:** Khi một sự kiện xảy ra, tự động thực hiện các hành động liên quan

```php
// Ví dụ: Khi tạo booking mới → Gửi notification

// app/Models/Book.php
class Book extends Model
{
    protected static function booted()
    {
        static::created(function ($book) {
            // Khi booking được tạo
            Notification::create([
                'user_id' => $book->user_id,
                'type' => 'booking_confirmed',
                'title' => 'Đặt sân thành công',
                'message' => "Bạn đã đặt sân vào {$book->day_book}",
            ]);
        });
        
        static::updated(function ($book) {
            // Khi booking được cập nhật
            if ($book->isDirty('status') && $book->status === 'cancelled') {
                Notification::create([
                    'user_id' => $book->user_id,
                    'type' => 'booking_cancelled',
                    'title' => 'Booking bị hủy',
                    'message' => "Booking của bạn đã bị hủy",
                ]);
            }
        });
    }
}
```

---

### 🎯 Pattern 6: STRATEGY PATTERN

**Mục đích:** Chọn thuật toán/chiến lược khác nhau dựa trên tình huống

```php
// Ví dụ: Tính giá booking khác nhau theo loại ngày

class PriceCalculator
{
    public function calculate($fieldType, $dayType, $sessionType)
    {
        // Strategy dựa trên day_type
        if ($dayType === 'weekend') {
            return $this->getWeekendPrice($fieldType, $sessionType);
        } else {
            return $this->getWeekdayPrice($fieldType, $sessionType);
        }
    }
    
    private function getWeekendPrice($fieldType, $sessionType)
    {
        // Logic tính giá cuối tuần
        return FieldHourlyPrice::where([
            'field_type' => $fieldType,
            'day_type' => 'weekend',
            'session_type' => $sessionType,
        ])->first()->price;
    }
    
    private function getWeekdayPrice($fieldType, $sessionType)
    {
        // Logic tính giá ngày thường
        return FieldHourlyPrice::where([
            'field_type' => $fieldType,
            'day_type' => 'weekday',
            'session_type' => $sessionType,
        ])->first()->price;
    }
}
```

---

### 🎯 Pattern 7: DEPENDENCY INJECTION (DI)

**Mục đích:** Inject dependencies vào class thay vì tạo mới trong class

```php
// Laravel Service Container tự động inject

class BookController extends Controller
{
    // Không cần:
    // private $validator;
    // public function __construct() {
    //     $this->validator = new Validator();
    // }
    
    // Laravel tự động inject Request
    public function store(Request $request)
    {
        // $request đã được inject sẵn
        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'day_book' => 'required|date',
        ]);
        
        // ...
    }
}
```

---

### 🎯 Pattern 8: SINGLETON PATTERN

**Mục đích:** Đảm bảo một class chỉ có duy nhất một instance

```php
// Laravel Service Container quản lý Singletons

// app/Providers/AppServiceProvider.php

public function register()
{
    // Đăng ký singleton
    $this->app->singleton(PaymentService::class, function ($app) {
        return new PaymentService();
    });
}

// Khi gọi, luôn trả về cùng một instance
$payment1 = app(PaymentService::class);
$payment2 = app(PaymentService::class);
// $payment1 === $payment2 (true)
```

---

## 5️⃣ AUTHENTICATION PATTERN: JWT (JSON Web Token)

### 📚 Cơ chế hoạt động:

```
┌──────────────┐                          ┌──────────────┐
│   CLIENT     │                          │   SERVER     │
└──────┬───────┘                          └──────┬───────┘
       │                                         │
       │  1. POST /auth/login                   │
       │     { email, password }                │
       │────────────────────────────────────────>│
       │                                         │
       │                                    2. Validate
       │                                    3. Generate JWT
       │                                         │
       │  4. Return JWT Token                   │
       │<────────────────────────────────────────│
       │     {                                   │
       │       "token": "eyJhbGc...",            │
       │       "user": {...}                     │
       │     }                                   │
       │                                         │
       │  5. GET /books                         │
       │     Headers:                            │
       │     Authorization: Bearer eyJhbGc...    │
       │────────────────────────────────────────>│
       │                                         │
       │                                    6. Verify JWT
       │                                    7. Get User
       │                                    8. Check Permission
       │                                    9. Process Request
       │                                         │
       │  10. Return Data                       │
       │<────────────────────────────────────────│
       │                                         │
```

### 💻 Code implementation:

```php
// app/Http/Controllers/Api/AuthController.php

class AuthController extends Controller
{
    // Login: Tạo JWT token
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        
        // Attempt authentication
        if (!$token = auth('api')->attempt($credentials)) {
            return ApiResponse::error('Sai email hoặc mật khẩu', null, 401);
        }
        
        // Return token
        return ApiResponse::success('Đăng nhập thành công', [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user()
        ]);
    }
    
    // Get current user (cần JWT token)
    public function me()
    {
        return ApiResponse::success('Thông tin người dùng', auth('api')->user());
    }
    
    // Logout: Vô hiệu hóa token
    public function logout()
    {
        auth('api')->logout();
        return ApiResponse::success('Đăng xuất thành công');
    }
}
```

---

## 6️⃣ LAYERED ARCHITECTURE (Kiến trúc phân lớp)

```
┌─────────────────────────────────────────────────────┐
│          PRESENTATION LAYER (Routes/API)             │
│  - Nhận HTTP requests                                │
│  - Trả về HTTP responses                             │
│  - Format JSON                                       │
└───────────────────┬─────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────┐
│         APPLICATION LAYER (Controllers)              │
│  - Xử lý business logic                              │
│  - Validation                                        │
│  - Authorization                                     │
│  - Orchestrate các operations                       │
└───────────────────┬─────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────┐
│          DOMAIN LAYER (Models/Entities)              │
│  - Business entities (User, Book, Tournament)        │
│  - Business rules                                    │
│  - Relationships                                     │
└───────────────────┬─────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────┐
│        DATA ACCESS LAYER (Eloquent ORM)              │
│  - Database queries                                  │
│  - Data persistence                                  │
│  - Migrations                                        │
└───────────────────┬─────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────┐
│            DATABASE (MySQL/SQLite)                   │
│  - Physical data storage                             │
└─────────────────────────────────────────────────────┘
```

---

## 7️⃣ RBAC (Role-Based Access Control) PATTERN

### 📊 Mô hình phân quyền:

```
┌─────────────────────────────────────────────────────┐
│                      ROLES                           │
├─────────────────────────────────────────────────────┤
│  Admin    │  Owner   │  Staff   │  Member          │
└─────┬───────────┬─────────┬──────────┬──────────────┘
      │           │         │          │
      ▼           ▼         ▼          ▼
┌───────────────────────────────────────────────────┐
│               PERMISSIONS                          │
├───────────────────────────────────────────────────┤
│                                                    │
│  Admin:                                           │
│  ✅ Tất cả quyền trong hệ thống                   │
│  ✅ Phê duyệt owner                               │
│  ✅ Xem tất cả booking                            │
│  ✅ Tổ chức giải đấu cấp cao                      │
│                                                    │
│  Owner:                                           │
│  ✅ Quản lý sân của mình                          │
│  ✅ Xem booking của sân mình                      │
│  ✅ Quản lý staff                                 │
│  ✅ Tổ chức giải tại sân mình                     │
│  ❌ Không xem booking của owner khác              │
│                                                    │
│  Staff:                                           │
│  ✅ Xem booking của sân được gán                  │
│  ✅ Check-in khách                                │
│  ❌ Không tạo/xóa sân                             │
│  ❌ Không xem doanh thu                           │
│                                                    │
│  Member:                                          │
│  ✅ Đặt sân                                       │
│  ✅ Xem booking của mình                          │
│  ✅ Tạo đội, tham gia giải                        │
│  ❌ Không xem booking người khác                  │
│  ❌ Không quản lý sân                             │
│                                                    │
└───────────────────────────────────────────────────┘
```

### 💻 Implementation:

```php
// database/seeders/RoleSeeder.php
Role::create(['id' => 1, 'name_role' => 'admin']);
Role::create(['id' => 2, 'name_role' => 'owner']);
Role::create(['id' => 3, 'name_role' => 'staff']);
Role::create(['id' => 4, 'name_role' => 'member']);

// app/Models/User.php
class User extends Authenticatable
{
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    public function isAdmin()
    {
        return $this->role->name_role === 'admin';
    }
    
    public function isOwner()
    {
        return $this->role->name_role === 'owner';
    }
}

// app/Http/Middleware/CheckRole.php
class CheckRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();
        
        if (!$user || !in_array($user->role->name_role, $roles)) {
            return ApiResponse::error('Unauthorized', null, 403);
        }
        
        return $next($request);
    }
}

// routes/api.php
// Chỉ admin mới được truy cập
Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/owner-registrations/{id}/approve', [OwnerRegistrationController::class, 'approve']);
});

// Admin và Owner được truy cập
Route::middleware(['jwt.auth', 'role:admin,owner'])->group(function () {
    Route::get('/field-clusters', [FieldClusterController::class, 'index']);
    Route::post('/tournaments', [TournamentController::class, 'store']);
});

// Tất cả user đã login
Route::middleware('jwt.auth')->group(function () {
    Route::get('/profile', [AuthController::class, 'me']);
    Route::post('/books', [BookController::class, 'store']);
});
```

---

## 8️⃣ SERVICE LAYER PATTERN (Có thể mở rộng)

### 📚 Định nghĩa:
Tách business logic phức tạp ra khỏi Controller, đưa vào Service layer

### 💡 Ví dụ nếu áp dụng:

```php
// app/Services/TournamentService.php

class TournamentService
{
    public function createTournament($data)
    {
        DB::beginTransaction();
        try {
            // 1. Tạo tournament
            $tournament = Tournament::create($data);
            
            // 2. Book sân tự động
            $this->bookFieldsForTournament($tournament, $data['bookings']);
            
            // 3. Gửi notification
            $this->notifyParticipants($tournament);
            
            DB::commit();
            return $tournament;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    private function bookFieldsForTournament($tournament, $bookings)
    {
        // Complex logic...
    }
    
    private function notifyParticipants($tournament)
    {
        // Send notifications...
    }
}

// Controller sử dụng Service
class TournamentController extends Controller
{
    protected $tournamentService;
    
    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([...]);
        
        $tournament = $this->tournamentService->createTournament($validated);
        
        return ApiResponse::success('Tạo giải thành công', $tournament);
    }
}
```

---

## 📊 TÓM TẮT CÁC MÔ HÌNH VÀ LỢI ÍCH

| Mô hình/Pattern | Mục đích | Ví dụ trong project |
|-----------------|----------|---------------------|
| **MVC** | Tách biệt logic | Model (User.php), Controller (BookController.php) |
| **RESTful API** | API chuẩn hóa | GET /books, POST /tournaments |
| **Active Record (ORM)** | Thao tác DB dễ dàng | `Book::where('status', 'active')->get()` |
| **Repository Pattern** | Tách biệt data access | Eloquent Models |
| **Facade Pattern** | Đơn giản hóa API | `Hash::make()`, `Auth::user()` |
| **Middleware Pattern** | Filter requests | JwtMiddleware, CheckRole |
| **Factory Pattern** | Tạo test data | UserFactory, BookFactory |
| **Observer Pattern** | Event-driven | Model events (created, updated) |
| **Strategy Pattern** | Nhiều thuật toán | Price calculation |
| **Dependency Injection** | Loosely coupled | Controller constructor injection |
| **Singleton Pattern** | Single instance | Service Container |
| **JWT Authentication** | Stateless auth | Token-based API auth |
| **RBAC** | Phân quyền | 4 roles: Admin, Owner, Staff, Member |
| **Layered Architecture** | Tách biệt concerns | Presentation → Application → Domain → Data |

---

## 🎯 LỢI ÍCH CỦA KIẾN TRÚC NÀY

### ✅ Maintainability (Dễ bảo trì)
- Code có cấu trúc rõ ràng
- Dễ tìm và sửa bug
- Dễ thêm tính năng mới

### ✅ Scalability (Dễ mở rộng)
- Có thể thêm nhiều endpoint
- Có thể thêm nhiều role/permission
- Có thể scale horizontal (nhiều server)

### ✅ Testability (Dễ test)
- Unit test từng component
- Integration test API endpoints
- Mock dependencies dễ dàng

### ✅ Security (Bảo mật)
- JWT authentication
- Role-based authorization
- Input validation
- SQL injection prevention (ORM)

### ✅ Reusability (Tái sử dụng)
- Model relationships
- Middleware chains
- Helper functions
- Service layers

---

## 🚀 SO SÁNH VỚI CÁC KIẾN TRÚC KHÁC

| Kiến trúc | Project này | Khác biệt |
|-----------|-------------|-----------|
| **Monolithic** | ✅ Có | Tất cả trong 1 codebase |
| **Microservices** | ❌ Không | Có thể tách thành nhiều service riêng (Auth Service, Booking Service, Tournament Service) |
| **Serverless** | ❌ Không | Có thể deploy lên AWS Lambda |
| **Event-Driven** | ⚠️ Một phần | Có Model Events, có thể mở rộng với Queue Jobs |
| **CQRS** | ❌ Không | Có thể tách Read/Write models |
| **Domain-Driven Design (DDD)** | ⚠️ Một phần | Có Domain Models, có thể tổ chức theo bounded contexts |

---

## 📚 KẾT LUẬN

**Project này sử dụng:**

### 🏗️ Kiến trúc chính:
- **MVC (Model-View-Controller)** - Kiến trúc Laravel standard
- **RESTful API Architecture** - API design pattern
- **Layered Architecture** - Phân tách concerns

### 🎨 Design Patterns:
- **Active Record (ORM)** - Eloquent Models
- **Repository Pattern** - Via Eloquent
- **Facade Pattern** - Laravel Facades
- **Middleware Pattern** - Request filtering
- **Factory Pattern** - Test data generation
- **Observer Pattern** - Model events
- **Strategy Pattern** - Business logic variations
- **Dependency Injection** - Service Container
- **Singleton Pattern** - Service management

### 🔐 Security Patterns:
- **JWT Authentication** - Stateless auth
- **RBAC (Role-Based Access Control)** - Authorization

### 📐 Best Practices:
- **SOLID Principles** - Clean code
- **DRY (Don't Repeat Yourself)** - Code reuse
- **Separation of Concerns** - Clear responsibilities
- **Convention over Configuration** - Laravel conventions

---

**Đây là một kiến trúc MVC chuẩn Laravel với RESTful API, áp dụng nhiều design patterns hiện đại, phù hợp cho ứng dụng web API có khả năng mở rộng cao!**

---

**Ngày tạo:** 05/05/2026  
**Version:** 1.0 - Chi tiết đầy đủ
