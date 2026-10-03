<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('portal.dashboard') : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.store');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/beranda', [PortalController::class, 'dashboard'])->name('portal.dashboard');
    Route::get('/laporan', [PortalController::class, 'createReport'])->name('portal.reports.create');
    Route::post('/laporan', [PortalController::class, 'storeReport'])->name('portal.reports.store');
    Route::get('/papan-laporan', [PortalController::class, 'reports'])->name('portal.reports.index');
    Route::get('/kerja-bakti', [PortalController::class, 'workdays'])->name('portal.workdays');
    Route::get('/warga-peduli', [PortalController::class, 'rewards'])->name('portal.rewards');
    Route::post('/warga-peduli/tukar', [PortalController::class, 'redeem'])->name('portal.redeem');
    Route::get('/edukasi', [PortalController::class, 'education'])->name('portal.education');
    Route::post('/edukasi/pendaftaran', [PortalController::class, 'registerActivity'])->name('portal.education.register');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::patch('/laporan/{report}', [AdminController::class, 'updateReport'])->name('reports.update');
    Route::post('/kerja-bakti', [AdminController::class, 'storeWorkday'])->name('workdays.store');
    Route::get('/warga-peduli', [AdminController::class, 'redemptions'])->name('redemptions.index');
    Route::patch('/warga-peduli/{redemption}', [AdminController::class, 'updateRedemption'])->name('redemptions.update');
    Route::get('/warga', [AdminController::class, 'residents'])->name('residents.index');
    Route::patch('/warga/{user}', [AdminController::class, 'updateResident'])->name('residents.update');
    Route::get('/kegiatan-edukasi', [AdminController::class, 'activityRegistrations'])->name('activity-registrations.index');
    Route::patch('/kegiatan-edukasi/{activityRegistration}', [AdminController::class, 'updateActivityRegistration'])->name('activity-registrations.update');
});
