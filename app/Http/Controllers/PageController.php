<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View { return view('pages.home'); }
    public function about(): View { return view('pages.about'); }
    public function contact(): View { return view('pages.contact'); }
    public function faq(): View { return view('pages.faq'); }
    public function blog(): View { return view('pages.blog'); }

    public function consumer(): View { return view('pages.services.consumer'); }
    public function mortgage(): View { return view('pages.services.mortgage'); }
    public function refinance(): View { return view('pages.services.refinance'); }
    public function debtBuyout(): View { return view('pages.services.debt_buyout'); }
}