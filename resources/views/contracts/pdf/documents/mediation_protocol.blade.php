@php
    $company = $derived['company'];
@endphp

<div class="title">ПРИЕМО-ПРЕДАВАТЕЛЕН ПРОТОКОЛ</div>
<div class="subtitle">към Договор за посредничество при обединяване и редуциране на потребителски кредити от {{ $derived['dates']['mediation_contract_date_formatted'] }} г.</div>

<p>Днес, {{ $derived['dates']['mediation_protocol_date_formatted'] }} г., в гр. Пловдив, се състави и подписа настоящият протокол между:</p>
<p>{{ $derived['identities']['client'] }}, наричан/а по-долу <strong>ВЪЗЛОЖИТЕЛ</strong>,</p>
@if (filled($submitted['co_applicant']['full_name']))
    <p>и {{ $derived['identities']['co_applicant'] }}, наричан/а по-долу <strong>СЪКРЕДИТОИСКАТЕЛ</strong>,</p>
@endif
<p>от една страна,</p>
<p>и</p>
<p>{{ $company['full_identity'] }}, наричано по-долу <strong>ПОСРЕДНИК</strong>,</p>
<p>от друга страна.</p>
<p>СТРАНИТЕ УДОСТОВЕРЯВАТ СЛЕДНОТО:</p>

<p>Чл. 1. (1) В изпълнение на сключения между страните Договор за посредничество, <strong>ПОСРЕДНИКЪТ</strong> предостави на <strong>ВЪЗЛОЖИТЕЛЯ</strong> @if (filled($submitted['co_applicant']['full_name'])) (и <strong>СЪКРЕДИТОИСКАТЕЛЯ</strong>, когато е приложимо) @endif качествена посредническа услуга, включваща анализ на финансовото състояние, проучване на пазара, съдействие при подготовка на документи и подпомагане на преговори с кредитиращи институции.</p>
<p>(2) Услугата е извършена с грижата на добрия търговец, в съответствие с предмета на договора и законовите изисквания.</p>
<p>Чл. 2. <strong>ВЪЗЛОЖИТЕЛЯТ</strong> @if (filled($submitted['co_applicant']['full_name'])) (и <strong>СЪКРЕДИТОИСКАТЕЛЯТ</strong>, когато е приложимо) @endif декларира, че е прегледал и приема извършената услуга без възражения и забележки относно нейното качество и обем. С подписването на този протокол услугата се счита за приета.</p>
<p>Чл. 3. (1) Страните констатират, че в резултат на извършеното от <strong>ПОСРЕДНИКА</strong> посредничество, на {{ $derived['dates']['mediation_protocol_date_formatted'] }} г. е сключен Договор за потребителски кредит № {{ $submitted['loan']['credit_agreement_number'] }} между <strong>ВЪЗЛОЖИТЕЛЯ</strong> @if (filled($submitted['co_applicant']['full_name'])) (и <strong>СЪКРЕДИТОИСКАТЕЛЯ</strong>) @endif и <strong>КРЕДИТОРА</strong> {{ $submitted['loan']['institution_name'] }}.</p>
<p>(2) С подписването на настоящия протокол страните потвърждават, че размерът на дължимото възнаграждение на <strong>ПОСРЕДНИКА</strong> е бил предварително уговорен в сключения между тях Договор за посредничество, преди подписването на този протокол.</p>
<p>Чл. 4. Настоящият протокол се състави и подписа в два еднообразни екземпляра, по един за всяка от страните, и представлява неразделна част от Договора за посредничество.</p>

<div style="margin-top: 30px;">
    <p>ЗА <strong>ВЪЗЛОЖИТЕЛЯ</strong>:</p>
    <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
</div>
@if (filled($submitted['co_applicant']['full_name']))
    <div style="margin-top: 30px;">
        <p>ЗА <strong>СЪКРЕДИТОИСКАТЕЛЯ</strong> (ако е приложимо):</p>
    <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
    </div>
@endif
<div style="margin-top: 30px;">
    <p>ЗА <strong>ПОСРЕДНИКА</strong>:</p>
    <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
</div>

<div class="declaration-box" style="border: 1px solid #4472c4; padding: 15px;">
    <p><strong>Възложителят</strong></p>
    <p>Декларирам, че преди извършването на услугата по договора за посредничество имам {{ $submitted['financial']['active_credit_count'] }} броя кредити с обща месечна вноска от {{ data_get($derived, 'financial.monthly_repayment_burden.eur.formatted') }} € ({{ data_get($derived, 'financial.monthly_repayment_burden.eur.words') }}) /{{ data_get($derived, 'financial.monthly_repayment_burden.bgn.formatted') }} лв. ({{ data_get($derived, 'financial.monthly_repayment_burden.bgn.words') }})/. След извършения от <strong>ПОСРЕДНИКА</strong> подробен финансов анализ и предоставеното съдействие, месечната вноска се е редуцирала до {{ data_get($derived, 'financial.post_service_monthly_repayment_burden.eur.formatted') }} € ({{ data_get($derived, 'financial.post_service_monthly_repayment_burden.eur.words') }}) /{{ data_get($derived, 'financial.post_service_monthly_repayment_burden.bgn.formatted') }} лв. ({{ data_get($derived, 'financial.post_service_monthly_repayment_burden.bgn.words') }})/, а кредитите са намалели до {{ $submitted['financial']['post_service_credit_count'] }} броя.</p>
    <p>Подпис:</p>
    <p style="border-bottom: 1px dotted #000; margin-bottom: 5px;">&nbsp;</p>
</div>
