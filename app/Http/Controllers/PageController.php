<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return $this->renderPage('home', 'КредитЗона');
    }

    public function about(): View
    {
        return $this->renderPage('about', 'За нас');
    }

    public function contact(): View
    {
        return $this->renderPage('contact', 'Контакти');
    }

    public function faq(): View
    {
        return $this->renderPage('faq', 'Често задавани въпроси');
    }

    public function blog(): View
    {
        return $this->renderPage('blog', 'Блог');
    }

    public function consumer(): View
    {
        return $this->renderPage('consumer', 'Потребителски кредит');
    }

    public function mortgage(): View
    {
        return $this->renderPage('mortgage', 'Ипотечен кредит');
    }

    public function refinance(): View
    {
        return $this->renderPage('refinance', 'Рефинансиране');
    }

    public function debtBuyout(): View
    {
        return $this->renderPage('debt_buyout', 'Изкупуване на задължения');
    }

    private function renderPage(string $page, string $title): View
    {
        return view('layouts.app', [
            'page' => $page,
            'title' => $title,
        ]);
    }
}
