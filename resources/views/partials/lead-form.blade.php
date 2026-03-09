<form method="POST" action="{{ route('leads.store') }}" style="display:grid; gap:10px;">
    @csrf

    <input type="hidden" name="service_type" value="{{ $serviceType }}">
    <input type="hidden" name="source" value="{{ request('source') }}">
    <input type="hidden" name="utm_source" value="{{ request('utm_source') }}">
    <input type="hidden" name="utm_campaign" value="{{ request('utm_campaign') }}">
    <input type="hidden" name="utm_medium" value="{{ request('utm_medium') }}">
    <input type="hidden" name="gclid" value="{{ request('gclid') }}">

    <div style="display:grid; gap:6px;">
        <label>Име и фамилия</label>
        <input name="full_name" value="{{ old('full_name') }}" required>
    </div>

    <div style="display:grid; gap:6px;">
        <label>Телефон</label>
        <input name="phone" value="{{ old('phone') }}" required>
    </div>

    <div style="display:grid; gap:6px;">
        <label>Имейл (по желание)</label>
        <input name="email" value="{{ old('email') }}" type="email">
    </div>

    <div style="display:grid; gap:6px;">
        <label>Град (по желание)</label>
        <input name="city" value="{{ old('city') }}">
    </div>

    <div style="display:grid; gap:6px;">
        <label>Сума (по желание)</label>
        <input name="amount" value="{{ old('amount') }}" type="number" min="0">
    </div>

    <div style="display:grid; gap:6px;">
        <label>Срок в месеци (по желание)</label>
        <input name="term_months" value="{{ old('term_months') }}" type="number" min="1">
    </div>

    <hr>

    <div style="display:grid; gap:6px;">
        <label>ЕГН (задължително)</label>
        <input name="egn" value="{{ old('egn') }}" required maxlength="10">
        <small style="opacity:.75;">Използва се за предварителна оценка и подготовка на оферта.</small>
    </div>

    <div style="display:grid; gap:6px;">
        <label>Месечен доход (по желание)</label>
        <input name="monthly_income" value="{{ old('monthly_income') }}" type="number" min="0">
    </div>

    <div style="display:grid; gap:6px;">
        <label>Заетост (по желание)</label>
        <select name="employment_type">
            <option value="">-- изберете --</option>
            <option value="contract" @selected(old('employment_type')==='contract')>Трудов договор</option>
            <option value="self_employed" @selected(old('employment_type')==='self_employed')>Самоосигуряващ</option>
            <option value="pensioner" @selected(old('employment_type')==='pensioner')>Пенсионер</option>
            <option value="unemployed" @selected(old('employment_type')==='unemployed')>Безработен</option>
        </select>
    </div>

    <div style="display:grid; gap:6px;">
        <label>Месечни задължения (по желание)</label>
        <input name="monthly_debt" value="{{ old('monthly_debt') }}" type="number" min="0">
    </div>

    <label style="display:flex; gap:8px; align-items:flex-start;">
        <input type="checkbox" name="consent" value="1" required>
        <span>Съгласен/а съм личните ми данни да бъдат обработвани за целите на консултацията.</span>
    </label>

    <button type="submit" style="padding:12px 14px; cursor:pointer;">Изпрати</button>
</form>