<?php

namespace App\Services\Contracts;

class CurrencyFormatterService
{
    public const EUR_TO_BGN_RATE = 1.95583;

    public function __construct(
        private readonly BulgarianNumberToWordsService $numberToWords,
    ) {
    }

    public function convertEurToBgn(int|float|string|null $amount): ?float
    {
        $normalized = $this->normalizeNullableAmount($amount);

        if ($normalized === null) {
            return null;
        }

        return round($normalized * self::EUR_TO_BGN_RATE, 2);
    }

    public function describeEuroAmount(int|float|string|null $amount): ?array
    {
        $normalized = $this->normalizeNullableAmount($amount);

        if ($normalized === null) {
            return null;
        }

        return [
            'amount' => $normalized,
            'formatted' => $this->formatDecimal($normalized),
            'words' => $this->numberToWords->spellOutMoney(
                $normalized,
                'евро',
                'евро',
                BulgarianNumberToWordsService::GENDER_NEUTER,
                'цент',
                'цента',
                BulgarianNumberToWordsService::GENDER_MASCULINE,
            ),
        ];
    }

    public function describeBgnAmount(int|float|string|null $amount): ?array
    {
        $normalized = $this->normalizeNullableAmount($amount);

        if ($normalized === null) {
            return null;
        }

        return [
            'amount' => $normalized,
            'formatted' => $this->formatDecimal($normalized),
            'words' => $this->numberToWords->spellOutMoney(
                $normalized,
                'лев',
                'лева',
                BulgarianNumberToWordsService::GENDER_MASCULINE,
                'стотинка',
                'стотинки',
                BulgarianNumberToWordsService::GENDER_FEMININE,
            ),
        ];
    }

    public function describeEurWithBgnEquivalent(int|float|string|null $amount): ?array
    {
        $euro = $this->describeEuroAmount($amount);

        if ($euro === null) {
            return null;
        }

        return [
            'eur' => $euro,
            'bgn' => $this->describeBgnAmount($this->convertEurToBgn($amount)),
        ];
    }

    public function formatDecimal(int|float|string $amount): string
    {
        $normalized = is_string($amount)
            ? (float) str_replace([' ', ','], ['', '.'], trim($amount))
            : (float) $amount;

        return number_format($normalized, 2, ',', ' ');
    }

    private function normalizeNullableAmount(int|float|string|null $amount): ?float
    {
        if ($amount === null) {
            return null;
        }

        if (is_string($amount) && trim($amount) === '') {
            return null;
        }

        $normalized = is_string($amount)
            ? (float) str_replace([' ', ','], ['', '.'], trim($amount))
            : (float) $amount;

        return round($normalized, 2);
    }
}
