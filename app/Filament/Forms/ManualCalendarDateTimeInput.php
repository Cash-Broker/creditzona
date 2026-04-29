<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\DateTimePicker;

class ManualCalendarDateTimeInput
{
    public const DISPLAY_FORMAT = 'd/m/Y H:i';

    public const PLACEHOLDER = 'дд/мм/гггг чч:мм';

    public static function configure(DateTimePicker $field): DateTimePicker
    {
        return $field
            ->seconds(false)
            ->native(false)
            ->displayFormat(self::DISPLAY_FORMAT)
            ->placeholder(self::PLACEHOLDER)
            ->extraAlpineAttributes(self::alpineAttributes(), merge: true);
    }

    /**
     * @return array<string, string>
     */
    public static function alpineAttributes(): array
    {
        return [
            'x-init' => <<<'JS'
                $nextTick(() => {
                    const input = $refs.button?.querySelector('input.fi-fo-date-time-picker-display-text-input');

                    if (! input) {
                        return;
                    }

                    input.removeAttribute('readonly');
                    input.setAttribute('autocomplete', 'off');
                    input.setAttribute('inputmode', 'numeric');
                    input.setAttribute('placeholder', 'дд/мм/гггг чч:мм');
                    input.setAttribute('aria-label', 'Въведете дата във формат ден/месец/година час:минута');

                    const manualFormats = [
                        'DD/MM/YYYY HH:mm',
                        'D/M/YYYY H:mm',
                        'DD/MM/YYYY',
                        'D/M/YYYY',
                    ];

                    const normalizeManualDateTime = (value) => value
                        .trim()
                        .replace(/\s+/g, ' ')
                        .replace(/[-.]/g, '/');

                    const parseManualCalendarDate = () => {
                        const value = normalizeManualDateTime(input.value);

                        if (! value) {
                            input.setCustomValidity('');
                            this.clearState();

                            return;
                        }

                        const parsedDate = manualFormats
                            .map((format) => dayjs(value, format, true))
                            .find((date) => date.isValid());

                        if (! parsedDate) {
                            input.setCustomValidity('Въведете датата във формат дд/мм/гггг чч:мм.');

                            return;
                        }

                        input.setCustomValidity('');

                        this.hour = parsedDate.hour();
                        this.minute = parsedDate.minute();
                        this.second = 0;
                        this.focusedDate = parsedDate;
                        this.setState(parsedDate);
                    };

                    input.addEventListener('blur', parseManualCalendarDate);
                    input.addEventListener('change', parseManualCalendarDate);
                    input.addEventListener('input', () => input.setCustomValidity(''));
                    input.addEventListener('keydown', (event) => {
                        if (event.key !== 'Enter') {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();

                        parseManualCalendarDate();
                    });
                })
                JS,
        ];
    }
}
