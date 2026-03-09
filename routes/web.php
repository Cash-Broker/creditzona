<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/za-nas', [PageController::class, 'about'])->name('about');
Route::get('/kontakti', [PageController::class, 'contact'])->name('contact');
Route::get('/chesto-zadavani-vaprosi', [PageController::class, 'faq'])->name('faq');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');

// Service pages
Route::get('/potrebitelski-kredit', [PageController::class, 'consumer'])->name('service.consumer');
Route::get('/ipotechen-kredit', [PageController::class, 'mortgage'])->name('service.mortgage');
Route::get('/refinansirane', [PageController::class, 'refinance'])->name('service.refinance');
Route::get('/izkupuvane-na-zadalzheniya', [PageController::class, 'debtBuyout'])->name('service.debt_buyout');

// Lead submission endpoint (shared for all forms)
Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');