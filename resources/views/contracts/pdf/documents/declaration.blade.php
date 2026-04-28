@php
    $company = $derived['company'];
@endphp

<div class="title">ДЕКЛАРАЦИЯ</div>

<p>от</p>
<p><strong>{{ $submitted['client']['full_name'] }}</strong>, ЕГН <strong>{{ $submitted['client']['egn'] }}</strong></p>
<p>с адрес: <strong>{{ $submitted['client']['permanent_address'] }}</strong></p>
<p>и</p>
<p><strong>{{ $submitted['co_applicant']['full_name'] }}</strong>, ЕГН <strong>{{ $submitted['co_applicant']['egn'] }}</strong></p>
<p>с адрес: <strong>{{ $submitted['co_applicant']['permanent_address'] }}</strong></p>

<p>С подписването на настоящата декларация, заявяваме следното:</p>
<p>● Запознати сме и приемаме общите условия на „КЕШ БРОКЕР" ЕООД, с ЕИК 206604988, със седалище и адрес на управление: гр. Пловдив, ул. „Полк. Сава Муткуров" 30, представлявано от управителя Дима Илчева, детайлно описани в https://creditzona.bg/;</p>
<p>● Разбираме, че {{ $company['full_identity'] }}, в качеството му на изпълнител, и <strong>{{ $submitted['client']['full_name'] }}</strong>, ЕГН <strong>{{ $submitted['client']['egn'] }}</strong>, в качеството му на възложител по Договор за консултантска услуга (наричан по-долу за краткост Договора), се договарят изпълнителят да предостави услуга, подробно описана в чл. 1 от Договора, включваща: финансово консултантска помощ и съдействие;</p>
<p>● Съгласни сме с предмета на договаряне и съзнаваме, че след изпълнение на предоставената услуга и заплащане на дължимата от възложителя по Договора комисионна, дружеството изпълнител не поема ангажименти и не носи отговорност в отношенията между нас, включително и договорни такива.</p>

<div class="signature-row">
    <p>Дата: {{ $derived['dates']['declaration_date_formatted'] }}</p>
    <p>ДЕКЛАРАТОР:</p>
    <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
    <p>/{{ $submitted['client']['full_name'] }}/</p>
</div>

<div class="signature-row">
    <p>ДЕКЛАРАТОР:</p>
    <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
    <p>/{{ $submitted['co_applicant']['full_name'] }}/</p>
</div>
