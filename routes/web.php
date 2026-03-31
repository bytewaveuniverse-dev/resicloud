<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::livewire('/post/create', 'pages::post.create');

Route::livewire('/asientos', 'pages::asientos.index')->name('asientos');
Route::livewire('/usuarios', 'pages::usuarios.index')->name('usuarios');


Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
