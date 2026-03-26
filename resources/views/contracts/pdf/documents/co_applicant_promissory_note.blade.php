<div class="title">ЗАПИС НА ЗАПОВЕД</div>

<p>Подписаният:</p>
<p>{{ $derived['identities']['client'] }}, в качеството на ИЗДАТЕЛ,</p>
<p>С НАСТОЯЩИЯТ “ЗАПИС НА ЗАПОВЕД” ИЗДАТЕЛЯТ СЕ ЗАДЪЛЖАВА БЕЗУСЛОВНО И НЕОТМЕНИМО ДА ЗАПЛАТИ {{ data_get($derived, 'financial.co_applicant_promissory_note_amount.eur.formatted') }} € ({{ data_get($derived, 'financial.co_applicant_promissory_note_amount.eur.words') }}) /{{ data_get($derived, 'financial.co_applicant_promissory_note_amount.bgn.formatted') }} лв./</p>

<p>Падеж:</p>
<p>Настоящият “Запис на заповед” е платим на {{ $derived['dates']['co_applicant_promissory_note_due_date_formatted'] }} г.</p>
<p>(словом: {{ $derived['dates']['co_applicant_promissory_note_due_date_words'] }})</p>

<p>Място на плащане:</p>
<p>гр. Пловдив, по банкова сметка на ПОЕМАТЕЛЯ;</p>

<p>Поемател:</p>
<p>{{ $derived['identities']['co_applicant'] }}</p>

<p>Място и дата на издаване:</p>
<p>гр. Пловдив, {{ $derived['dates']['co_applicant_promissory_note_issue_date_formatted'] }} г. ({{ $derived['dates']['co_applicant_promissory_note_issue_date_words'] }})</p>

<p>Допълнителна клауза:</p>
<p>“БЕЗ ПРОТЕСТ”, съгласно чл. 500, ал. 1 от Търговския закон на РБ.</p>

<div class="signature-row">
    <p>ИЗДАТЕЛ: __________________________________________________________________</p>
    <p class="small">(подпис и трите имена - собственоръчно)</p>
</div>
