<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::livewire('/login', 'pages::login')->name('login');
Route::livewire('/register', 'pages::register')->name('register');

// Grup Route Admin Panel
Route::livewire('/admin/dashboard-admin', 'pages::admin.dashboard-admin')->name('admin.dashboard-admin');

// Dashboard khusus Anggota (Portal Client)
Route::livewire('/anggota/dashboard', 'pages::anggota.dashboard')->name('anggota.dashboard');
Route::livewire('/anggota/pinjaman/ajukan', 'pages::anggota.ajukan-pinjaman')
    ->name('anggota.ajukan-pinjaman');


// Route untuk anggota
Route::middleware(['auth'])->group(function () {
    Route::get('/anggota/simpanan', function () {
        return app('livewire')->mount('anggota.simpanan');
    })->name('anggota.simpanan');

    Route::get('/anggota/arus-kas', function () {
        return app('livewire')->mount('anggota.arus-kas');
    })->name('anggota.arus-kas');
});