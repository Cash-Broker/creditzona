<?php

namespace App\Services\Contracts;

class BulgarianNumberToWordsService
{
    public const GENDER_MASCULINE = 'masculine';

    public const GENDER_FEMININE = 'feminine';

    public const GENDER_NEUTER = 'neuter';

    private const UNITS = [
        self::GENDER_MASCULINE => [
            0 => 'нула',
            1 => 'един',
            2 => 'два',
            3 => 'три',
            4 => 'четири',
            5 => 'пет',
            6 => 'шест',
            7 => 'седем',
            8 => 'осем',
            9 => 'девет',
        ],
        self::GENDER_FEMININE => [
            0 => 'нула',
            1 => 'една',
            2 => 'две',
            3 => 'три',
            4 => 'четири',
            5 => 'пет',
            6 => 'шест',
            7 => 'седем',
            8 => 'осем',
            9 => 'девет',
        ],
        self::GENDER_NEUTER => [
            0 => 'нула',
            1 => 'едно',
            2 => 'две',
            3 => 'три',
            4 => 'четири',
            5 => 'пет',
            6 => 'шест',
            7 => 'седем',
            8 => 'осем',
            9 => 'девет',
        ],
    ];

    private const TEENS = [
        10 => 'десет',
        11 => 'единадесет',
        12 => 'дванадесет',
        13 => 'тринадесет',
        14 => 'четиринадесет',
        15 => 'петнадесет',
        16 => 'шестнадесет',
        17 => 'седемнадесет',
        18 => 'осемнадесет',
        19 => 'деветнадесет',
    ];

    private const TENS = [
        2 => 'двадесет',
        3 => 'тридесет',
        4 => 'четиридесет',
        5 => 'петдесет',
        6 => 'шестдесет',
        7 => 'седемдесет',
        8 => 'осемдесет',
        9 => 'деветдесет',
    ];

    private const HUNDREDS = [
        1 => 'сто',
        2 => 'двеста',
        3 => 'триста',
        4 => 'четиристотин',
        5 => 'петстотин',
        6 => 'шестстотин',
        7 => 'седемстотин',
        8 => 'осемстотин',
        9 => 'деветстотин',
    ];

    public function spellOut(int|float|string $number, string $gender = self::GENDER_MASCULINE): string
    {
        $normalized = $this->normalizeNumber($number);
        $integer = (int) floor(abs($normalized));
        $words = $this->convertInteger($integer, $gender);

        return $normalized < 0 ? 'минус '.$words : $words;
    }

    public function spellOutMoney(
        int|float|string $amount,
        string $majorSingular,
        string $majorPlural,
        string $majorGender = self::GENDER_MASCULINE,
        string $minorSingular = 'стотинка',
        string $minorPlural = 'стотинки',
        string $minorGender = self::GENDER_FEMININE,
    ): string {
        $totalMinorUnits = (int) round(abs($this->normalizeNumber($amount)) * 100);
        $major = intdiv($totalMinorUnits, 100);
        $minor = $totalMinorUnits % 100;

        $majorWords = $this->convertInteger($major, $majorGender).' '.($major === 1 ? $majorSingular : $majorPlural);

        if ($minor === 0) {
            return $majorWords;
        }

        $minorWords = $this->convertInteger($minor, $minorGender).' '.($minor === 1 ? $minorSingular : $minorPlural);

        return $majorWords.' и '.$minorWords;
    }

    private function normalizeNumber(int|float|string $number): float
    {
        if (is_int($number) || is_float($number)) {
            return (float) $number;
        }

        $normalized = str_replace([' ', ','], ['', '.'], trim($number));

        return (float) $normalized;
    }

    private function convertInteger(int $number, string $gender): string
    {
        if ($number === 0) {
            return self::UNITS[self::GENDER_MASCULINE][0];
        }

        if ($number < 1000) {
            return $this->convertBelowThousand($number, $gender);
        }

        if ($number < 1_000_000) {
            $thousands = intdiv($number, 1000);
            $remainder = $number % 1000;
            $prefix = $thousands === 1
                ? 'хиляда'
                : $this->convertBelowThousand($thousands, self::GENDER_FEMININE).' хиляди';

            return $this->joinScaleParts($prefix, $remainder, $gender);
        }

        if ($number < 1_000_000_000) {
            $millions = intdiv($number, 1_000_000);
            $remainder = $number % 1_000_000;
            $prefix = $millions === 1
                ? 'един милион'
                : $this->convertInteger($millions, self::GENDER_MASCULINE).' милиона';

            return $this->joinScaleParts($prefix, $remainder, $gender);
        }

        $billions = intdiv($number, 1_000_000_000);
        $remainder = $number % 1_000_000_000;
        $prefix = $billions === 1
            ? 'един милиард'
            : $this->convertInteger($billions, self::GENDER_MASCULINE).' милиарда';

        return $this->joinScaleParts($prefix, $remainder, $gender);
    }

    private function joinScaleParts(string $prefix, int $remainder, string $gender): string
    {
        if ($remainder === 0) {
            return $prefix;
        }

        $separator = ($remainder < 100) || ($remainder % 100 === 0)
            ? ' и '
            : ' ';

        return $prefix.$separator.$this->convertInteger($remainder, $gender);
    }

    private function convertBelowThousand(int $number, string $gender): string
    {
        if ($number < 100) {
            return $this->convertBelowHundred($number, $gender);
        }

        $hundreds = intdiv($number, 100);
        $remainder = $number % 100;

        if ($remainder === 0) {
            return self::HUNDREDS[$hundreds];
        }

        $separator = $remainder < 20 ? ' и ' : ' ';

        return self::HUNDREDS[$hundreds].$separator.$this->convertBelowHundred($remainder, $gender);
    }

    private function convertBelowHundred(int $number, string $gender): string
    {
        if ($number < 10) {
            return self::UNITS[$gender][$number];
        }

        if ($number < 20) {
            return self::TEENS[$number];
        }

        $tens = intdiv($number, 10);
        $units = $number % 10;

        if ($units === 0) {
            return self::TENS[$tens];
        }

        return self::TENS[$tens].' и '.self::UNITS[$gender][$units];
    }
}
