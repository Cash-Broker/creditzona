<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
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

                            <h1 style="margin: 10px 0 0; font-size: 22px; line-height: 1.35; font-weight: 700;">
                                Здравейте, {{ $contactMessage->full_name }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 28px 32px;">
                            <div
                                style="white-space: pre-line; font-size: 15px; line-height: 1.7; color: #111827;">{{ $body }}</div>

                            <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                                <div style="font-size: 14px; color: #111827; font-weight: 600;">
                                    {{ $sender->name }}
                                </div>

                                <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
                                    Екип CreditZona
                                </div>

                                <div style="font-size: 13px; margin-top: 4px;">
                                    <a href="mailto:{{ $sender->email }}"
                                        style="color: #0f766e; text-decoration: none;">{{ $sender->email }}</a>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 18px 32px; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #6b7280;">
                                Можете да отговорите директно на това писмо — отговорът ще се получи на личния имейл
                                на {{ $sender->name }}.
                            </p>

                            <p style="margin: 8px 0 0; font-size: 12px; line-height: 1.6; color: #9ca3af;">
                                Във връзка с Ваше запитване от контактната форма на CreditZona.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
