@php
    /** @var string $model Livewire property path, e.g. 'data.dates.loan_agreement_date' (state stays Y-m-d). */
@endphp

{{-- Текстово поле за дата във формат дд.мм.гггг, независимо от локала на браузъра.
     Видимата стойност е d.m.Y, а Livewire state-ът остава Y-m-d. --}}
<div
    x-data="{
        state: $wire.entangle(@js($model)),
        display: '',
        syncFromState() {
            const value = this.state;

            if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
                const parts = value.slice(0, 10).split('-');
                this.display = parts[2] + '.' + parts[1] + '.' + parts[0];

                return;
            }

            if (! value) {
                this.display = '';
            }
        },
        format() {
            const digits = this.display.replace(/\D/g, '').slice(0, 8);

            if (digits.length > 4) {
                this.display = digits.slice(0, 2) + '.' + digits.slice(2, 4) + '.' + digits.slice(4);
            } else if (digits.length > 2) {
                this.display = digits.slice(0, 2) + '.' + digits.slice(2);
            } else {
                this.display = digits;
            }
        },
        commit() {
            if (this.display.trim() === '') {
                this.state = null;

                return;
            }

            const match = this.display.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);

            if (! match) {
                this.syncFromState();

                return;
            }

            const day = parseInt(match[1], 10);
            const month = parseInt(match[2], 10);
            const year = parseInt(match[3], 10);
            const date = new Date(year, month - 1, day);

            if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
                this.syncFromState();

                return;
            }

            this.state = match[3] + '-' + match[2] + '-' + match[1];
        },
        init() {
            this.syncFromState();
            this.$watch('state', () => this.syncFromState());
        },
    }"
>
    <input
        type="text"
        x-model="display"
        @input="format()"
        @blur="commit()"
        @keydown.enter="commit()"
        inputmode="numeric"
        autocomplete="off"
        placeholder="дд.мм.гггг"
        maxlength="10"
        class="cz-input"
    >
</div>
