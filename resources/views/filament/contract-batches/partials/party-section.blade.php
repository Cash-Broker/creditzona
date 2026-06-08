@php
    /** @var string $title */
    /** @var string $namespace */
@endphp

<div class="cz-cb-section">
    <h3 class="cz-section-title">{{ $title }}</h3>

    {{-- Row A: Имена 2/3 + ЕГН 1/3 --}}
    <div class="cz-cb-row cz-cb-row-3">
        <div class="cz-field cz-col-span-2">
            <label class="cz-label">Имена<span class="cz-req">*</span></label>
            <input type="text" wire:model="data.{{ $namespace }}.full_name" class="cz-input" maxlength="255">
            @error("data.{$namespace}.full_name") <span class="cz-error">{{ $message }}</span> @enderror
        </div>
        <div class="cz-field">
            <label class="cz-label">ЕГН<span class="cz-req">*</span></label>
            <input type="text" wire:model="data.{{ $namespace }}.egn" class="cz-input" maxlength="10">
            @error("data.{$namespace}.egn") <span class="cz-error">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Row B: Адрес full --}}
    <div class="cz-cb-row cz-cb-row-1">
        <div class="cz-field">
            <label class="cz-label">
                Адрес<span class="cz-req">*</span>
                <span class="cz-help" title="Пример: гр. София, ул. Витоша 1, ет. 2, ап. 5">?</span>
            </label>
            <textarea wire:model="data.{{ $namespace }}.permanent_address" class="cz-input cz-textarea" rows="2"></textarea>
            @error("data.{$namespace}.permanent_address") <span class="cz-error">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Row C: Лична Карта | Дата | От --}}
    <div class="cz-cb-row cz-cb-row-3">
        <div class="cz-field">
            <label class="cz-label">Лична Карта<span class="cz-req">*</span></label>
            <input type="text" wire:model="data.{{ $namespace }}.id_card_number" class="cz-input" maxlength="32">
            @error("data.{$namespace}.id_card_number") <span class="cz-error">{{ $message }}</span> @enderror
        </div>
        <div class="cz-field">
            <label class="cz-label">Дата на Издаване<span class="cz-req">*</span></label>
            <input type="date" wire:model="data.{{ $namespace }}.id_card_issued_at" class="cz-input">
            @error("data.{$namespace}.id_card_issued_at") <span class="cz-error">{{ $message }}</span> @enderror
        </div>
        <div class="cz-field">
            <label class="cz-label">Издадена от<span class="cz-req">*</span></label>
            <input type="text" wire:model="data.{{ $namespace }}.id_card_issued_by" class="cz-input" maxlength="255">
            @error("data.{$namespace}.id_card_issued_by") <span class="cz-error">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Row D: Движимо/недвижимо имущество --}}
    <div class="cz-cb-row cz-cb-row-1">
        <div class="cz-field">
            <label class="cz-label">Движимо/недвижимо имущество</label>
            <textarea wire:model="data.{{ $namespace }}.property" class="cz-input cz-textarea" rows="3" placeholder="Опишете движимо и/или недвижимо имущество (по желание)"></textarea>
            @error("data.{$namespace}.property") <span class="cz-error">{{ $message }}</span> @enderror
        </div>
    </div>
</div>
