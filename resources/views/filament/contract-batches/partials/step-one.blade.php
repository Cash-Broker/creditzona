@php
    /** @var string|null $layout */
    $layouts = \App\Models\ContractBatch::getLayoutOptions();
    $showCredits = in_array(($layout ?? null), [
        \App\Models\ContractBatch::DOCUMENT_LAYOUT_FULL,
        \App\Models\ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED,
        \App\Models\ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M,
    ], true);
@endphp

<div class="cz-cb">
    {{-- Row 1: Type + City --}}
    <div class="cz-cb-row cz-cb-row-2">
        <div class="cz-field">
            <label class="cz-label" for="cz-document-layout">Вид Документи<span class="cz-req">*</span></label>
            <select wire:model.live="data.document_layout" id="cz-document-layout" class="cz-input">
                @foreach($layouts as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('data.document_layout') <span class="cz-error">{{ $message }}</span> @enderror
        </div>
        <div class="cz-field">
            <label class="cz-label" for="cz-client-city">Град<span class="cz-req">*</span></label>
            <div class="cz-input-group">
                <span class="cz-input-prefix">гр.</span>
                <input type="text" wire:model="data.client.city" id="cz-client-city" class="cz-input cz-input-with-prefix" maxlength="120">
            </div>
            @error('data.client.city') <span class="cz-error">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Row 2 + 3: Client / Avalist sections --}}
    <div class="cz-cb-row cz-cb-row-2">
        @include('filament.contract-batches.partials.party-section', [
            'title' => 'Данни на Клиент',
            'namespace' => 'client',
        ])
        @include('filament.contract-batches.partials.party-section', [
            'title' => 'Данни на Авалист',
            'namespace' => 'co_applicant',
        ])
    </div>

    {{-- Row 4: Credit data (Пълен + Опростен) --}}
    @if($showCredits)
        <div class="cz-cb-section">
            <h3 class="cz-section-title">Данни за Кредити</h3>
            <div class="cz-cb-row cz-cb-row-5">
                <div class="cz-field">
                    <label class="cz-label">В Финансови Институции</label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.credit_count_in_institutions" class="cz-input cz-input-with-suffix" placeholder="Пример: 3" min="0">
                        <span class="cz-input-suffix">броя</span>
                    </div>
                </div>
                <div class="cz-field">
                    <label class="cz-label">Брой Институции <span class="cz-help" title="Брой финансови институции, които клиентът ползва">?</span></label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.institution_count" class="cz-input cz-input-with-suffix" placeholder="Пример: 2" min="0">
                        <span class="cz-input-suffix">броя</span>
                    </div>
                </div>
                <div class="cz-field">
                    <label class="cz-label">Кредити в Банки</label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.credit_count_in_banks" class="cz-input cz-input-with-suffix" placeholder="Пример: 2" min="0">
                        <span class="cz-input-suffix">броя</span>
                    </div>
                </div>
                <div class="cz-field">
                    <label class="cz-label">Брой Банки <span class="cz-help" title="Брой банки, в които клиентът има кредити">?</span></label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.bank_count" class="cz-input cz-input-with-suffix" placeholder="Пример: 2" min="0">
                        <span class="cz-input-suffix">броя</span>
                    </div>
                </div>
                <div class="cz-field">
                    <label class="cz-label">Общ Размер<span class="cz-req">*</span></label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.total_loan_amount_eur" class="cz-input cz-input-with-suffix" placeholder="Пример: 30000" min="0" step="0.01">
                        <span class="cz-input-suffix">€</span>
                    </div>
                    @error('data.financial.total_loan_amount_eur') <span class="cz-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="cz-cb-row cz-cb-row-5">
                <div class="cz-field">
                    <label class="cz-label">Комисионна<span class="cz-req">*</span></label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.commission_eur" class="cz-input cz-input-with-suffix" placeholder="Пример: 2500" min="0" step="0.01">
                        <span class="cz-input-suffix">€</span>
                    </div>
                    @error('data.financial.commission_eur') <span class="cz-error">{{ $message }}</span> @enderror
                </div>
                <div class="cz-field">
                    <label class="cz-label">Месечни Вноски<span class="cz-req">*</span></label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.monthly_payments_eur" class="cz-input cz-input-with-suffix" placeholder="Пример: 1000" min="0" step="0.01">
                        <span class="cz-input-suffix">€</span>
                    </div>
                </div>
                <div class="cz-field">
                    <label class="cz-label">Частни Заеми</label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.private_loans_eur" class="cz-input cz-input-with-suffix" placeholder="Пример: 5000" min="0" step="0.01">
                        <span class="cz-input-suffix">€</span>
                    </div>
                </div>
                <div class="cz-field">
                    <label class="cz-label">Доход (Нетно)<span class="cz-req">*</span></label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.net_income_eur" class="cz-input cz-input-with-suffix" placeholder="Пример: 3000" min="0" step="0.01">
                        <span class="cz-input-suffix">€</span>
                    </div>
                </div>
                <div class="cz-field">
                    <label class="cz-label">Съдебно Изискуеми</label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.court_required_eur" class="cz-input cz-input-with-suffix" placeholder="Пример: 3000" min="0" step="0.01">
                        <span class="cz-input-suffix">€</span>
                    </div>
                </div>
            </div>
            <div class="cz-cb-row cz-cb-row-5">
                <div class="cz-field">
                    <label class="cz-label">Кредити след съдействие</label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.post_service_credit_count" class="cz-input cz-input-with-suffix" placeholder="Пример: 1" min="0">
                        <span class="cz-input-suffix">броя</span>
                    </div>
                </div>
                <div class="cz-field">
                    <label class="cz-label">Вноска след съдействие</label>
                    <div class="cz-input-group">
                        <input type="number" wire:model="data.financial.post_service_monthly_repayment_burden_eur" class="cz-input cz-input-with-suffix" placeholder="Пример: 500" min="0" step="0.01">
                        <span class="cz-input-suffix">€</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
