<?php

$sameAs = array_values(array_filter([
    env('SEO_FACEBOOK_URL'),
    env('SEO_INSTAGRAM_URL'),
    env('SEO_LINKEDIN_URL'),
    env('SEO_YOUTUBE_URL'),
]));

return [
    'site' => [
        'name' => env('SEO_SITE_NAME', 'Кредит Зона'),
        'legal_name' => env('SEO_BUSINESS_LEGAL_NAME', 'Кредит Зона БГ ЕООД'),
        'url' => rtrim(env('APP_URL', 'https://creditzona.bg'), '/'),
        'locale' => 'bg_BG',
        'language' => 'bg',
        'title_separator' => '|',
        'default_title' => 'Кредитни консултации онлайн и в Пловдив',
        'default_description' => 'Кредит Зона предлага кредитни консултации онлайн и в Пловдив с ясен анализ, реалистични насоки и човешки подход.',
        'default_keywords' => [
            'кредити',
            'кредитни консултации онлайн',
            'онлайн кредитна консултация',
            'кредитен консултант онлайн',
        ],
        'default_image' => '/images/credit-consultation.jpg',
        'logo' => '/images/logo/logo.png',
        'theme_color' => '#f9fafb',
        'twitter_card' => 'summary_large_image',
    ],

    'business' => [
        'type' => 'FinancialService',
        'name' => env('SEO_SITE_NAME', 'Кредит Зона'),
        'legal_name' => env('SEO_BUSINESS_LEGAL_NAME', 'Кредит Зона БГ ЕООД'),
        'email' => env('SEO_EMAIL', 'office@creditzona.bg'),
        'phone' => env('SEO_PHONE', '+359879000685'),
        'phones' => array_values(array_filter([
            env('SEO_PHONE', '0879000685'),
        ])),
        'street_address' => env('SEO_STREET_ADDRESS', 'ул. Полк. Сава Муткуров 30'),
        'address_locality' => env('SEO_CITY', 'Пловдив'),
        'address_region' => env('SEO_REGION', 'Пловдив'),
        'postal_code' => env('SEO_POSTAL_CODE', '4000'),
        'country' => env('SEO_COUNTRY', 'Bulgaria'),
        'country_code' => env('SEO_COUNTRY_CODE', 'BG'),
        'latitude' => env('SEO_LATITUDE'),
        'longitude' => env('SEO_LONGITUDE'),
        'google_maps_url' => env('SEO_GOOGLE_MAPS_URL', 'https://www.google.com/maps?cid=17488259411281683573'),
        'opening_hours' => [
            [
                'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'opens' => '09:00',
                'closes' => '18:00',
            ],
        ],
        'same_as' => $sameAs,
    ],

    'pages' => [
        'home' => [
            'title' => 'Кредитни консултации онлайн и в Пловдив',
            'description' => 'Получете кредитна консултация онлайн или в Пловдив с анализ на ситуацията, реалистични насоки и ясен план за следващите стъпки.',
            'keywords' => [
                'кредитни консултации онлайн',
                'онлайн кредитна консултация',
                'кредитен консултант Пловдив',
            ],
            'h1' => 'Кредитни консултации онлайн с ясен план за действие',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'breadcrumbs' => [],
            'preload_images' => ['/images/bg-image.png'],
            'sitemap' => true,
        ],
        'about' => [
            'title' => 'За нас и кредитните консултации онлайн',
            'description' => 'Научете повече за подхода на Кредит Зона при кредитни консултации онлайн, анализ на случая и избор на по-ясна финансова посока.',
            'keywords' => [
                'кредитни консултации онлайн',
                'кредитен консултант онлайн',
                'консултация за кредит',
            ],
            'h1' => 'За нас и начина, по който подхождаме към кредитните консултации онлайн',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'За нас', 'route' => 'about'],
            ],
            'sitemap' => true,
        ],
        'contact' => [
            'title' => 'Контакти за кредитни консултации онлайн',
            'description' => 'Свържете се с Кредит Зона за кредитни консултации онлайн и в Пловдив. Изпратете запитване, обадете се по телефон или посетете офиса ни.',
            'keywords' => [
                'кредитни консултации онлайн',
                'консултация за кредит онлайн',
                'контакти кредитен консултант',
            ],
            'h1' => 'Контакти за кредитни консултации онлайн и в Пловдив',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'Контакти', 'route' => 'contact'],
            ],
            'sitemap' => true,
        ],
        'faq' => [
            'title' => 'Често задавани въпроси',
            'description' => 'Отговори на често задавани въпроси за кредитни консултации онлайн, кандидатстване, документи и следващи стъпки.',
            'keywords' => [
                'кредитни консултации онлайн',
                'въпроси за кредитна консултация',
                'кандидатстване за кредит въпроси',
            ],
            'h1' => 'Често задавани въпроси за кредити и кредитни консултации онлайн',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'Често задавани въпроси', 'route' => 'faq'],
            ],
            'sitemap' => true,
        ],
        'blog' => [
            'title' => 'Блог за кредити и кредитни консултации онлайн',
            'description' => 'Практични статии за кредити, документи, месечна тежест, кредитна история и онлайн кредитни консултации.',
            'keywords' => [
                'блог за кредити',
                'съвети за кредит',
                'кредитни консултации онлайн',
            ],
            'h1' => 'Полезни статии за кредити и онлайн кредитни консултации',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'Блог', 'route' => 'blog'],
            ],
            'sitemap' => true,
        ],
        'blog_show' => [
            'title' => 'Статия',
            'description' => 'Практична статия от блога на Кредит Зона.',
            'robots' => 'index,follow',
            'og_type' => 'article',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'Блог', 'route' => 'blog'],
            ],
            'sitemap' => false,
        ],
        'privacy_policy' => [
            'title' => 'Политика за поверителност',
            'description' => 'Политика за поверителност на Кредит Зона.',
            'robots' => 'noindex,follow',
            'og_type' => 'website',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'Политика за поверителност', 'route' => 'privacy'],
            ],
            'sitemap' => false,
        ],
        'cookie_policy' => [
            'title' => 'Политика за бисквитки',
            'description' => 'Политика за бисквитки на Кредит Зона.',
            'robots' => 'noindex,follow',
            'og_type' => 'website',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'Политика за бисквитки', 'route' => 'cookies'],
            ],
            'sitemap' => false,
        ],
        'terms' => [
            'title' => 'Общи условия',
            'description' => 'Общи условия на Кредит Зона.',
            'robots' => 'noindex,follow',
            'og_type' => 'website',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'Общи условия', 'route' => 'terms'],
            ],
            'sitemap' => false,
        ],
    ],
];
