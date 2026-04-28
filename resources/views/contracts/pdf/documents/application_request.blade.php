@php
    $company = $derived['company'];
    $requestDate = $derived['dates']['application_request_date_formatted'];
@endphp

<div class="title">МОЛБА</div>
<div class="subtitle">ЗА ПРЕДОСТАВЯНЕ НА КОНСУЛТАНТСКА УСЛУГА И/ИЛИ ПОСРЕДНИЧЕСТВО</div>

<p>До:</p>
<p>{{ $company['full_identity'] }}</p>

<p>
    От: {{ $submitted['client']['full_name'] }}, ЕГН: {{ $submitted['client']['egn'] }}, притежаващ/а лична карта с №
    {{ $submitted['client']['id_card_number'] }}, издадена на {{ \Carbon\CarbonImmutable::parse($submitted['client']['id_card_issued_at'], 'Europe/Sofia')->format('d.m.Y') }} г.,
    от {{ $submitted['client']['id_card_issued_by'] }}, с постоянен адрес: {{ $submitted['client']['permanent_address'] }},
</p>

<p><strong>Уважаеми госпожи/господа,</strong></p>

<p>С настоящата молба, по моя лична инициатива и доброволно, се обръщам към Вас с искане за предоставяне на консултантска услуга и/или посредничество във връзка с евентуално сключване на договор за кредит.</p>
<p>Към момента имам следното финансово състояние и задължения:</p>
<p>Брой активни кредити: {{ $submitted['financial']['active_credit_count'] }}</p>
<p>Общ приблизителен размер на задълженията: {{ data_get($derived, 'financial.liabilities_total.eur.formatted') }} € ({{ data_get($derived, 'financial.liabilities_total.eur.words') }}) /{{ data_get($derived, 'financial.liabilities_total.bgn.formatted') }} лв. ({{ data_get($derived, 'financial.liabilities_total.bgn.words') }})/</p>
<p>Обща месечна погасителна тежест: {{ data_get($derived, 'financial.monthly_repayment_burden.eur.formatted') }} € ({{ data_get($derived, 'financial.monthly_repayment_burden.eur.words') }}) /{{ data_get($derived, 'financial.monthly_repayment_burden.bgn.formatted') }} лв. ({{ data_get($derived, 'financial.monthly_repayment_burden.bgn.words') }})/</p>
<p>Среден месечен нетен доход: {{ data_get($derived, 'financial.monthly_net_income.eur.formatted') }} € ({{ data_get($derived, 'financial.monthly_net_income.eur.words') }}) /{{ data_get($derived, 'financial.monthly_net_income.bgn.formatted') }} лв. ({{ data_get($derived, 'financial.monthly_net_income.bgn.words') }})/</p>
<p>Моля да извършите анализ на финансовото ми състояние и да ми предоставите информация и съдействие за намиране на подходящи възможности за финансиране, съобразени с моите нужди и възможности.</p>

<p>Декларирам, че:</p>
<p>1. Съм наясно, че Вие притежавате необходимата финансова и икономическа експертиза в качеството си на посредник и Вашата услуга е посредничество за сключване на договор за кредит, а не директно отпускане на кредит;</p>
<p>2. Съм информиран(а), че преди сключването на договор за кредит ще ми бъде предоставена пълна преддоговорна информация под формата на Стандартен европейски формуляр (СЕФ), както и необходимите разяснения относно предлаганите продукти и условия;</p>
<p>3. Разбирам, че Вашата услуга не гарантира одобрение или отпускане на кредит, нито постигане на конкретен финансов резултат. Окончателното решение за отпускане на кредит се взема от съответната финансова институция (банкова или небанкова);</p>
<p>4. Съм съгласен(а) да заплатя възнаграждение за предоставената услуга по посредничество, съгласно условията, които ще бъдат писмено уговорени между нас преди евентуалното сключване на договор за кредит;</p>
<p>5. Предоставената от мен информация в настоящата молба е вярна и пълна;</p>
<p>6. Давам изричното си съгласие за обработка на личните ми данни, включително финансови данни, за целите на предоставяне на услугата по кредитно посредничество, извършване на финансов анализ и свързване с потенциални кредитори, в съответствие с Регламент (ЕС) 2016/679 (GDPR) и Закона за защита на личните данни.</p>
<p>Прилагам към настоящата молба всички необходими документи, които сте изискали за извършване на финансовия анализ.</p>

<div class="signature-block">
    <p>{{ $requestDate }}</p>
    <p>С уважение:</p>
    <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
    <p class="small">(трите имена и подпис, положени собственоръчно)</p>
</div>
