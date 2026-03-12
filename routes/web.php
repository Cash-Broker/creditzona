<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contacts', [PageController::class, 'contact'])->name('contact');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/chesto-zadavani-vaprosi', [PageController::class, 'faq'])->name('faq.legacy');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [PageController::class, 'blog'])->name('blog.show');
Route::view('/politika-za-poveritelnost', 'layouts.app', [
    'page' => 'privacy_policy',
    'title' => 'Политика за поверителност',
])->name('privacy');
Route::view('/politika-za-biskvitki', 'layouts.app', [
    'page' => 'cookie_policy',
    'title' => 'Политика за бисквитки',
])->name('cookies');
Route::view('/obshti-usloviya', 'layouts.app', [
    'page' => 'terms',
    'title' => 'Общи условия',
])->name('terms');
Route::redirect('/poveritelnost', '/politika-za-poveritelnost', 301);
Route::redirect('/obshtiusloviq', '/obshti-usloviya', 301);
Route::redirect('/политика-за-поверителност', '/politika-za-biskvitki', 301);
// Route::get('/potrebitelski-kredit', [PageController::class, 'consumer'])->name('service.consumer');
// Route::get('/ipotechen-kredit', [PageController::class, 'mortgage'])->name('service.mortgage');
// Route::get('/refinansirane', [PageController::class, 'refinance'])->name('service.refinance');
// Route::get('/izkupuvane-na-zadalzheniya', [PageController::class, 'debtBuyout'])->name('service.debt_buyout');


Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
