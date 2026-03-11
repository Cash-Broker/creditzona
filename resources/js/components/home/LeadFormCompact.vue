<template>
    <form @submit.prevent="submitForm" class="space-y-4">
        <section
            class="rounded-[30px] bg-white/95 p-5 shadow-[0_18px_50px_rgba(15,23,42,0.10)] ring-1 ring-white/70 backdrop-blur"
        >
            <div class="text-center">
                <div
                    class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500"
                >
                    Желана сума
                </div>

                <div class="mt-2 amount-value">
                    {{ formattedAmount }}
                </div>
            </div>

            <div class="mt-5 flex items-center gap-3 sm:gap-4">
                <span class="range-edge">{{ formattedMinAmount }}</span>

                <input
                    id="loan-amount"
                    v-model.number="form.amount"
                    type="range"
                    :min="amountMin"
                    :max="amountMax"
                    :step="amountStep"
                    class="credit-range"
                    :style="creditRangeStyle"
                    aria-describedby="loan-amount-hint"
                />

                <span class="range-edge">{{ formattedMaxAmount }}</span>
            </div>

            <p
                id="loan-amount-hint"
                class="mt-3 text-center text-xs text-slate-500"
            >
                Плъзнете скалата, за да изберете подходяща сума.
            </p>
        </section>

        <section
            class="rounded-[30px] bg-white/95 p-5 shadow-[0_18px_50px_rgba(15,23,42,0.10)] ring-1 ring-white/70 backdrop-blur"
        >
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div class="field">
                    <label class="field-label" for="credit-type"
                        >Тип кредит</label
                    >
                    <select
                        id="credit-type"
                        v-model="form.credit_type"
                        class="input"
                        autocomplete="off"
                        required
                    >
                        <option value="" disabled>Изберете</option>
                        <option value="consumer">Потребителски кредит</option>
                        <option value="mortgage">Ипотечен кредит</option>
                    </select>
                </div>

                <div class="field">
                    <label class="field-label" for="first-name">Име</label>
                    <input
                        id="first-name"
                        v-model="form.first_name"
                        type="text"
                        placeholder="Иван"
                        class="input"
                        autocomplete="given-name"
                        required
                    />
                </div>

                <div class="field">
                    <label class="field-label" for="last-name">Фамилия</label>
                    <input
                        id="last-name"
                        v-model="form.last_name"
                        type="text"
                        placeholder="Иванов"
                        class="input"
                        autocomplete="family-name"
                        required
                    />
                </div>

                <div class="field">
                    <label class="field-label" for="phone">Телефон</label>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        placeholder="08XXXXXXXX"
                        class="input"
                        inputmode="tel"
                        autocomplete="tel"
                        required
                    />
                </div>

                <div class="field">
                    <label class="field-label" for="email">Имейл</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="example@email.com"
                        class="input"
                        autocomplete="email"
                        required
                    />
                </div>

                <div class="field">
                    <label class="field-label" for="city">Град</label>
                    <input
                        id="city"
                        v-model="form.city"
                        type="text"
                        placeholder="Пловдив"
                        class="input"
                        autocomplete="address-level2"
                        required
                    />
                </div>
            </div>

            <div class="mt-5">
                <button
                    type="submit"
                    :disabled="loading"
                    :aria-busy="loading ? 'true' : 'false'"
                    class="cta-button"
                >
                    <font-awesome-icon icon="fa-solid fa-paper-plane" />
                    <span v-if="!loading">Направи безплатна проверка</span>
                    <span v-else>Изпращане...</span>
                </button>

                <p class="cta-note">Ще се свържем с вас до 48ч</p>
            </div>

            <transition name="fade-slide">
                <div v-if="success" class="form-success mt-4">
                    <font-awesome-icon
                        icon="fa-solid fa-circle-check"
                        class="mt-0.5"
                    />
                    <span>
                        Запитването е изпратено успешно. Ще се свържем с вас до
                        48ч.
                    </span>
                </div>
            </transition>
        </section>
    </form>
</template>

<script setup>
import { useLeadForm } from "../../composables/useLeadForm";

const {
    form,
    loading,
    success,
    amountMin,
    amountMax,
    amountStep,
    formattedAmount,
    formattedMinAmount,
    formattedMaxAmount,
    creditRangeStyle,
    submitForm,
} = useLeadForm();
</script>

