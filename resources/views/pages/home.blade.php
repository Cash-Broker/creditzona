@extends('layouts.app')

@section('title', 'КредитЗона')

@section('content')
    <div class="grid lg:grid-cols-2 gap-10 items-start">
        {{-- HERO --}}
        <div class="pt-4">
            <div class="inline-flex items-center gap-2 rounded-full bg-yellow-50 px-4 py-2 text-sm text-yellow-900 border border-yellow-200">
                <span class="font-semibold">Кредитни консултанти</span>
                <span class="text-yellow-700">•</span>
                <span>Пловдив и онлайн</span>
            </div>

            <h1 class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">
                По-бързо към правилната кредитна оферта
            </h1>

            <p class="mt-4 text-lg text-gray-600">
                Насочваме ви към подходяща опция според профила ви и помагаме с документацията.
                <span class="font-medium text-gray-800">Не отпускaме кредити директно.</span>
            </p>

            <div class="mt-6 grid sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Отговор</div>
                    <div class="text-xl font-bold">в рамките на деня*</div>
                </div>
                <div class="bg-white rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Процес</div>
                    <div class="text-xl font-bold">ясни стъпки</div>
                </div>
                <div class="bg-white rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Контакт</div>
                    <div class="text-xl font-bold">телефон + форма</div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('service.consumer') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-yellow-500 px-5 py-3 font-semibold text-black hover:bg-yellow-400 transition">
                    Потребителски кредит
                </a>

                <a href="{{ route('service.mortgage') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 font-semibold text-gray-900 border hover:bg-gray-50 transition">
                    Ипотечен кредит
                </a>

                <a href="{{ route('contact') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 font-semibold text-gray-900 border hover:bg-gray-50 transition">
                    Контакти
                </a>
            </div>

            <p class="mt-3 text-xs text-gray-500">
                * Текстът е примерен — смени го с реалното им SLA, за да не ги вкараш в обещания.
            </p>
        </div>

        {{-- QUICK FORM CARD --}}
        <div class="bg-white border rounded-2xl shadow-sm p-6">
            <h2 class="text-xl font-bold">Безплатна консултация</h2>
            <p class="text-gray-600 mt-1">
                Оставете данни и ще се свържем с вас.
            </p>

            <div class="mt-5">
                @include('partials.lead-form-compact')
            </div>

            <div class="mt-4 text-xs text-gray-500">
                С изпращане на формата потвърждавате, че данните ще се използват само за целите на консултацията.
            </div>
        </div>
    </div>

    {{-- SERVICES --}}
    <div class="mt-16">
        <h2 class="text-2xl font-bold mb-6">Услуги</h2>

        <div class="grid md:grid-cols-3 gap-6">
            <a href="{{ route('service.consumer') }}" class="bg-white border rounded-2xl p-6 hover:shadow-sm transition">
                <h3 class="text-lg font-semibold">Потребителски кредит</h3>
                <p class="text-gray-600 mt-2">Бърза консултация и насочване.</p>
                <div class="mt-4 text-yellow-700 font-semibold">Виж повече →</div>
            </a>

            <a href="{{ route('service.mortgage') }}" class="bg-white border rounded-2xl p-6 hover:shadow-sm transition">
                <h3 class="text-lg font-semibold">Ипотечен кредит</h3>
                <p class="text-gray-600 mt-2">Път през документите и процеса.</p>
                <div class="mt-4 text-yellow-700 font-semibold">Виж повече →</div>
            </a>

            <a href="{{ route('service.refinance') }}" class="bg-white border rounded-2xl p-6 hover:shadow-sm transition">
                <h3 class="text-lg font-semibold">Рефинансиране</h3>
                <p class="text-gray-600 mt-2">Опит за по-добри условия.</p>
                <div class="mt-4 text-yellow-700 font-semibold">Виж повече →</div>
            </a>
        </div>
    </div>
@endsection