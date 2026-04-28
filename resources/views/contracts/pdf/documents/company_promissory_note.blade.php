@php
    $company = $derived['company'];
@endphp

<div class="title">ЗАПИС НА ЗАПОВЕД</div>

<p>Подписаният:</p>
<p>{{ $derived['identities']['client'] }}, в качеството на <strong>ИЗДАТЕЛ</strong>,</p>
<p>С НАСТОЯЩИЯ "ЗАПИС НА ЗАПОВЕД", <strong>ИЗДАТЕЛЯТ</strong> СЕ ЗАДЪЛЖАВА НЕОТМЕНИМО И БЕЗУСЛОВНО ДА ЗАПЛАТИ НА <strong>ПОЕМАТЕЛЯ</strong></p>
<p>СУМАТА ОТ {{ data_get($derived, 'financial.company_promissory_note_amount.eur.formatted') }} € ({{ data_get($derived, 'financial.company_promissory_note_amount.eur.words') }}) /{{ data_get($derived, 'financial.company_promissory_note_amount.bgn.formatted') }} лв. ({{ data_get($derived, 'financial.company_promissory_note_amount.bgn.words') }})/</p>

<p>Падеж:</p>
<p>Настоящият „Запис на заповед" е платим на {{ $derived['dates']['company_promissory_note_due_date_formatted'] }} г.</p>
<p>(словом: {{ $derived['dates']['company_promissory_note_due_date_words'] }})</p>

<p>Място на плащане:</p>
<p>гр. Пловдив, по банкова сметка на <strong>ПОЕМАТЕЛЯ</strong>,</p>

<p><strong>ПОЕМАТЕЛ</strong>:</p>
<p>{{ $company['full_identity'] }}</p>

<p>Място и дата на издаване:</p>
<p>гр. Пловдив, {{ $derived['dates']['company_promissory_note_issue_date_formatted'] }} г. ({{ $derived['dates']['company_promissory_note_issue_date_words'] }})</p>

<p>Допълнителна клауза:</p>
<p>„БЕЗ ПРОТЕСТ", съгласно чл. 500, ал. 1 от Търговския закон на Република България.</p>

<div class="signature-row">
    <p><strong>ИЗДАТЕЛ</strong>:</p>
    <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
    <p class="small">(трите имена - собственоръчно) (подпис)</p>
</div>

@if (filled($submitted['co_applicant']['full_name']))
    <div style="margin-top: 18px; page-break-inside: avoid;">
        <p>Подписаният:</p>
        <p>{{ $derived['identities']['co_applicant'] }}</p>
        <p>в качеството си на ПОРЪЧИТЕЛ /авалист/, поръчителствам за изпълнението на задълженията на {{ $submitted['client']['full_name'] }} по настоящия Запис на заповед.</p>
        <p>ПОРЪЧИТЕЛ/АВАЛИСТ:</p>
        <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
        <p class="small">(трите имена - собственоръчно) (подпис)</p>
    </div>
@endif
