<template>
    <form
        id="lead-form-compact"
        @submit.prevent="submitForm"
        class="space-y-4"
        novalidate
    >
        <section
            class="rounded-[30px] bg-white/95 p-5 shadow-[0_18px_50px_rgba(15,23,42,0.10)] ring-1 ring-white/70 backdrop-blur"
        >
            <div class="text-center">
                <div
                    class="text-[11px] font-semibold uppercase tracking-[0.24em] text-text-subtle"
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
                    :class="{ 'input-error': getFieldError('amount') }"
                    :style="creditRangeStyle"
                    aria-describedby="loan-amount-hint"
                    @input="handleInput('amount')"
                    @blur="handleBlur('amount')"
                />

                <span class="range-edge">{{ formattedMaxAmount }}</span>
            </div>

            <p
                id="loan-amount-hint"
                class="mt-3 text-center text-xs text-text-subtle"
            >
                Плъзнете скалата, за да изберете подходяща сума.
            </p>

            <p v-if="getFieldError('amount')" class="field-error mt-3 text-center">
                {{ getFieldError("amount") }}
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
                        :class="{ 'input-error': getFieldError('credit_type') }"
                        autocomplete="off"
                        required
                        :disabled="lockCreditType"
                        @change="handleInput('credit_type')"
                        @blur="handleBlur('credit_type')"
                    >
                        <option value="" disabled>Изберете</option>
                        <option value="consumer">Потребителски кредит</option>
                        <option value="mortgage">Ипотечен кредит</option>
                    </select>

                    <p v-if="getFieldError('credit_type')" class="field-error">
                        {{ getFieldError("credit_type") }}
                    </p>
                </div>

                <div class="field">
                    <label class="field-label" for="first-name">Име</label>
                    <input
                        id="first-name"
                        v-model="form.first_name"
                        type="text"
                        placeholder="Иван"
                        class="input"
                        :class="{ 'input-error': getFieldError('first_name') }"
                        autocomplete="given-name"
                        required
                        @input="handleInput('first_name')"
                        @blur="handleBlur('first_name')"
                    />

                    <p v-if="getFieldError('first_name')" class="field-error">
                        {{ getFieldError("first_name") }}
                    </p>
                </div>

                <div class="field">
                    <label class="field-label" for="last-name">Фамилия</label>
                    <input
                        id="last-name"
                        v-model="form.last_name"
                        type="text"
                        placeholder="Иванов"
                        class="input"
                        :class="{ 'input-error': getFieldError('last_name') }"
                        autocomplete="family-name"
                        required
                        @input="handleInput('last_name')"
                        @blur="handleBlur('last_name')"
                    />

                    <p v-if="getFieldError('last_name')" class="field-error">
                        {{ getFieldError("last_name") }}
                    </p>
                </div>

                <div class="field">
                    <label class="field-label" for="phone">Телефон</label>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        placeholder="08XXXXXXXX"
                        class="input"
                        :class="{
                            'input-error':
                                getFieldError('phone') &&
                                getFieldError('phone') !== submitError,
                        }"
                        inputmode="tel"
                        autocomplete="tel"
                        required
                        @input="handleInput('phone')"
                        @blur="handleBlur('phone')"
                    />

                    <p
                        v-if="
                            getFieldError('phone') &&
                            getFieldError('phone') !== submitError
                        "
                        class="field-error"
                    >
                        {{ getFieldError("phone") }}
                    </p>
                </div>

                <div class="field">
                    <label class="field-label" for="email">Имейл</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="example@email.com"
                        class="input"
                        :class="{ 'input-error': getFieldError('email') }"
                        autocomplete="email"
                        required
                        @input="handleInput('email')"
                        @blur="handleBlur('email')"
                    />

                    <p v-if="getFieldError('email')" class="field-error">
                        {{ getFieldError("email") }}
                    </p>
                </div>

                <input
                    v-model="form.website"
                    type="text"
                    tabindex="-1"
                    autocomplete="off"
                    aria-hidden="true"
                    class="hidden"
                />

                <input
                    v-model="form.form_started_at"
                    type="hidden"
                />

                <div class="field">
                    <label class="field-label" for="city">Град</label>
                    <input
                        id="city"
                        v-model="form.city"
                        type="text"
                        placeholder="Пловдив"
                        class="input"
                        :class="{ 'input-error': getFieldError('city') }"
                        autocomplete="address-level2"
                        required
                        @input="handleInput('city')"
                        @blur="handleBlur('city')"
                    />

                    <p v-if="getFieldError('city')" class="field-error">
                        {{ getFieldError("city") }}
                    </p>
                </div>
            </div>

            <p
                class="mt-4 rounded-2xl border border-accent-soft-border bg-accent-soft/70 px-4 py-3 text-sm font-medium text-text-muted"
            >
                Моля, попълвайте имената, града и местоположението само на
                кирилица.
            </p>

            <transition name="fade-slide">
                <div
                    v-if="isMortgage"
                    class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2"
                >
                    <div class="field">
                        <label class="field-label" for="property-type">
                            Вид имот
                        </label>
                        <select
                            id="property-type"
                            v-model="form.property_type"
                            class="input"
                            :class="{ 'input-error': getFieldError('property_type') }"
                            :required="isMortgage"
                            @change="handleInput('property_type')"
                            @blur="handleBlur('property_type')"
                        >
                            <option value="" disabled>Изберете</option>
                            <option value="house">Къща</option>
                            <option value="apartment">Апартамент</option>
                        </select>

                        <p v-if="getFieldError('property_type')" class="field-error">
                            {{ getFieldError("property_type") }}
                        </p>
                    </div>

                    <div class="field">
                        <label class="field-label" for="property-location">
                            Местонахождение на имота
                        </label>
                        <input
                            id="property-location"
                            v-model="form.property_location"
                            type="text"
                            placeholder="Например: Пловдив"
                            class="input"
                            :class="{ 'input-error': getFieldError('property_location') }"
                            :required="isMortgage"
                            @input="handleInput('property_location')"
                            @blur="handleBlur('property_location')"
                        />

                        <p
                            v-if="getFieldError('property_location')"
                            class="field-error"
                        >
                            {{ getFieldError("property_location") }}
                        </p>
                    </div>
                </div>
            </transition>

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

            <p
                v-if="submitError"
                class="rounded-2xl border border-error/30 bg-error/10 px-4 py-3 text-sm text-error"
            >
                {{ submitError }}
            </p>

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

