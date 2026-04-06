<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\FieldCluster;
use App\Models\Field;
use App\Models\OwnerProfile;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║      KIỂM TRA & FIX DATABASE - OWNER FIELDS PERMISSIONS      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Step 1: Kiểm tra column 'name' trong bảng fields
echo "📊 BƯỚC 1: KIỂM TRA CẤU TRÚC DATABASE\n";
echo "════════════════════════════════════════════════════════════════\n";

$columns = DB::select("SHOW COLUMNS FROM fields");
$hasNameColumn = false;
foreach ($columns as $column) {
    if ($column->Field === 'name') {
        $hasNameColumn = true;
        break;
    }
}

if ($hasNameColumn) {
    echo "✅ Column 'name' đã tồn tại trong bảng fields\n\n";
} else {
    echo "❌ Column 'name' CHƯA TỒN TẠI trong bảng fields\n";
    echo "⚠️  CẦN CHẠY: php artisan migrate\n\n";
}

// Step 2: Kiểm tra tất cả owners
echo "📊 BƯỚC 2: KIỂM TRA DỮ LIỆU OWNERS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$allOwners = OwnerProfile::with('user')->get();

if ($allOwners->isEmpty()) {
    echo "❌ KHÔNG CÓ OWNER PROFILES\n";
    echo "⚠️  CẦN CHẠY: php artisan db:seed --class=OwnerSeeder\n\n";
} else {
    echo "✅ Tìm thấy {$allOwners->count()} owners:\n\n";

    foreach ($allOwners as $ownerProfile) {
        echo "👤 Owner Profile ID {$ownerProfile->id}: {$ownerProfile->business_name}\n";
        echo "   User: {$ownerProfile->user->name} ({$ownerProfile->user->email})\n";

        $clusters = FieldCluster::where('owner_id', $ownerProfile->id)->get();
        echo "   Clusters: {$clusters->count()}\n";

        foreach ($clusters as $cluster) {
            $fields = Field::where('field_cluster_id', $cluster->id)->get();
            echo "      → Cluster ID {$cluster->id}: {$cluster->name}\n";
            echo "         Fields: {$fields->count()} sân\n";

            if ($fields->isEmpty()) {
                echo "         ❌ KHÔNG CÓ FIELDS!\n";
            } else {
                foreach ($fields as $field) {
                    $displayName = $field->name ?? "Field #{$field->id}";
                    echo "            * {$displayName} ({$field->field_type}) - {$field->status}\n";
                }
            }
        }
        echo "\n";
    }
}

// Step 3: Test owner1 permissions
echo "\n📊 BƯỚC 3: TEST OWNER1 PERMISSIONS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$owner1 = User::where('email', 'owner1@sanbongda.com')->first();

if (!$owner1) {
    echo "❌ Owner1 không tồn tại\n";
    echo "⚠️  CẦN CHẠY: php artisan db:seed --class=OwnerSeeder\n\n";
} else {
    echo "✅ Owner1 found: {$owner1->name}\n";

    if (!$owner1->ownerProfile) {
        echo "❌ Owner1 không có ownerProfile\n\n";
    } else {
        echo "✅ Owner Profile ID: {$owner1->ownerProfile->id}\n";

        $clustersCount = FieldCluster::where('owner_id', $owner1->ownerProfile->id)->count();
        $fieldsCount = Field::whereHas('fieldCluster', function($q) use ($owner1) {
            $q->where('owner_id', $owner1->ownerProfile->id);
        })->count();

        echo "   Clusters: {$clustersCount}\n";
        echo "   Fields: {$fieldsCount}\n\n";

        if ($clustersCount == 0) {
            echo "❌ Owner1 không có clusters\n";
            echo "⚠️  CẦN CHẠY: php artisan db:seed --class=FieldClusterSeeder\n\n";
        } elseif ($fieldsCount == 0) {
            echo " Owner1 có clusters nhưng KHÔNG CÓ FIELDS\n";
            echo "⚠️  CẦN CHẠY: php artisan db:seed --class=FieldSeeder\n\n";
        } else {
            echo "✅ Owner1 có đủ data để hoạt động\n\n";
        }
    }

    // Test login
    echo "🔐 Test login owner1:\n";
    $token = auth('api')->attempt([
        'email' => 'owner1@sanbongda.com',
        'password' => 'owner123'
    ]);

    if ($token) {
        echo "   ✅ Login SUCCESS\n";
        auth('api')->logout();
    } else {
        echo "   ❌ Login FAILED\n";
    }
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    KẾT LUẬN & HƯỚNG DẪN FIX                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$hasIssues = false;

if (!$hasNameColumn) {
    echo "❌ LỖI 1: Column 'name' chưa tồn tại\n";
    echo "   → FIX: php artisan migrate\n\n";
    $hasIssues = true;
}

$totalClusters = FieldCluster::count();
$totalFields = Field::count();

if ($totalClusters > 0 && $totalFields == 0) {
    echo "❌ LỖI 2: Có {$totalClusters} clusters nhưng KHÔNG CÓ FIELDS\n";
    echo "   → FIX: php artisan db:seed --class=FieldSeeder\n\n";
    $hasIssues = true;
}

if ($allOwners->isNotEmpty()) {
    foreach ($allOwners as $ownerProfile) {
        $clustersCount = FieldCluster::where('owner_id', $ownerProfile->id)->count();
        $fieldsCount = Field::whereHas('fieldCluster', function($q) use ($ownerProfile) {
            $q->where('owner_id', $ownerProfile->id);
        })->count();

        if ($clustersCount > 0 && $fieldsCount == 0) {
            echo "❌ LỖI 3: {$ownerProfile->user->email} có clusters nhưng không có fields\n";
            echo "   → FIX: php artisan db:seed --class=FieldSeeder\n\n";
            $hasIssues = true;
            break;
        }
    }
}

if (!$hasIssues) {
    echo "✅✅✅ BACKEND HOÀN TOÀN OK - KHÔNG CÓ LỖI!\n\n";
    echo "⚠️  NẾU FRONTEND VẪN BÁO LỖI 403:\n";
    echo "   → Đây là LỖI TỪ FRONTEND\n";
    echo "   → FE đang gọi sai API hoặc cache fields của owner khác\n";
    echo "   → Kiểm tra FE gọi GET /api/fields (authenticated)\n";
    echo "   → KHÔNG ĐƯỢC gọi GET /api/public/fields\n\n";
} else {
    echo "\n🔧 CHẠY CÁC LỆNH SAU ĐỂ FIX:\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "1. php artisan migrate\n";
    echo "2. php artisan db:seed --class=FieldSeeder\n";
    echo "3. php test-all-permissions.php (chạy lại script này để verify)\n\n";
}
