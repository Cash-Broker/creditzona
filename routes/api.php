<?php

use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\FaqController;
use Illuminate\Support\Facades\Route;

Route::get('/faqs', [FaqController::class, 'index'])->name('api.faqs.index');
Route::get('/blogs', [BlogController::class, 'index'])->name('api.blogs.index');
Route::get('/blogs/{slug}', [BlogController::class, 'show'])->name('api.blogs.show');
Route::post('/contact-messages', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact-messages')
    ->name('api.contact-messages.store');
