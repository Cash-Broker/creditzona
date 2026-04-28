@php
    $company = $derived['company'];
@endphp

<div class="title">ПРОТОКОЛ ЗА ИЗВЪРШЕНА КОНСУЛТАЦИЯ</div>

<p>Днес, {{ $derived['dates']['consultation_protocol_date_formatted'] }} г. в гр. Пловдив, между:</p>
<p>{{ $derived['identities']['client'] }} - <strong>ВЪЗЛОЖИТЕЛ</strong>, и</p>
<p>{{ $company['full_identity'] }} - <strong>ИЗПЪЛНИТЕЛ</strong>,</p>
<p>Се подписа настоящия двустранен протокол, по силата на който <strong>ИЗПЪЛНИТЕЛЯТ</strong> е осъществил, а <strong>ВЪЗЛОЖИТЕЛЯТ</strong> декларира, че е приел изцяло и без забележки извършената от <strong>ИЗПЪЛНИТЕЛЯ</strong> работа включваща неизчерпателно изброените - консултации, финансов анализ, план за редуциране на кредитна експозиция и др.</p>
<p>Настоящият протокол се състави и подписа в два еднообразни екземпляра - по един за всяка от страните.</p>

<div style="margin-top: 40px;">
    <p><strong>ВЪЗЛОЖИТЕЛ</strong>/ПРИЕЛ:</p>
    <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
</div>

<div style="margin-top: 40px;">
    <p><strong>ИЗПЪЛНИТЕЛ</strong>/ПРЕДАЛ:</p>
    <p style="border-bottom: 1px solid #000; margin-bottom: 5px;">&nbsp;</p>
</div>

<div class="spacer"></div>
<div>
    <p style="text-align: center;"><strong>Възложителят</strong></p>
    <p>………………………………………………………………………………………………………</p>
    <p>Декларирам, че преди извършването на услугата по договора имам {{ $submitted['financial']['active_credit_count'] }} броя кредити с обща месечна вноска от {{ data_get($derived, 'financial.monthly_repayment_burden.eur.formatted') }} € ({{ data_get($derived, 'financial.monthly_repayment_burden.eur.words') }}) /{{ data_get($derived, 'financial.monthly_repayment_burden.bgn.formatted') }} лв. ({{ data_get($derived, 'financial.monthly_repayment_burden.bgn.words') }})/. След извършения от <strong>ИЗПЪЛНИТЕЛЯ</strong> подробен финансов анализ и предоставеното съдействие, месечната вноска се е редуцирала до {{ data_get($derived, 'financial.post_service_monthly_repayment_burden.eur.formatted') }} € ({{ data_get($derived, 'financial.post_service_monthly_repayment_burden.eur.words') }}) /{{ data_get($derived, 'financial.post_service_monthly_repayment_burden.bgn.formatted') }} лв. ({{ data_get($derived, 'financial.post_service_monthly_repayment_burden.bgn.words') }})/, а кредитите са намалели до {{ $submitted['financial']['post_service_credit_count'] }} броя.</p>
    <p>Подпис:</p>
    <p style="border-bottom: 1px dotted #000; margin-bottom: 5px;">&nbsp;</p>
</div>
