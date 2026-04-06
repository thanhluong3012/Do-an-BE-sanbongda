<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Field;
use App\Models\FieldCluster;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║    DEBUG OWNER FIELD UPDATE PERMISSIONS - CHI TIẾT           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Bạn đang login bằng email nào?
echo "📝 Nhập email owner đang login (mặc định: owner1@sanbongda.com): ";
$inputEmail = trim(fgets(STDIN));
$ownerEmail = $inputEmail ?: 'owner1@sanbongda.com';

echo "\n🔐 ĐĂNG NHẬP...\n";
echo "════════════════════════════════════════════════════════════════\n";

$owner = User::where('email', $ownerEmail)->first();

if (!$owner) {
    echo "❌ KHÔNG TÌM THẤY USER: {$ownerEmail}\n\n";
    echo "Danh sách owner có sẵn:\n";
    $owners = User::whereHas('role', function($q) {
        $q->where('name_role', 'owner');
    })->get();
    foreach ($owners as $o) {
        echo "  - {$o->email} (ID: {$o->id})\n";
    }
    exit;
}

echo "✅ USER FOUND\n";
echo "   ID: {$owner->id}\n";
echo "   Name: {$owner->name}\n";
echo "   Email: {$owner->email}\n";
echo "   Role ID: {$owner->role_id}\n";
echo "   Role: {$owner->role->name_role}\n\n";

if ($owner->role->name_role !== 'owner') {
    echo "❌ LỖI: User này KHÔNG PHẢI OWNER!\n";
    echo "   Role hiện tại: {$owner->role->name_role}\n\n";
    exit;
}

echo "👤 OWNER PROFILE\n";
echo "════════════════════════════════════════════════════════════════\n";

if (!$owner->ownerProfile) {
    echo "❌ OWNER KHÔNG CÓ OWNER PROFILE!\n";
    echo "   → Đây là nguyên nhân gây lỗi 403\n";
    echo "   → Cần chạy: php artisan db:seed --class=OwnerSeeder\n\n";
    exit;
}

$ownerProfile = $owner->ownerProfile;
echo "✅ Owner Profile ID: {$ownerProfile->id}\n";
echo "   Business Name: {$ownerProfile->business_name}\n";
echo "   Address: {$ownerProfile->address}\n\n";

echo "🏢 FIELD CLUSTERS CỦA OWNER\n";
echo "════════════════════════════════════════════════════════════════\n";

$clusters = FieldCluster::where('owner_id', $ownerProfile->id)->get();

if ($clusters->isEmpty()) {
    echo "❌ OWNER KHÔNG CÓ FIELD CLUSTERS!\n";
    echo "   → Cần chạy: php artisan db:seed --class=FieldClusterSeeder\n\n";
    exit;
}

echo "✅ Tìm thấy {$clusters->count()} cluster(s):\n\n";

