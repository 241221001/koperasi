<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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


Route::livewire('/setor-tunai', 'pages::anggota.setor-tunai')->name('setor-tunai')->middleware(['auth']);
Route::livewire('/tarik-tunai', 'pages::anggota.tarik-tunai')->name('tarik-tunai')->middleware(['auth']);