const props = defineProps({
    initialCreditType: {
        type: String,
        default: "",
    },
    lockCreditType: {
        type: Boolean,
        default: false,
    },
});

const {
    form,
    loading,
    success,
    submitError,
    getFieldError,
    isMortgage,
    lockCreditType,
    amountMin,
    amountMax,
    amountStep,
    formattedAmount,
    formattedMinAmount,
    formattedMaxAmount,
    creditRangeStyle,
    handleBlur,
    handleInput,
    submitForm,
} = useLeadForm({
    initialCreditType: props.initialCreditType,
    lockCreditType: props.lockCreditType,
});
</script>

<style scoped>
.amount-value {
    font-size: clamp(2rem, 4.6vw, 3.35rem);
    line-height: 1;
    font-weight: 900;
    letter-spacing: -0.04em;
    color: var(--color-accent-darkened);
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.6);
}

.range-edge {
    flex-shrink: 0;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--color-text-subtle);
}

.credit-range {
    --range-progress: 0%;
    --range-active: var(--color-accent);
    --range-track: color-mix(
        in oklab,
        var(--color-accent-soft) 65%,
        var(--color-surface) 35%
    );

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
        0 0 0 4px color-mix(in oklab, var(--color-accent) 20%, white);
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
    background: linear-gradient(
        180deg,
        var(--color-surface) 0%,
        color-mix(
                in oklab,
                var(--color-background) 72%,
                var(--color-surface) 28%
            )
            100%
    );
    border: 4px solid var(--color-accent);
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
    background: var(--range-track);
}

.credit-range::-moz-range-progress {
    height: 12px;
    border: none;
    border-radius: 9999px;
    background: var(--range-active);
}

.credit-range::-moz-range-thumb {
    width: 28px;
    height: 28px;
    border: 4px solid var(--color-accent);
    border-radius: 9999px;
    background: linear-gradient(
        180deg,
        var(--color-surface) 0%,
        color-mix(
                in oklab,
                var(--color-background) 72%,
                var(--color-surface) 28%
            )
            100%
    );
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
    color: var(--color-text-muted);
    padding-left: 0.1rem;
}

.input {
    width: 100%;
    height: 50px;
    border-radius: 14px;
    border: 1px solid var(--color-border-strong);
    background: linear-gradient(
        180deg,
        var(--color-surface) 0%,
        color-mix(
                in oklab,
                var(--color-background) 60%,
                var(--color-surface) 40%
            )
            100%
    );
    padding: 0 14px;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--color-text);
    outline: none;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    transition:
        border-color 0.18s ease,
        box-shadow 0.18s ease,
        transform 0.18s ease,
        background 0.18s ease;
}

.input::placeholder {
    color: var(--color-text-subtle);
    font-weight: 400;
}

.input:focus {
    border-color: var(--color-accent);
    background: var(--color-surface);
    box-shadow:
        0 0 0 4px color-mix(in oklab, var(--color-accent) 15%, white),
        0 4px 14px rgba(15, 23, 42, 0.05);
    transform: translateY(-1px);
}

.input:disabled {
    opacity: 0.72;
    cursor: not-allowed;
    background: color-mix(
        in oklab,
        var(--color-background) 75%,
        var(--color-surface) 25%
    );
}

.input-error {
    border-color: color-mix(in oklab, var(--color-error, #dc2626) 75%, white);
    box-shadow: 0 0 0 3px color-mix(in oklab, var(--color-error, #dc2626) 10%, white);
}

.field-error {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--color-error, #dc2626);
    padding-left: 0.1rem;
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
    color: var(--color-surface);
    cursor: pointer;
    background: linear-gradient(
        180deg,
        color-mix(in oklab, var(--color-accent) 88%, white) 0%,
        var(--color-accent-darkened) 100%
    );
    box-shadow:
        0 14px 30px rgba(15, 23, 42, 0.28),
        inset 0 1px 0 rgba(255, 255, 255, 0.24);
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease,
        filter 0.18s ease;
}

.cta-button:hover:not(:disabled) {
    transform: translateY(-1px);
    background: linear-gradient(
        180deg,
        var(--color-accent) 0%,
        var(--color-accent-darkened) 100%
    );
    box-shadow:
        0 18px 34px rgba(15, 23, 42, 0.32),
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
    color: var(--color-text-muted);
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
