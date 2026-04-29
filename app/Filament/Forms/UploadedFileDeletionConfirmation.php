<?php

namespace App\Filament\Forms;

use Illuminate\Support\Js;

class UploadedFileDeletionConfirmation
{
    private const DEFAULT_MESSAGE = 'Сигурни ли сте, че искате да изтриете този документ?';

    /**
     * @return array<string, string>
     */
    public static function alpineAttributes(?string $message = null): array
    {
        $messageExpression = Js::from($message ?? self::DEFAULT_MESSAGE);

        return [
            'x-init' => <<<JS
                const setUploadedFileDeletionConfirmation = () => {
                    if (! pond) {
                        return;
                    }

                    pond.setOptions({
                        beforeRemoveFile: () => window.confirm({$messageExpression}),
                    });
                };

                setUploadedFileDeletionConfirmation();

                \$watch('pond', () => setUploadedFileDeletionConfirmation());
                JS,
        ];
    }
}
