<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Получихме Вашата заявка</title>
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
                                Получихме Вашата заявка успешно
                            </h1>

                            <p
                                style="margin: 12px 0 0; font-size: 14px; line-height: 1.6; color: rgba(255,255,255,0.88);">
                                Благодарим Ви, че се свързахте с нас. Наш консултант ще прегледа заявката и ще се
                                свърже с Вас до 48 часа.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 28px 32px;">
                            <p style="margin: 0 0 16px; font-size: 16px; line-height: 1.7; color: #111827;">
                                Здравейте{{ filled($lead->first_name) ? ', '.$lead->first_name : '' }},
                            </p>

                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.8; color: #374151;">
                                Потвърждаваме, че получихме Вашата заявка за
                                <strong>{{ mb_strtolower(\App\Models\Lead::getCreditTypeLabel($lead->credit_type), 'UTF-8') }}</strong>.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin: 20px 0; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 16px 18px; background-color: #f9fafb; width: 42%; font-size: 13px; font-weight: 700; color: #6b7280;">
                                        Име
                                    </td>
                                    <td style="padding: 16px 18px; font-size: 15px; color: #111827;">
                                        {{ trim(implode(' ', array_filter([$lead->first_name, $lead->middle_name, $lead->last_name]))) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 18px; background-color: #f9fafb; width: 42%; font-size: 13px; font-weight: 700; color: #6b7280;">
                                        Телефон
                                    </td>
                                    <td style="padding: 16px 18px; font-size: 15px; color: #111827;">
                                        {{ $lead->phone }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 18px; background-color: #f9fafb; width: 42%; font-size: 13px; font-weight: 700; color: #6b7280;">
                                        Град
                                    </td>
                                    <td style="padding: 16px 18px; font-size: 15px; color: #111827;">
                                        {{ $lead->city ?: 'Няма' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 18px; background-color: #f9fafb; width: 42%; font-size: 13px; font-weight: 700; color: #6b7280;">
                                        Желана сума
                                    </td>
                                    <td style="padding: 16px 18px; font-size: 15px; color: #111827;">
                                        {{ number_format((int) $lead->amount, 0, ',', ' ') }} €
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 15px; line-height: 1.8; color: #374151;">
                                Няма нужда да правите нищо допълнително на този етап. Ако сте изпратили заявката по
                                погрешка, просто можете да игнорирате това писмо.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 32px; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #6b7280;">
                                Това е автоматично потвърждение за получена заявка от сайта на CreditZona.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
