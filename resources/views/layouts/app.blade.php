<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'КредитЗона')</title>

    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="bg-gray-50 text-gray-900">

<header class="bg-white shadow">
    <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">

        <a href="{{ route('home') }}" class="text-xl font-bold text-yellow-600">
            КредитЗона
        </a>

        <nav class="flex gap-6 text-sm">
            <a href="{{ route('service.consumer') }}" class="hover:text-yellow-600">Потребителски</a>
            <a href="{{ route('service.mortgage') }}" class="hover:text-yellow-600">Ипотечен</a>
            <a href="{{ route('service.refinance') }}" class="hover:text-yellow-600">Рефинансиране</a>
            <a href="{{ route('faq') }}" class="hover:text-yellow-600">FAQ</a>
            <a href="{{ route('contact') }}" class="hover:text-yellow-600">Контакти</a>
        </nav>

    </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-12">
    @yield('content')
</main>

<footer class="bg-white border-t mt-20">
    <div class="max-w-6xl mx-auto px-4 py-8 text-sm text-gray-600">

        <p class="mb-2">
            Ние сме кредитни консултанти/посредници и не отпускaме кредити директно.
        </p>

        <p>
            © {{ date('Y') }} КредитЗона
        </p>

    </div>
</footer>

</body>
</html>