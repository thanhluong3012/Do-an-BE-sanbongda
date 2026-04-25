<?php

/**
 * Test QR Code URL Validation
 * Kiểm tra backend chỉ chấp nhận full URL, từ chối relative path
 */

echo "=== TEST QR CODE URL VALIDATION ===\n\n";

// Test cases
$testCases = [
    // ✅ Valid URLs (should pass)
    [
        'url' => 'https://xxx.supabase.co/storage/v1/object/public/qr-codes/abc123.jpg',
        'expected' => true,
        'description' => 'Valid HTTPS Supabase URL'
    ],
    [
        'url' => 'http://example.com/images/qr.png',
        'expected' => true,
        'description' => 'Valid HTTP URL'
    ],
    [
        'url' => 'https://cdn.cloudflare.com/path/to/qr.jpg',
        'expected' => true,
        'description' => 'Valid CDN URL'
    ],

    // ❌ Invalid paths (should fail)
    [
        'url' => 'owner_qr_codes/owner_1_qr_1714567890.jpg',
        'expected' => false,
        'description' => 'Relative path (SHOULD REJECT)'
    ],
    [
        'url' => '/storage/owner_qr_codes/qr.jpg',
        'expected' => false,
        'description' => 'Absolute path without domain (SHOULD REJECT)'
    ],
    [
        'url' => 'qr-code.jpg',
        'expected' => false,
        'description' => 'Filename only (SHOULD REJECT)'
    ],
    [
        'url' => 'ftp://example.com/qr.jpg',
        'expected' => false,
        'description' => 'FTP protocol (SHOULD REJECT)'
    ],
    [
        'url' => '',
        'expected' => false,
        'description' => 'Empty string (SHOULD REJECT)'
    ],
];

echo "Running validation tests...\n\n";

foreach ($testCases as $index => $test) {
    $url = $test['url'];
    $expected = $test['expected'];
    $description = $test['description'];

    // Test 1: Laravel URL validation
    $isValidUrl = filter_var($url, FILTER_VALIDATE_URL) !== false;

    // Test 2: Starts with http:// or https://
    $startsWithHttp = preg_match('/^https?:\/\//i', $url);

    // Combined result
    $isValid = $isValidUrl && $startsWithHttp;

    // Check if matches expected
    $testPassed = ($isValid === $expected);
    $status = $testPassed ? '✅ PASS' : '❌ FAIL';

    echo sprintf(
        "%s | Test #%d: %s\n",
        $status,
        $index + 1,
        $description
    );

    echo sprintf(
        "   URL: %s\n",
        $url ?: '(empty)'
    );

    echo sprintf(
        "   filter_var: %s | regex: %s | Result: %s | Expected: %s\n",
        $isValidUrl ? 'true' : 'false',
        $startsWithHttp ? 'true' : 'false',
        $isValid ? 'VALID' : 'INVALID',
        $expected ? 'VALID' : 'INVALID'
    );

    if (!$testPassed) {
        echo "   ⚠️  TEST FAILED!\n";
    }

    echo "\n";
}

echo "\n=== VALIDATION RULES ===\n";
echo "Backend chỉ chấp nhận:\n";
echo "1. Full URL (http:// hoặc https://)\n";
echo "2. Max length: 1000 characters\n";
echo "3. Valid URL format\n";
echo "\n";

echo "Backend TỪ CHỐI:\n";
echo "1. Relative path (owner_qr_codes/xxx.jpg)\n";
echo "2. Absolute path without domain (/storage/xxx.jpg)\n";
echo "3. Non-HTTP protocols (ftp://, file://)\n";
echo "4. Empty string\n";
echo "5. Filename only (qr.jpg)\n";
echo "\n";

echo "=== FRONTEND GUIDELINE ===\n";
echo "1. Upload file to Supabase\n";
echo "2. Get publicUrl from Supabase\n";
echo "3. Send full URL to backend\n";
echo "4. Example: https://xxx.supabase.co/storage/v1/object/public/...\n";
echo "\n";
