<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIELD CLUSTERS ===\n";
$clusters = \App\Models\FieldCluster::select('id', 'name')->get();
foreach ($clusters as $fc) {
    echo "ID: {$fc->id} - {$fc->name}\n";
}

echo "\n=== TOURNAMENT SESSION PRICES ===\n";
$prices = \App\Models\TournamentSessionPrice::select('field_cluster_id', 'field_type', 'day_type', 'session_type', 'price_per_session')
    ->orderBy('field_cluster_id')
    ->orderBy('field_type')
    ->orderBy('day_type')
    ->orderBy('session_type')
    ->get();

if ($prices->isEmpty()) {
    echo "❌ KHÔNG CÓ GIÁ NÀO TRONG DATABASE!\n";
} else {
    foreach ($prices as $p) {
        echo "Cluster: {$p->field_cluster_id} | Type: {$p->field_type} | Day: {$p->day_type} | Session: {$p->session_type} | Price: " . number_format($p->price_per_session) . "\n";
    }
}

// Kiểm tra cụ thể giá cho 5v5, weekday, full_day
echo "\n=== KIỂM TRA CỤ THỂ: 5v5, weekday, full_day ===\n";
foreach ($clusters as $fc) {
    $price = \App\Models\TournamentSessionPrice::where('field_cluster_id', $fc->id)
        ->where('field_type', '5v5')
        ->where('day_type', 'weekday')
        ->where('session_type', 'full_day')
        ->first();

    if ($price) {
        echo "✅ Cluster {$fc->id} ({$fc->name}): Có giá = " . number_format($price->price_per_session) . "\n";
    } else {
        echo "❌ Cluster {$fc->id} ({$fc->name}): KHÔNG CÓ GIÁ!\n";
    }
}
