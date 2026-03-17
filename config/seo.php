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
        'default_title' => 'Кредитен консултант в Пловдив',
        'default_description' => 'Кредит Зона предлага консултация за кредити в Пловдив и онлайн с ясен анализ, реалистични насоки и човешки подход.',
        'default_keywords' => [
            'кредити',
            'консултация за кредити',
            'кредитен консултант',
            'кредитен консултант Пловдив',
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
            'title' => 'Кредитен консултант и консултация за кредити в Пловдив',
            'description' => 'Получете ясна консултация за кредити в Пловдив с анализ на ситуацията, реалистични насоки и ясен план за следващите стъпки.',
            'keywords' => [
                'консултация за кредити',
                'кредитен консултант Пловдив',
                'кредити Пловдив',
            ],
            'h1' => 'Консултация за кредити в Пловдив с ясен план за действие',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'breadcrumbs' => [],
            'preload_images' => ['/images/formBG.jpg'],
            'sitemap' => true,
        ],
        'about' => [
            'title' => 'За нас',
            'description' => 'Научете повече за подхода на Кредит Зона при кредитна консултация, анализ на случая и избор на по-ясна финансова посока.',
            'keywords' => [
                'кредитен консултант',
                'кредитен брокер Пловдив',
                'консултация за кредит',
            ],
            'h1' => 'За нас и начина, по който подхождаме към кредитната консултация',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'За нас', 'route' => 'about'],
            ],
            'sitemap' => true,
        ],
        'contact' => [
            'title' => 'Контакти',
            'description' => 'Свържете се с Кредит Зона за консултация за кредит в Пловдив. Изпратете запитване, обадете се по телефон или посетете офиса ни.',
            'keywords' => [
                'консултация за кредит Пловдив',
                'кредитен консултант Пловдив',
                'контакти кредитен консултант',
            ],
            'h1' => 'Контакти за консултация за кредит в Пловдив',
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
            'description' => 'Отговори на често задавани въпроси за кредитна консултация, кандидатстване, документи и следващи стъпки.',
            'keywords' => [
                'често задавани въпроси за кредит',
                'въпроси за кредитна консултация',
                'кандидатстване за кредит въпроси',
            ],
            'h1' => 'Често задавани въпроси за кредити и консултация',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'breadcrumbs' => [
                ['label' => 'Начало', 'route' => 'home'],
                ['label' => 'Често задавани въпроси', 'route' => 'faq'],
            ],
            'sitemap' => true,
        ],
        'blog' => [
            'title' => 'Блог за кредити и лични финанси',
            'description' => 'Практични статии за кредити, документи, месечна тежест, кредитна история и по-спокоен финансов избор.',
            'keywords' => [
                'блог за кредити',
                'съвети за кредит',
                'кредитна консултация',
            ],
            'h1' => 'Полезни статии за кредити и финансови решения',
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