foreach ($clusters as $cluster) {
    echo "📍 Cluster ID: {$cluster->id}\n";
    echo "   Name: {$cluster->name}\n";
    echo "   Owner ID: {$cluster->owner_id} ";

    if ($cluster->owner_id == $ownerProfile->id) {
        echo "✅ (MATCH!)\n";
    } else {
        echo "❌ (KHÔNG MATCH! Lỗi data!)\n";
    }

    // Lấy fields
    $fields = Field::where('field_cluster_id', $cluster->id)->get();
    echo "   Fields: {$fields->count()} sân\n\n";

    if ($fields->isEmpty()) {
        echo "   ❌ Cluster này không có fields!\n\n";
    } else {
        foreach ($fields as $field) {
            echo "   ⚽ Field ID {$field->id}:\n";
            echo "      Name: " . ($field->name ?? 'N/A') . "\n";
            echo "      Type: {$field->field_type}\n";
            echo "      Status: {$field->status}\n";
            echo "      Cluster ID: {$field->field_cluster_id}\n";

            // TEST UPDATE PERMISSION
            echo "      🔍 Test update permission:\n";

            // Giả lập logic trong FieldController::update()
            $currentCluster = FieldCluster::find($field->field_cluster_id);

            if (!$currentCluster) {
                echo "         ❌ Cluster không tồn tại!\n";
            } elseif ($currentCluster->owner_id !== $ownerProfile->id) {
                echo "         ❌ FAIL - Cluster owner_id ({$currentCluster->owner_id}) != ownerProfile id ({$ownerProfile->id})\n";
                echo "         → LỖI DATA: Field thuộc cluster của owner khác!\n";
            } else {
                echo "         ✅ PASS - Owner có quyền update field này\n";
            }
            echo "\n";
        }
    }
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    KIỂM TRA API THỰC TẾ                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Login thử
$token = auth('api')->attempt([
    'email' => $ownerEmail,
    'password' => $ownerEmail === 'owner1@sanbongda.com' ? 'owner123' : '123456'
]);

if (!$token) {
    echo "❌ LOGIN FAILED\n";
    echo "   Email: {$ownerEmail}\n";
    echo "   Password thử: owner123 hoặc 123456\n\n";
} else {
    echo "✅ LOGIN SUCCESS\n";
    echo "   Token: " . substr($token, 0, 20) . "...\n\n";

    $authenticatedUser = auth('api')->user();
    echo "🔍 KIỂM TRA AUTHENTICATED USER:\n";
    echo "   User ID: {$authenticatedUser->id}\n";
    echo "   Role: {$authenticatedUser->role->name_role}\n";
    echo "   Has ownerProfile: " . ($authenticatedUser->ownerProfile ? "YES (ID: {$authenticatedUser->ownerProfile->id})" : "NO") . "\n\n";

    // Lấy 1 field bất kỳ của owner để test
    $testField = Field::whereHas('fieldCluster', function($q) use ($ownerProfile) {
        $q->where('owner_id', $ownerProfile->id);
    })->first();

    if ($testField) {
        echo "🧪 TEST UPDATE FIELD ID {$testField->id}:\n";
        echo "   Current status: {$testField->status}\n";
        echo "   Trying to change to: maintenance\n\n";

        // Giả lập logic trong controller
        $currentCluster = FieldCluster::find($testField->field_cluster_id);

        echo "   📊 PERMISSION CHECK:\n";
        echo "   ├─ authenticatedUser->role->name_role: {$authenticatedUser->role->name_role}\n";
        echo "   ├─ authenticatedUser->ownerProfile: " . ($authenticatedUser->ownerProfile ? "EXISTS (ID: {$authenticatedUser->ownerProfile->id})" : "NULL") . "\n";
        echo "   ├─ currentCluster: " . ($currentCluster ? "EXISTS (ID: {$currentCluster->id})" : "NULL") . "\n";

        if ($currentCluster) {
            echo "   ├─ currentCluster->owner_id: {$currentCluster->owner_id}\n";
            echo "   ├─ authenticatedUser->ownerProfile->id: {$authenticatedUser->ownerProfile->id}\n";
            echo "   └─ Match: " . ($currentCluster->owner_id === $authenticatedUser->ownerProfile->id ? "YES ✅" : "NO ❌") . "\n\n";

            if ($currentCluster->owner_id !== $authenticatedUser->ownerProfile->id) {
                echo "   ❌ PERMISSION DENIED (403)\n";
                echo "   → Lý do: Cluster owner_id KHÔNG KHỚP với ownerProfile->id\n";
                echo "   → Field thuộc về owner khác!\n\n";
            } else {
                echo "   ✅ PERMISSION GRANTED (200)\n";
                echo "   → Owner có quyền update field này\n\n";
            }
        }
    }

    auth('api')->logout();
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    KẾT LUẬN                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📋 QUYỀN CỦA OWNER:\n";
echo "   ✅ Xem/Tạo/Sửa/Xóa FIELDS trong clusters của mình\n";
echo "   ✅ Đổi status field: available → maintenance → inactive\n";
echo "   ✅ Đổi field_type: 5v5, 7v7, 11v11\n";
echo "   ❌ KHÔNG được sửa fields của owner khác\n\n";

echo "🔍 NGUYÊN NHÂN LỖI 403:\n";
echo "   1. Owner không có ownerProfile → cần OwnerSeeder\n";
echo "   2. Cluster->owner_id khác ownerProfile->id → lỗi data\n";
echo "   3. Field->cluster thuộc owner khác → lỗi data\n";
echo "   4. Frontend gọi sai field_id (không thuộc owner)\n\n";

echo "✅ GIẢI PHÁP:\n";
echo "   php artisan migrate:fresh --seed\n";
echo "   hoặc\n";
echo "   php artisan db:seed --class=OwnerSeeder\n";
echo "   php artisan db:seed --class=FieldClusterSeeder\n";
echo "   php artisan db:seed --class=FieldSeeder\n\n";
