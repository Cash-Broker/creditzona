<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return $this->renderPage('home', 'ÐšÑ€ÐµÐ´Ð¸Ñ‚Ð—Ð¾Ð½Ð°');
    }

    public function about(): View
    {
        return $this->renderPage('about', 'Ð—Ð° Ð½Ð°Ñ');
    }

    public function contact(): View
    {
        return $this->renderPage('contact', 'ÐšÐ¾Ð½Ñ‚Ð°ÐºÑ‚Ð¸');
    }

    public function faq(): View
    {
        return $this->renderPage('faq', 'Ð§ÐµÑÑ‚Ð¾ Ð·Ð°Ð´Ð°Ð²Ð°Ð½Ð¸ Ð²ÑŠÐ¿Ñ€Ð¾ÑÐ¸');
    }

    public function blog(?string $slug = null): View
    {
        return $this->renderPage('blog', 'Ð‘Ð»Ð¾Ð³');
    }

    public function consumer(): View
    {
        return $this->renderPage('consumer', 'ÐŸÐ¾Ñ‚Ñ€ÐµÐ±Ð¸Ñ‚ÐµÐ»ÑÐºÐ¸ ÐºÑ€ÐµÐ´Ð¸Ñ‚');
    }

    public function mortgage(): View
    {
        return $this->renderPage('mortgage', 'Ð˜Ð¿Ð¾Ñ‚ÐµÑ‡ÐµÐ½ ÐºÑ€ÐµÐ´Ð¸Ñ‚');
    }

    public function refinance(): View
    {
        return $this->renderPage('refinance', 'Ð ÐµÑ„Ð¸Ð½Ð°Ð½ÑÐ¸Ñ€Ð°Ð½Ðµ');
    }

    public function debtBuyout(): View
    {
        return $this->renderPage('debt_buyout', 'Ð˜Ð·ÐºÑƒÐ¿ÑƒÐ²Ð°Ð½Ðµ Ð½Ð° Ð·Ð°Ð´ÑŠÐ»Ð¶ÐµÐ½Ð¸Ñ');
    }

    private function renderPage(string $page, string $title): View
    {
        return view('layouts.app', [
            'page' => $page,
            'title' => $title,
        ]);
    }
}

