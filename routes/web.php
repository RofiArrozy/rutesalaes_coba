<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SalesUserController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - RuteSales Application
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. AUTENTIKASI & PUBLIC ROUTES
// =========================================================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/quick-login/{id}', [AuthController::class, 'quickLogin'])->name('quick.login');

// =========================================================================
// 2. PROTECTED ROUTES (HARUS LOGIN)
// =========================================================================
Route::middleware(['auth'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Pengalihan Halaman Utama Berdasarkan Role
    Route::get('/', function () {
        return auth()->user()->isSales() 
            ? redirect()->route('rute.index') 
            : redirect()->route('dashboard');
    });

    // ---------------------------------------------------------------------
    // A. MODUL KHUSUS ADMIN (Role: Admin)
    // ---------------------------------------------------------------------
    Route::middleware(['role:admin'])->group(function () {
        // Dashboard Analitik Admin
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Management User / Sales Team (Menghasilkan sales.index, sales.create, dll)
        Route::resource('sales', SalesUserController::class);

        // Management Toko (Khusus Aksi Edit & Hapus Admin)
        Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::get('/stores/{id}/edit', [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('/stores/{id}', [StoreController::class, 'update'])->name('stores.update');
        Route::delete('/stores/{id}', [StoreController::class, 'destroy'])->name('stores.destroy');

        // Alias Prefix /admin/*
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/users', [SalesUserController::class, 'index'])->name('users.index');
            Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
            Route::get('/rute', [VisitController::class, 'index'])->name('rute.index');
        });
    });

    // ---------------------------------------------------------------------
    // B. MODUL KHUSUS SALES (Role: Sales)
    // Gunakan prefiks nama 'sales-panel.' agar TIDAK bentrok dengan 'sales.' resource
    // ---------------------------------------------------------------------
    Route::prefix('sales-panel')->middleware(['role:sales'])->name('sales-panel.')->group(function () {
        Route::get('/', [VisitController::class, 'index'])->name('dashboard');
        Route::get('/rute', [VisitController::class, 'index'])->name('rute.index');
        Route::get('/rute/detail/{id}', [VisitController::class, 'detail'])->name('rute.detail');
        Route::post('/rute/checkin/{id}', [VisitController::class, 'checkin'])->name('rute.checkin');
    });

    // ---------------------------------------------------------------------
    // C. MODUL BERSAMA (Dapat Diakses Admin & Sales)
    // ---------------------------------------------------------------------
    // Data Toko (Lihat Detail & Daftar)
    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/{id}', [StoreController::class, 'show'])->name('stores.show');

    // Data Rute Kunjungan
    Route::prefix('rute')->name('rute.')->group(function () {
        Route::get('/', [VisitController::class, 'index'])->name('index');
        Route::get('/create', [VisitController::class, 'create'])->name('create');
        Route::post('/', [VisitController::class, 'store'])->name('store');
        Route::get('/detail/{id}', [VisitController::class, 'detail'])->name('detail');
        Route::post('/checkin/{id}', [VisitController::class, 'checkin'])->name('checkin');
        Route::get('/{id}/edit', [VisitController::class, 'edit'])->name('edit');
        Route::put('/{id}', [VisitController::class, 'update'])->name('update');
        Route::delete('/{id}', [VisitController::class, 'destroy'])->name('destroy');
    });

    // Helper Redirect
    Route::get('/visits/{id}', function ($id) {
        return redirect()->route('rute.detail', $id);
    });
});