# ============================================
# TEST API PAYMENT & QR CODE
# ============================================

Write-Host \"
=== TESTING API PAYMENT & QR CODE ===
\" -ForegroundColor Green

# 1. Test Login Owner
Write-Host \"1. Testing Login Owner...\" -ForegroundColor Yellow
$loginResponse = Invoke-RestMethod -Uri \"http://localhost:8000/api/login\" -Method POST -Body (@{email=\"owner@test.com\"; password=\"123456\"} | ConvertTo-Json) -ContentType \"application/json\"

if ($loginResponse.success) {
    $ownerToken = $loginResponse.data.access_token
    Write-Host \"   ✅ Login Owner Success: Token = $($ownerToken.Substring(0,20))...\" -ForegroundColor Green
} else {
    Write-Host \"   ❌ Login Owner Failed\" -ForegroundColor Red
    exit
}

# 2. Test Get QR Code (should be empty first time)
Write-Host \"
2. Testing Get QR Code...\" -ForegroundColor Yellow
$headers = @{Authorization=\"Bearer $ownerToken\"}
$qrResponse = Invoke-RestMethod -Uri \"http://localhost:8000/api/owner/qr-code\" -Method GET -Headers $headers

if ($qrResponse.success) {
    Write-Host \"   ✅ Get QR Code Success\" -ForegroundColor Green
    Write-Host \"   Has QR: $($qrResponse.data.has_qr_code)\" -ForegroundColor Cyan
    if ($qrResponse.data.qr_code_url) {
        Write-Host \"   QR URL: $($qrResponse.data.qr_code_url)\" -ForegroundColor Cyan
    }
} else {
    Write-Host \"   ❌ Get QR Code Failed\" -ForegroundColor Red
}

# 3. Test Get Tournament Prices
Write-Host \"
3. Testing Tournament Session Prices...\" -ForegroundColor Yellow
$pricesResponse = Invoke-RestMethod -Uri \"http://localhost:8000/api/tournament-session-prices\" -Method GET -Headers $headers

if ($pricesResponse.success) {
    $priceCount = $pricesResponse.data.Count
    Write-Host \"   ✅ Get Prices Success: Total $priceCount prices\" -ForegroundColor Green
    
    # Show sample price
    if ($priceCount -gt 0) {
        $sample = $pricesResponse.data[0]
        Write-Host \"   Sample: Cluster $($sample.field_cluster_id) - $($sample.field_type) - $($sample.day_type) - $($sample.session_type) = $($sample.price_per_session) VND\" -ForegroundColor Cyan
    }
} else {
    Write-Host \"   ❌ Get Prices Failed\" -ForegroundColor Red
}

# 4. Test Login User
Write-Host \"
4. Testing Login User (Captain)...\" -ForegroundColor Yellow
$userLoginResponse = Invoke-RestMethod -Uri \"http://localhost:8000/api/login\" -Method POST -Body (@{email=\"captain1@test.com\"; password=\"123456\"} | ConvertTo-Json) -ContentType \"application/json\"

if ($userLoginResponse.success) {
    $userToken = $userLoginResponse.data.access_token
    Write-Host \"   ✅ Login User Success: Token = $($userToken.Substring(0,20))...\" -ForegroundColor Green
} else {
    Write-Host \"   ❌ Login User Failed\" -ForegroundColor Red
    Write-Host \"   Note: Cần chạy seeder cho user test\" -ForegroundColor Yellow
}

Write-Host \"
=== MANUAL TESTING REQUIRED ===\" -ForegroundColor Magenta
Write-Host \"Để test upload QR code, dùng Postman/Insomnia:\" -ForegroundColor White
Write-Host \"POST http://localhost:8000/api/owner/upload-qr\" -ForegroundColor Cyan
Write-Host \"Header: Authorization: Bearer $ownerToken\" -ForegroundColor Cyan
Write-Host \"Body (form-data): qr_code = [file ảnh]\" -ForegroundColor Cyan

Write-Host \"
=== DONE ===
\" -ForegroundColor Green
