<?php

namespace App\Services\Contracts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class BulgarianDateFormatterService
{
    /**
     * @var array<int, string>
     */
    private const DAY_ORDINALS = [
        1 => 'първи',
        2 => 'втори',
        3 => 'трети',
        4 => 'четвърти',
        5 => 'пети',
        6 => 'шести',
        7 => 'седми',
        8 => 'осми',
        9 => 'девети',
        10 => 'десети',
        11 => 'единадесети',
        12 => 'дванадесети',
        13 => 'тринадесети',
        14 => 'четиринадесети',
        15 => 'петнадесети',
        16 => 'шестнадесети',
        17 => 'седемнадесети',
        18 => 'осемнадесети',
        19 => 'деветнадесети',
        20 => 'двадесети',
        21 => 'двадесет и първи',
        22 => 'двадесет и втори',
        23 => 'двадесет и трети',
        24 => 'двадесет и четвърти',
        25 => 'двадесет и пети',
        26 => 'двадесет и шести',
        27 => 'двадесет и седми',
        28 => 'двадесет и осми',
        29 => 'двадесет и девети',
        30 => 'тридесети',
        31 => 'тридесет и първи',
    ];

    /**
     * @var array<int, string>
     */
    private const MONTHS = [
        1 => 'януари',
        2 => 'февруари',
        3 => 'март',
        4 => 'април',
        5 => 'май',
        6 => 'юни',
        7 => 'юли',
        8 => 'август',
        9 => 'септември',
        10 => 'октомври',
        11 => 'ноември',
        12 => 'декември',
    ];

    public function __construct(
        private readonly BulgarianNumberToWordsService $numberToWords,
    ) {
    }

    public function format(CarbonInterface|string|null $date, string $timezone = 'Europe/Sofia'): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return $this->toImmutable($date, $timezone)->format('d.m.Y');
    }

    public function spellOut(CarbonInterface|string|null $date, string $timezone = 'Europe/Sofia'): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $resolvedDate = $this->toImmutable($date, $timezone);
        $day = self::DAY_ORDINALS[$resolvedDate->day] ?? $resolvedDate->format('d');
        $month = self::MONTHS[$resolvedDate->month] ?? $resolvedDate->format('m');
        $year = $this->spellOutYear($resolvedDate->year);

        return trim("{$day} {$month} {$year} година");
    }

    private function spellOutYear(int $year): string
    {
        if (($year >= 2000) && ($year <= 2099)) {
            $remainder = $year - 2000;

            if ($remainder === 0) {
                return 'двехилядна';
            }

            if ($remainder < 10) {
                return 'две хиляди и '.$this->spellOutOrdinalFeminine($remainder);
            }

            return 'две хиляди '.$this->spellOutOrdinalFeminine($remainder);
        }

        return $this->numberToWords->spellOut($year, BulgarianNumberToWordsService::GENDER_FEMININE);
    }

    private function spellOutOrdinalFeminine(int $number): string
    {
        return match ($number) {
            1 => 'първа',
            2 => 'втора',
            3 => 'трета',
            4 => 'четвърта',
            5 => 'пета',
            6 => 'шеста',
            7 => 'седма',
            8 => 'осма',
            9 => 'девета',
            10 => 'десета',
            11 => 'единадесета',
            12 => 'дванадесета',
            13 => 'тринадесета',
            14 => 'четиринадесета',
            15 => 'петнадесета',
            16 => 'шестнадесета',
            17 => 'седемнадесета',
            18 => 'осемнадесета',
            19 => 'деветнадесета',
            20 => 'двадесета',
            21 => 'двадесет и първа',
            22 => 'двадесет и втора',
            23 => 'двадесет и трета',
            24 => 'двадесет и четвърта',
            25 => 'двадесет и пета',
            26 => 'двадесет и шеста',
            27 => 'двадесет и седма',
            28 => 'двадесет и осма',
            29 => 'двадесет и девета',
            30 => 'тридесета',
            31 => 'тридесет и първа',
            32 => 'тридесет и втора',
            33 => 'тридесет и трета',
            34 => 'тридесет и четвърта',
            35 => 'тридесет и пета',
            36 => 'тридесет и шеста',
            37 => 'тридесет и седма',
            38 => 'тридесет и осма',
            39 => 'тридесет и девета',
            40 => 'четиридесета',
            41 => 'четиридесет и първа',
            42 => 'четиридесет и втора',
            43 => 'четиридесет и трета',
            44 => 'четиридесет и четвърта',
            45 => 'четиридесет и пета',
            46 => 'четиридесет и шеста',
            47 => 'четиридесет и седма',
            48 => 'четиридесет и осма',
            49 => 'четиридесет и девета',
            50 => 'петдесета',
            51 => 'петдесет и първа',
            52 => 'петдесет и втора',
            53 => 'петдесет и трета',
            54 => 'петдесет и четвърта',
            55 => 'петдесет и пета',
            56 => 'петдесет и шеста',
            57 => 'петдесет и седма',
            58 => 'петдесет и осма',
            59 => 'петдесет и девета',
            60 => 'шестдесета',
            61 => 'шестдесет и първа',
            62 => 'шестдесет и втора',
            63 => 'шестдесет и трета',
            64 => 'шестдесет и четвърта',
            65 => 'шестдесет и пета',
            66 => 'шестдесет и шеста',
            67 => 'шестдесет и седма',
            68 => 'шестдесет и осма',
            69 => 'шестдесет и девета',
            70 => 'седемдесета',
            71 => 'седемдесет и първа',
            72 => 'седемдесет и втора',
            73 => 'седемдесет и трета',
            74 => 'седемдесет и четвърта',
            75 => 'седемдесет и пета',
            76 => 'седемдесет и шеста',
            77 => 'седемдесет и седма',
            78 => 'седемдесет и осма',
            79 => 'седемдесет и девета',
            80 => 'осемдесета',
            81 => 'осемдесет и първа',
            82 => 'осемдесет и втора',
            83 => 'осемдесет и трета',
            84 => 'осемдесет и четвърта',
            85 => 'осемдесет и пета',
            86 => 'осемдесет и шеста',
            87 => 'осемдесет и седма',
            88 => 'осемдесет и осма',
            89 => 'осемдесет и девета',
            90 => 'деветдесета',
            91 => 'деветдесет и първа',
            92 => 'деветдесет и втора',
            93 => 'деветдесет и трета',
            94 => 'деветдесет и четвърта',
            95 => 'деветдесет и пета',
            96 => 'деветдесет и шеста',
            97 => 'деветдесет и седма',
            98 => 'деветдесет и осма',
            99 => 'деветдесет и девета',
            default => $this->numberToWords->spellOut($number, BulgarianNumberToWordsService::GENDER_FEMININE),
        };
    }

    private function toImmutable(CarbonInterface|string $date, string $timezone): CarbonImmutable
    {
        if ($date instanceof CarbonInterface) {
            return CarbonImmutable::instance($date)->setTimezone($timezone)->startOfDay();
        }

        return CarbonImmutable::parse($date, $timezone)->startOfDay();
    }
}
