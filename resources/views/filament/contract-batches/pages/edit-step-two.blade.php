@php
    /** @var \App\Models\ContractBatch $record */
    $record = $this->getRecord();
    $layout = $data['document_layout'] ?? $record->document_layout ?? null;
    $companyOptions = \App\Models\ContractBatch::getCompanyOptions();
    $isFull = $layout === \App\Models\ContractBatch::DOCUMENT_LAYOUT_FULL;
    $isSimplified = $layout === \App\Models\ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED;
    $isSimplifiedNoGuarantor = $layout === \App\Models\ContractBatch::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR;
    $isLoanOnly = $layout === \App\Models\ContractBatch::DOCUMENT_LAYOUT_LOAN_ONLY;
    $isContract12m = $layout === \App\Models\ContractBatch::DOCUMENT_LAYOUT_CONTRACT_12M;
    $isBridgeCredit = $layout === \App\Models\ContractBatch::DOCUMENT_LAYOUT_BRIDGE_CREDIT;
    $showLoan = $isFull || $isLoanOnly || $isBridgeCredit;
    $showConsultation = $isFull || $isSimplified || $isSimplifiedNoGuarantor || $isContract12m || $isBridgeCredit;
@endphp

<x-filament-panels::page>
    <form wire:submit.prevent="save" class="cz-cb-form">
        @if($showConsultation)
            <div class="cz-cb-row cz-cb-row-cs">
                {{-- Договор за Консултантска Услуга и Протокол --}}
                <div class="cz-cb-section">
                    <h3 class="cz-section-title">Договор за Консултантска Услуга и Протокол</h3>
                    <div class="cz-cb-row cz-cb-row-3">
                        <div class="cz-field">
                            <label class="cz-label">Дата на Договор<span class="cz-req">*</span></label>
                            @include('filament.contract-batches.partials.date-input', ['model' => 'data.dates.consultation_contract_date'])
                            @error('data.dates.consultation_contract_date') <span class="cz-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="cz-field">
                            <label class="cz-label">Дата на Протокол</label>
                            @include('filament.contract-batches.partials.date-input', ['model' => 'data.dates.consultation_protocol_date'])
                        </div>
                        <div class="cz-field">
                            <label class="cz-label">Фирма<span class="cz-req">*</span></label>
                            <select wire:model="data.company_key" class="cz-input">
                                @foreach($companyOptions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('data.company_key') <span class="cz-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Запис на Заповед (Клиент към нас) --}}
                <div class="cz-cb-section">
                    <h3 class="cz-section-title">Запис на Заповед (Клиент към нас)</h3>
                    <div class="cz-cb-row cz-cb-row-2">
                        <div class="cz-field">
                            <label class="cz-label">Дата на Издаване</label>
                            @include('filament.contract-batches.partials.date-input', ['model' => 'data.dates.company_promissory_note_issue_date'])
                        </div>
                        <div class="cz-field">
                            <label class="cz-label">Дата на Плащане<span class="cz-req">*</span></label>
                            @include('filament.contract-batches.partials.date-input', ['model' => 'data.dates.company_promissory_note_due_date'])
                            @error('data.dates.company_promissory_note_due_date') <span class="cz-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    @if($isBridgeCredit)
                        {{-- При мостов кредит сумата се въвежда ръчно и се различава от комисионната в консултантския договор. --}}
                        <div class="cz-cb-row cz-cb-row-1">
                            <div class="cz-field">
                                <label class="cz-label">Сума на запис на заповед<span class="cz-req">*</span></label>
                                <div class="cz-input-group">
                                    <input type="number" wire:model="data.financial.company_promissory_note_amount_eur" class="cz-input cz-input-with-suffix" placeholder="Пример: 5000" min="0" step="0.01">
                                    <span class="cz-input-suffix">€</span>
                                </div>
                                @error('data.financial.company_promissory_note_amount_eur') <span class="cz-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($showLoan)
            <div class="cz-cb-section">
                <h3 class="cz-section-title">Договор за Заем и Запис на Заповед (Клиент към Поръчител)</h3>

                {{-- Row 1: Дата | Размер | Сума | Месечна --}}
                <div class="cz-cb-row cz-cb-row-4">
                    <div class="cz-field">
                        <label class="cz-label">Дата на Договор<span class="cz-req">*</span></label>
                        @include('filament.contract-batches.partials.date-input', ['model' => 'data.dates.loan_agreement_date'])
                        @error('data.dates.loan_agreement_date') <span class="cz-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="cz-field">
                        <label class="cz-label">Размер на Заем<span class="cz-req">*</span></label>
                        <div class="cz-input-group">
                            <input type="number" wire:model="data.financial.loan_amount_eur" class="cz-input cz-input-with-suffix" min="0" step="0.01">
                            <span class="cz-input-suffix">€</span>
                        </div>
                        @error('data.financial.loan_amount_eur') <span class="cz-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="cz-field">
                        <label class="cz-label">Сума за Връщане<span class="cz-req">*</span></label>
                        <div class="cz-input-group">
                            <input type="number" wire:model="data.financial.loan_return_amount_eur" class="cz-input cz-input-with-suffix" min="0" step="0.01">
                            <span class="cz-input-suffix">€</span>
                        </div>
                        @error('data.financial.loan_return_amount_eur') <span class="cz-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="cz-field">
                        <label class="cz-label">Месечна Вноска<span class="cz-req">*</span></label>
                        <div class="cz-input-group">
                            <input type="number" wire:model="data.financial.loan_installment_eur" class="cz-input cz-input-with-suffix" min="0" step="0.01">
                            <span class="cz-input-suffix">€</span>
                        </div>
                        @error('data.financial.loan_installment_eur') <span class="cz-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Row 2: Име на Банка (span 2) | Дата на месечна | Дата на последна --}}
                <div class="cz-cb-row cz-cb-row-4">
                    <div class="cz-field cz-col-span-2">
                        <label class="cz-label">
                            Име на Банка
                            <span class="cz-help" title="Име на финансовата институция">?</span>
                        </label>
                        <input type="text" wire:model="data.loan.institution_name" class="cz-input" maxlength="255">
                    </div>
                    <div class="cz-field">
                        <label class="cz-label">Дата на месечна вноска</label>
                        <div class="cz-input-group">
                            <input type="number" wire:model="data.financial.loan_installment_day_of_month" class="cz-input cz-input-with-suffix" min="1" max="31">
                            <span class="cz-input-suffix">число</span>
                        </div>
                    </div>
                    <div class="cz-field">
                        <label class="cz-label">Дата на последна вноска</label>
                        @include('filament.contract-batches.partials.date-input', ['model' => 'data.dates.loan_last_installment_date'])
                    </div>
                </div>

                {{-- Row 3: Падеж на запис на заповед (span 2) | Сума на запис на заповед (span 2) --}}
                <div class="cz-cb-row cz-cb-row-4">
                    <div class="cz-field cz-col-span-2">
                        <label class="cz-label">Падеж на запис на заповед<span class="cz-req">*</span></label>
                        @include('filament.contract-batches.partials.date-input', ['model' => 'data.dates.co_applicant_promissory_note_due_date'])
                        @error('data.dates.co_applicant_promissory_note_due_date') <span class="cz-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="cz-field cz-col-span-2">
                        <label class="cz-label">Сума на запис на заповед<span class="cz-req">*</span></label>
                        <div class="cz-input-group">
                            <input type="number" wire:model="data.financial.co_applicant_promissory_note_amount_eur" class="cz-input cz-input-with-suffix" min="0" step="0.01">
                            <span class="cz-input-suffix">€</span>
                        </div>
                        @error('data.financial.co_applicant_promissory_note_amount_eur') <span class="cz-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        @endif

        <div class="cz-cb-actions">
            <a href="{{ \App\Filament\Resources\ContractBatches\ContractBatchResource::getUrl('edit', ['record' => $record]) }}" class="cz-btn cz-btn-ghost">Назад</a>
            <button type="button" wire:click="save" class="cz-btn cz-btn-primary">Обнови</button>
            <button type="button" class="cz-btn cz-btn-danger" wire:click="mountAction('deleteFromForm')">Изтрий</button>
        </div>
    </form>
</x-filament-panels::page>
