<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\ProfileController; // Import Profile Controller

// Redirect ke halaman login saat aplikasi pertama kali diakses
Route::get('/', function () {
    return redirect()->route('login');
});

// Route Autentikasi
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Jika Anda menggunakan fitur register
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');

// Jika Anda menggunakan fitur reset password
Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request')->middleware('guest');
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('guest');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset')->middleware('guest');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update')->middleware('guest');

// Jika Anda menggunakan fitur konfirmasi password
Route::get('/password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
Route::post('/password/confirm', [ConfirmPasswordController::class, 'confirm']);

// Jika Anda menggunakan fitur verifikasi email
Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');

// Route yang memerlukan autentikasi
Route::middleware(['auth'])->group(function () {
    // Route Dashboard - ini sesuai dengan $redirectTo di LoginController
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/energy-usage-data', [DashboardController::class, 'getEnergyUsageData'])->name('dashboard.energy-usage-data');

    // Route Transaksi (Contoh, sesuaikan jika perlu)
    Route::get('/transaction-example', function () {
        return view('pages.transaction.index');
    })->name('transaction.example'); // Beri nama jika ini rute yang valid

    // Route-route Transaksi
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{id}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::match(['put', 'patch'], '/transactions/{id}/status', [TransactionController::class, 'updateStatus'])->name('transactions.updateStatus');

    // Route-route Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update'); // Method PATCH
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});