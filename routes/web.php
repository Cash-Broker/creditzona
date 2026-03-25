<?php

use App\Http\Controllers\AdminCalendarEventFeedController;
use App\Http\Controllers\AdminCalendarEventTimingController;
use App\Http\Controllers\AdminDocumentFileController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadDocumentDownloadController;
use App\Http\Controllers\LeadGuarantorDocumentDownloadController;
use App\Http\Controllers\LeadGuarantorPrivacyConsentDocumentDownloadController;
use App\Http\Controllers\LeadPrivacyConsentDocumentDownloadController;
use App\Http\Controllers\PageController;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contacts', [PageController::class, 'contact'])->name('contact');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::redirect('/chesto-zadavani-vaprosi', '/faq', 301)->name('faq.legacy');

// Route::get('/potrebitelski-kredit', [PageController::class, 'consumer'])->name('service.consumer');
// Route::get('/ipotechen-kredit', [PageController::class, 'mortgage'])->name('service.mortgage');
// Route::get('/refinansirane', [PageController::class, 'refinance'])->name('service.refinance');
// Route::get('/izkupuvane-na-zadalzheniya', [PageController::class, 'debtBuyout'])->name('service.debt_buyout');

Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [PageController::class, 'blogShow'])->name('blog.show');

Route::get('/politika-za-poveritelnost', [PageController::class, 'privacyPolicy'])->name('privacy');
Route::get('/politika-za-biskvitki', [PageController::class, 'cookiePolicy'])->name('cookies');
Route::get('/obshti-usloviya', [PageController::class, 'terms'])->name('terms');

Route::redirect('/poveritelnost', '/politika-za-poveritelnost', 301);
Route::redirect('/obshtiusloviq', '/obshti-usloviya', 301);
Route::redirect('/политика-за-поверителност', '/politika-za-poveritelnost', 301);
Route::redirect('/политика-за-бисквитки', '/politika-za-biskvitki', 301);

Route::post('/leads', [LeadController::class, 'store'])
    ->middleware('throttle:lead-submissions')
    ->name('leads.store');

Route::middleware([Authenticate::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/documents/{adminDocument}/open', [AdminDocumentFileController::class, 'open'])
            ->name('documents.open');
        Route::get('/documents/{adminDocument}/download', [AdminDocumentFileController::class, 'download'])
            ->name('documents.download');
        Route::get('/leads/{lead}/documents/download', LeadDocumentDownloadController::class)
            ->name('leads.documents.download');
        Route::get('/leads/{lead}/privacy-consent/download', LeadPrivacyConsentDocumentDownloadController::class)
            ->name('leads.privacy-consent.download');
        Route::get('/leads/{lead}/guarantors/{guarantor}/documents/download', LeadGuarantorDocumentDownloadController::class)
            ->name('leads.guarantors.documents.download');
        Route::get('/leads/{lead}/guarantors/{guarantor}/privacy-consent/download', LeadGuarantorPrivacyConsentDocumentDownloadController::class)
            ->name('leads.guarantors.privacy-consent.download');
        Route::get('/calendar/events/feed', AdminCalendarEventFeedController::class)
            ->name('calendar-events.feed');
        Route::patch('/calendar/events/{calendarEvent}/timing', AdminCalendarEventTimingController::class)
            ->name('calendar-events.timing.update');
    });
