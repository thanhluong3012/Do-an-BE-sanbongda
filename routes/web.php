<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\OwnerRegistrationApprovedMail;

Route::get('/', function () {
    return view('welcome');
});

// TEST EMAIL - Gửi tới email bất kỳ
Route::get('/send-test-email/{email}', function ($email) {
    try {
        Mail::to($email)->send(
            new OwnerRegistrationApprovedMail('Test User', 'Test Business')
        );

        return response()->json([
            'success' => true,
            'message' => 'Email đã gửi thành công tới: ' . $email,
            'note' => 'Kiểm tra inbox và SPAM folder sau 2-5 phút'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});
