<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\CalendarApiController;
use App\Http\Controllers\Api\ContactMessageApiController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeadApiController;
use App\Http\Controllers\Api\LeadGuarantorApiController;
use App\Http\Controllers\Api\LeadMessageApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\FaqController;
use Illuminate\Support\Facades\Route;

Route::get('/faqs', [FaqController::class, 'index'])->name('api.faqs.index');
Route::get('/blogs', [BlogController::class, 'index'])->name('api.blogs.index');
Route::get('/blogs/{slug}', [BlogController::class, 'show'])->name('api.blogs.show');
Route::post('/contact-messages', [ContactMessageController::class, 'store'])
    ->middleware('throttle:contact-messages')
    ->name('api.contact-messages.store');

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/push-token', [AuthController::class, 'savePushToken']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Leads
    Route::get('/leads', [LeadApiController::class, 'index']);
    Route::get('/leads/attached', [LeadApiController::class, 'attached']);
    Route::get('/leads/statuses', [LeadApiController::class, 'statuses']);
    Route::get('/leads/{id}', [LeadApiController::class, 'show'])->where('id', '[0-9]+');
    Route::patch('/leads/{id}/status', [LeadApiController::class, 'updateStatus'])->where('id', '[0-9]+');
    Route::patch('/leads/{id}', [LeadApiController::class, 'update'])->where('id', '[0-9]+');
    Route::patch('/leads/{id}/mark-for-later', [LeadApiController::class, 'markForLater'])->where('id', '[0-9]+');
    Route::get('/leads/{id}/privacy-consent', [LeadApiController::class, 'privacyConsent'])->where('id', '[0-9]+');
    Route::patch('/leads/{id}/return', [LeadApiController::class, 'returnToPrimary'])->where('id', '[0-9]+');

    // Lead Guarantors
    Route::get('/leads/{id}/guarantors', [LeadGuarantorApiController::class, 'index'])->where('id', '[0-9]+');
    Route::post('/leads/{id}/guarantors', [LeadGuarantorApiController::class, 'store'])->where('id', '[0-9]+');
    Route::get('/leads/guarantor-statuses', [LeadGuarantorApiController::class, 'statuses']);
    Route::patch('/leads/{id}/guarantors/{guarantorId}', [LeadGuarantorApiController::class, 'update'])->where(['id' => '[0-9]+', 'guarantorId' => '[0-9]+']);
    Route::delete('/leads/{id}/guarantors/{guarantorId}', [LeadGuarantorApiController::class, 'destroy'])->where(['id' => '[0-9]+', 'guarantorId' => '[0-9]+']);
    Route::get('/leads/{id}/guarantors/{guarantorId}/privacy-consent', [LeadGuarantorApiController::class, 'privacyConsent'])->where(['id' => '[0-9]+', 'guarantorId' => '[0-9]+']);

    // Lead Messages
    Route::get('/leads/{id}/messages', [LeadMessageApiController::class, 'index'])->where('id', '[0-9]+');
    Route::post('/leads/{id}/messages', [LeadMessageApiController::class, 'store'])->where('id', '[0-9]+');
    Route::patch('/leads/{id}/messages/{messageId}', [LeadMessageApiController::class, 'update'])->where(['id' => '[0-9]+', 'messageId' => '[0-9]+']);
    Route::delete('/leads/{id}/messages/{messageId}', [LeadMessageApiController::class, 'destroy'])->where(['id' => '[0-9]+', 'messageId' => '[0-9]+']);

    // Calendar
    Route::get('/calendar', [CalendarApiController::class, 'index']);
    Route::post('/calendar', [CalendarApiController::class, 'store']);
    Route::get('/calendar/event-types', [CalendarApiController::class, 'eventTypes']);
    Route::get('/calendar/today', [CalendarApiController::class, 'today']);
    Route::patch('/calendar/{id}', [CalendarApiController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/calendar/{id}', [CalendarApiController::class, 'destroy'])->where('id', '[0-9]+');

    // Contact Messages (CRM)
    Route::get('/crm/contact-messages', [ContactMessageApiController::class, 'index']);
    Route::get('/crm/contact-messages/{id}', [ContactMessageApiController::class, 'show'])->where('id', '[0-9]+');
    Route::patch('/crm/contact-messages/{id}/assign', [ContactMessageApiController::class, 'assign'])->where('id', '[0-9]+');
    Route::patch('/crm/contact-messages/{id}/archive', [ContactMessageApiController::class, 'archive'])->where('id', '[0-9]+');

    // Auth extras
    Route::patch('/auth/availability', [AuthController::class, 'toggleAvailability']);

    // Users
    Route::get('/users', [UserApiController::class, 'index']);
    Route::get('/users/online', [UserApiController::class, 'online']);
});