<style scoped>
.amount-value {
    font-size: clamp(2rem, 4.6vw, 3.35rem);
    line-height: 1;
    font-weight: 900;
    letter-spacing: -0.04em;
    color: #b7791f;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.6);
}

.range-edge {
    flex-shrink: 0;
    font-size: 0.78rem;
    font-weight: 700;
    color: #64748b;
}

.credit-range {
    --range-progress: 0%;
    --range-active: #d6a11b;
    --range-track: #e8edf3;

    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 12px;
    border-radius: 9999px;
    outline: none;
    cursor: pointer;
    background: linear-gradient(
        to right,
        var(--range-active) 0%,
        var(--range-active) var(--range-progress),
        var(--range-track) var(--range-progress),
        var(--range-track) 100%
    );
    box-shadow:
        inset 0 1px 2px rgba(15, 23, 42, 0.08),
        inset 0 -1px 1px rgba(255, 255, 255, 0.4);
    transition: background 0.2s ease;
}

.credit-range:focus-visible {
    box-shadow:
        inset 0 1px 2px rgba(15, 23, 42, 0.08),
        0 0 0 4px rgba(214, 161, 27, 0.16);
}

.credit-range::-webkit-slider-runnable-track {
    height: 12px;
    border-radius: 9999px;
    background: transparent;
}

.credit-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 28px;
    height: 28px;
    border-radius: 9999px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 4px solid #d6a11b;
    box-shadow:
        0 8px 20px rgba(15, 23, 42, 0.18),
        0 1px 0 rgba(255, 255, 255, 0.8) inset;
    cursor: pointer;
    margin-top: -8px;
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease;
}

.credit-range::-webkit-slider-thumb:hover {
    transform: scale(1.06);
    box-shadow:
        0 10px 24px rgba(15, 23, 42, 0.2),
        0 1px 0 rgba(255, 255, 255, 0.85) inset;
}

.credit-range::-moz-range-track {
    height: 12px;
    border: none;
    border-radius: 9999px;
    background: #e8edf3;
}

.credit-range::-moz-range-progress {
    height: 12px;
    border: none;
    border-radius: 9999px;
    background: #d6a11b;
}

.credit-range::-moz-range-thumb {
    width: 28px;
    height: 28px;
    border: 4px solid #d6a11b;
    border-radius: 9999px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18);
    cursor: pointer;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.field-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    padding-left: 0.1rem;
}

.input {
    width: 100%;
    height: 50px;
    border-radius: 14px;
    border: 1px solid #dbe3ec;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    padding: 0 14px;
    font-size: 0.95rem;
    font-weight: 500;
    color: #0f172a;
    outline: none;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    transition:
        border-color 0.18s ease,
        box-shadow 0.18s ease,
        transform 0.18s ease,
        background 0.18s ease;
}

.input::placeholder {
    color: #94a3b8;
    font-weight: 400;
}

.input:focus {
    border-color: #d6a11b;
    background: #ffffff;
    box-shadow:
        0 0 0 4px rgba(214, 161, 27, 0.12),
        0 4px 14px rgba(15, 23, 42, 0.05);
    transform: translateY(-1px);
}

.cta-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
    width: 100%;
    min-height: 60px;
    border: 0;
    border-radius: 18px;
    padding: 0 1.6rem;
    font-size: 1rem;
    font-weight: 900;
    letter-spacing: 0.01em;
    color: #ffffff;
    cursor: pointer;
    background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
    box-shadow:
        0 14px 30px rgba(220, 38, 38, 0.28),
        inset 0 1px 0 rgba(255, 255, 255, 0.24);
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease,
        filter 0.18s ease;
}

.cta-button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow:
        0 18px 34px rgba(220, 38, 38, 0.32),
        inset 0 1px 0 rgba(255, 255, 255, 0.24);
    filter: saturate(1.04);
}

.cta-button:active:not(:disabled) {
    transform: translateY(0);
}

.cta-button:disabled {
    cursor: not-allowed;
    opacity: 0.72;
    box-shadow: none;
}

.cta-note {
    margin-top: 0.85rem;
    text-align: center;
    font-size: 0.92rem;
    font-weight: 700;
    color: #475569;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.22s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
