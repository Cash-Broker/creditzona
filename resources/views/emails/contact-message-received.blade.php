<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ново съобщение от контактната форма</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f4f7fb; font-family: Arial, Helvetica, sans-serif; color: #1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color: #f4f7fb; margin: 0; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                    style="max-width: 680px; background-color: #ffffff; border-radius: 18px; overflow: hidden; border: 1px solid #e5e7eb;">

                    <tr>
                        <td
                            style="padding: 28px 32px; background: linear-gradient(135deg, #0f766e, #115e59); color: #ffffff;">
                            <div
                                style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; opacity: 0.9;">
                                CreditZona
                            </div>

                            <h1 style="margin: 10px 0 0; font-size: 24px; line-height: 1.3; font-weight: 700;">
                                Ново съобщение от контактната форма
                            </h1>

                            <p
                                style="margin: 12px 0 0; font-size: 14px; line-height: 1.6; color: rgba(255,255,255,0.88);">
                                Получено е ново запитване от сайта. Данните от формата са обобщени по-долу.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 28px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding: 0 0 16px;">
                                        <div
                                            style="font-size: 13px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;">
                                            Име
                                        </div>
                                        <div style="font-size: 16px; line-height: 1.6; color: #111827;">
                                            {{ $contactMessage->full_name }}
                                        </div>
                                    </td>
                                </tr>

                                @if ($contactMessage->phone)
                                    <tr>
                                        <td style="padding: 0 0 16px;">
                                            <div
                                                style="font-size: 13px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;">
                                                Телефон
                                            </div>
                                            <div style="font-size: 16px; line-height: 1.6; color: #111827;">
                                                <a href="tel:{{ $contactMessage->phone }}"
                                                    style="color: #0f766e; text-decoration: none;">
                                                    {{ $contactMessage->phone }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                                @if ($contactMessage->email)
                                    <tr>
                                        <td style="padding: 0 0 16px;">
                                            <div
                                                style="font-size: 13px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;">
                                                Имейл
                                            </div>
                                            <div style="font-size: 16px; line-height: 1.6; color: #111827;">
                                                <a href="mailto:{{ $contactMessage->email }}"
                                                    style="color: #0f766e; text-decoration: none;">
                                                    {{ $contactMessage->email }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                            </table>

                            <div style="margin-top: 8px; border-top: 1px solid #e5e7eb; padding-top: 24px;">
                                <div
                                    style="font-size: 13px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px;">
                                    Съобщение
                                </div>

                                <div
                                    style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 14px; padding: 18px; font-size: 15px; line-height: 1.8; color: #111827; white-space: pre-line;">
                                    {{ $contactMessage->message }}
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 32px; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #6b7280;">
                                Това съобщение е изпратено автоматично от контактната форма на сайта.
                            </p>

                            @if ($contactMessage->email)
                                <p style="margin: 8px 0 0; font-size: 13px; line-height: 1.6; color: #6b7280;">
                                    Можете да отговорите директно на това писмо и отговорът ще бъде насочен към
                                    подателя.
                                </p>
                            @endif
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>

