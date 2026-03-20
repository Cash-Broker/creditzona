<template>
    <div class="relative">
        <transition name="success-swap" mode="out-in">
            <div
                v-if="success"
                :key="'success'"
                ref="successPanel"
                class="success-popover"
                role="status"
                aria-live="polite"
                tabindex="-1"
            >
            <div class="success-grid" aria-hidden="true"></div>
            <div class="success-glow success-glow-primary"></div>
            <div class="success-glow success-glow-secondary"></div>
            <span class="success-spark success-spark-1" aria-hidden="true"></span>
            <span class="success-spark success-spark-2" aria-hidden="true"></span>
            <span class="success-spark success-spark-3" aria-hidden="true"></span>

            <div class="success-shell">
                <div class="success-pill">Успешно изпратено</div>

                <div class="success-badge-wrap" aria-hidden="true">
                    <span class="success-ring success-ring-outer"></span>
                    <span class="success-ring success-ring-inner"></span>

                    <div class="success-badge">
                        <font-awesome-icon icon="fa-solid fa-check" />
                    </div>
                </div>

                <div class="success-kicker">Кредитна заявка</div>
                <h3 class="success-title">Заявката е приета успешно</h3>
                <p class="success-text">
                    Получихме данните ви и наш консултант ще се свърже с вас до 48ч.
                </p>

                <div class="success-meta" aria-hidden="true">
                    <span class="success-chip">Безплатна консултация</span>
                    <span class="success-chip">Обратна връзка до 48ч</span>
                </div>

                <p class="success-note">
                    Очаквайте обаждане на посочения от вас телефон.
                </p>
            </div>
        </div>

            <form
                v-else
                :key="'form'"
                id="lead-form-compact"
                @submit.prevent="handleSubmit"
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

                    <div class="range-control">
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
                    </div>

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
                            <option value="consumer_with_guarantor">
                                Потребителски кредит с поръчител
                            </option>
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
                    <template v-if="isConsumerWithGuarantor">
                        При този тип кредит попълнете и данните на поръчителя отдолу.
                    </template>
                    <template v-else>
                        Моля, попълвайте имената, града и местоположението само на
                        кирилица.
                    </template>
                </p>

                <transition name="fade-slide">
                    <div
                        v-if="isConsumerWithGuarantor"
                        class="mt-4 rounded-[24px] border border-accent-soft-border bg-accent-soft/35 p-4"
                    >
                        <div class="mb-3">
                            <div class="text-sm font-extrabold text-accent-ink">
                                Данни за поръчителя
                            </div>
                            <p class="mt-1 text-xs font-medium text-text-subtle">
                                Добавете две имена и телефон на поръчителя.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="field">
                                <label class="field-label" for="guarantor-first-name">
                                    Име на поръчител
                                </label>
                                <input
                                    id="guarantor-first-name"
                                    ref="guarantorFirstNameInput"
                                    v-model="form.guarantor_first_name"
                                    type="text"
                                    placeholder="Мария"
                                    class="input"
                                    :class="{
                                        'input-error': getFieldError('guarantor_first_name'),
                                    }"
                                    autocomplete="off"
                                    required
                                    @input="handleInput('guarantor_first_name')"
                                    @blur="handleBlur('guarantor_first_name')"
                                />

                                <p
                                    v-if="getFieldError('guarantor_first_name')"
                                    class="field-error"
                                >
                                    {{ getFieldError("guarantor_first_name") }}
                                </p>
                            </div>

                            <div class="field">
                                <label class="field-label" for="guarantor-last-name">
                                    Фамилия на поръчител
                                </label>
                                <input
                                    id="guarantor-last-name"
                                    v-model="form.guarantor_last_name"
                                    type="text"
                                    placeholder="Петрова"
                                    class="input"
                                    :class="{
                                        'input-error': getFieldError('guarantor_last_name'),
                                    }"
                                    autocomplete="off"
                                    required
                                    @input="handleInput('guarantor_last_name')"
                                    @blur="handleBlur('guarantor_last_name')"
                                />

                                <p
                                    v-if="getFieldError('guarantor_last_name')"
                                    class="field-error"
                                >
                                    {{ getFieldError("guarantor_last_name") }}
                                </p>
                            </div>

                            <div class="field">
                                <label class="field-label" for="guarantor-phone">
                                    Телефон на поръчител
                                </label>
                                <input
                                    id="guarantor-phone"
                                    v-model="form.guarantor_phone"
                                    type="tel"
                                    placeholder="08XXXXXXXX"
                                    class="input"
                                    :class="{
                                        'input-error': getFieldError('guarantor_phone'),
                                    }"
                                    inputmode="tel"
                                    autocomplete="off"
                                    required
                                    @input="handleInput('guarantor_phone')"
                                    @blur="handleBlur('guarantor_phone')"
                                />

                                <p
                                    v-if="getFieldError('guarantor_phone')"
                                    class="field-error"
                                >
                                    {{ getFieldError("guarantor_phone") }}
                                </p>
                            </div>
                        </div>
                    </div>
                </transition>

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

                <div
                    class="consent-panel mt-5"
                    :class="{
                        'consent-panel-error': getFieldError('privacy_consent'),
                    }"
                >
                    <label class="consent-check" for="privacy-consent">
                        <input
                            id="privacy-consent"
                            v-model="form.privacy_consent"
                            type="checkbox"
                            class="consent-checkbox"
                            @change="handleInput('privacy_consent')"
                            @blur="handleBlur('privacy_consent')"
                        />

                        <span class="consent-copy">
                            Съгласен/съгласна съм личните ми данни да бъдат обработвани
                            за целите на кредитната консултация и запознат/а съм с
                            документа
                            <a
                                :href="leadConsentDocument.url"
                                class="consent-link"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{
                                    false
                                        ? "Подготвяме документа..."
                                        : leadConsentDocument.name
                                }}
                            </a>
                            .
                        </span>
                    </label>

                    <p class="consent-note">
                        За да изпратите заявката, трябва първо да отбележите това
                        съгласие.
                    </p>

                    <p
                        v-if="getFieldError('privacy_consent')"
                        class="field-error mt-3"
                    >
                        {{ getFieldError("privacy_consent") }}
                    </p>
                </div>

                <div class="mt-5">
                    <button
                        type="submit"
                        :disabled="loading || !form.privacy_consent"
                        :aria-busy="loading ? 'true' : 'false'"
                        class="cta-button"
                    >
                        <font-awesome-icon icon="fa-solid fa-paper-plane" />
                        <span v-if="!loading">Направи безплатна консултация</span>
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
            </section>
            </form>
        </transition>

        <transition name="upsell-pop">
            <div
                v-if="showGuarantorUpsell"
                class="upsell-overlay"
                role="dialog"
                aria-modal="true"
                aria-labelledby="guarantor-upsell-title"
            >
                <div class="upsell-backdrop"></div>

                <div class="upsell-dialog">
                    <div class="upsell-grid" aria-hidden="true"></div>
                    <div class="upsell-glow upsell-glow-primary"></div>
                    <div class="upsell-glow upsell-glow-secondary"></div>

                    <div class="upsell-pill">Препоръка</div>
                    <h3 id="guarantor-upsell-title" class="upsell-title">
                        Увеличи шанса си като добавиш поръчител
                    </h3>
                    <p class="upsell-text">
                        Добавянето на поръчител може да помогне за по-силен профил
                        на заявката. Ако искаш, ще ти отворим полетата веднага.
                    </p>

                    <div class="upsell-actions">
                        <button
                            type="button"
                            class="upsell-primary"
                            @click="confirmGuarantorUpsell"
                        >
                            Да, искам
                        </button>
                        <button
                            type="button"
                            class="upsell-secondary"
                            @click="declineGuarantorUpsell"
                        >
                            Не, не искам
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { nextTick, ref, watch } from "vue";
import { useLeadForm } from "../../composables/useLeadForm";
import { getInitialData } from "../../utils/appConfig";

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

const successPanel = ref(null);
const guarantorFirstNameInput = ref(null);
const showGuarantorUpsell = ref(false);
const leadConsentDocument = getInitialData("leadConsentDocument", {
    name: "Съгласие за обработка на лични данни",
    url: "/documents/legal/lead-personal-data-consent-v1.pdf",
});

const {
    form,
    loading,
    success,
    submitError,
    getFieldError,
    isMortgage,
    isConsumerWithGuarantor,
    shouldOfferGuarantorUpsell,
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
    switchToConsumerWithGuarantor,
} = useLeadForm({
    initialCreditType: props.initialCreditType,
    lockCreditType: props.lockCreditType,
});

async function handleSubmit() {
    const result = await submitForm();

    if (result?.status === "show_guarantor_upsell") {
        showGuarantorUpsell.value = true;
    }
}

async function confirmGuarantorUpsell() {
    showGuarantorUpsell.value = false;
    switchToConsumerWithGuarantor();

    await nextTick();
    guarantorFirstNameInput.value?.focus();
    guarantorFirstNameInput.value?.scrollIntoView({
        behavior: "smooth",
        block: "center",
    });
}

async function declineGuarantorUpsell() {
    showGuarantorUpsell.value = false;

    if (!shouldOfferGuarantorUpsell.value) {
        return;
    }

    await submitForm({
        skipGuarantorUpsell: true,
    });
}

watch(success, async (isSuccess) => {
    if (!isSuccess) {
        return;
    }

    await nextTick();
    successPanel.value?.focus();
    successPanel.value?.scrollIntoView({
        behavior: "smooth",
        block: "center",
    });
});
</script>

<style scoped>
.success-popover {
    position: relative;
    overflow: hidden;
    min-height: 440px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    border-radius: 34px;
    text-align: center;
    background:
        radial-gradient(
            circle at top left,
            color-mix(in oklab, var(--color-accent-soft) 90%, white) 0%,
            transparent 42%
        ),
        linear-gradient(
            145deg,
            color-mix(in oklab, var(--color-accent-soft) 82%, white) 0%,
            color-mix(in oklab, var(--color-surface) 96%, var(--color-accent-soft))
                54%,
            color-mix(in oklab, var(--color-accent-soft) 52%, white) 100%
        );
    border: 1px solid color-mix(
        in oklab,
        var(--color-accent-soft-border) 84%,
        white
    );
    box-shadow:
        0 30px 80px rgba(11, 79, 91, 0.18),
        inset 0 1px 0 rgba(255, 255, 255, 0.82);
    outline: none;
    isolation: isolate;
}

.success-grid {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(
            90deg,
            transparent 0,
            transparent calc(50% - 0.5px),
            rgba(11, 79, 91, 0.04) calc(50% - 0.5px),
            rgba(11, 79, 91, 0.04) calc(50% + 0.5px),
            transparent calc(50% + 0.5px)
        ),
        linear-gradient(
            rgba(11, 79, 91, 0.04) 1px,
            transparent 1px
        );
    background-size: 100% 100%, 100% 26px;
    mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.28), transparent 72%);
    pointer-events: none;
}

.success-glow {
    position: absolute;
    border-radius: 9999px;
    filter: blur(14px);
    opacity: 0.72;
    z-index: -1;
}

.success-glow-primary {
    width: 260px;
    height: 260px;
    top: -70px;
    right: -55px;
    background: color-mix(in oklab, var(--color-accent) 26%, white);
}

.success-glow-secondary {
    width: 220px;
    height: 220px;
    bottom: -55px;
    left: -30px;
    background: color-mix(in oklab, var(--color-success) 22%, white);
}

.success-spark {
    position: absolute;
    width: 10px;
    height: 10px;
    border-radius: 9999px;
    background: color-mix(in oklab, var(--color-accent) 72%, white);
    box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.45);
    opacity: 0.78;
    animation: success-float 4.2s ease-in-out infinite;
}

.success-spark-1 {
    top: 92px;
    left: 16%;
}

.success-spark-2 {
    top: 138px;
    right: 15%;
    width: 8px;
    height: 8px;
    animation-delay: 0.8s;
}

.success-spark-3 {
    bottom: 108px;
    right: 26%;
    width: 12px;
    height: 12px;
    animation-delay: 1.6s;
}

.success-shell {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 40rem;
    padding: 2rem 1.5rem;
    border-radius: 28px;
    background: color-mix(in oklab, var(--color-surface) 86%, white);
    border: 1px solid rgba(255, 255, 255, 0.72);
    box-shadow:
        0 18px 44px rgba(11, 79, 91, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
}

.success-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 0.45rem 0.9rem;
    border-radius: 9999px;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--color-accent-darkened);
    background: color-mix(in oklab, var(--color-accent-soft) 68%, white);
    border: 1px solid color-mix(
        in oklab,
        var(--color-accent-soft-border) 78%,
        white
    );
}

.success-badge-wrap {
    position: relative;
    display: grid;
    place-items: center;
    width: 128px;
    height: 128px;
    margin: 1.35rem auto 0;
}

.success-ring {
    position: absolute;
    inset: 0;
    border-radius: 9999px;
    border: 1px solid color-mix(
        in oklab,
        var(--color-accent-darkened) 26%,
        white
    );
    animation: success-pulse 2.4s ease-out infinite;
}

.success-ring-inner {
    inset: 12px;
    animation-delay: 0.35s;
}

.success-badge {
    position: relative;
    z-index: 1;
    width: 88px;
    height: 88px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    font-size: 2rem;
    color: var(--color-surface);
    background: linear-gradient(
        180deg,
        color-mix(in oklab, var(--color-accent) 80%, white) 0%,
        var(--color-accent-darkened) 100%
    );
    box-shadow:
        0 18px 38px rgba(11, 79, 91, 0.28),
        inset 0 1px 0 rgba(255, 255, 255, 0.26);
    animation: success-badge-bounce 0.75s cubic-bezier(0.22, 1, 0.36, 1);
    overflow: hidden;
}

.success-badge::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.35) 0%,
        rgba(255, 255, 255, 0) 52%
    );
}

.success-kicker {
    margin-top: 0.45rem;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--color-accent-darkened);
}

.success-title {
    margin-top: 0.8rem;
    font-size: clamp(2rem, 4.5vw, 2.9rem);
    line-height: 1;
    font-weight: 900;
    letter-spacing: -0.04em;
    color: var(--color-accent-ink);
}

.success-text {
    margin-top: 1rem;
    max-width: 31rem;
    font-size: 1.02rem;
    line-height: 1.8;
    font-weight: 600;
    color: var(--color-text-muted);
}

.success-meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.65rem;
    margin-top: 1.35rem;
}

.success-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0.55rem 0.95rem;
    border-radius: 9999px;
    font-size: 0.84rem;
    font-weight: 800;
    color: var(--color-accent-ink);
    background: color-mix(in oklab, var(--color-accent-soft) 58%, white);
    border: 1px solid color-mix(
        in oklab,
        var(--color-accent-soft-border) 70%,
        white
    );
}

.success-note {
    margin-top: 1rem;
    font-size: 0.92rem;
    line-height: 1.6;
    font-weight: 700;
    color: var(--color-text-subtle);
}

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

.range-control {
    flex: 1 1 auto;
    padding-inline: 0.45rem;
}

.credit-range {
    --range-progress: 0%;
    --range-active: var(--color-accent-darkened);
    --range-track: color-mix(
        in oklab,
        var(--color-accent-soft-border) 68%,
        var(--color-surface) 32%
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
    border: 4px solid var(--color-accent-darkened);
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
    border: 4px solid var(--color-accent-darkened);
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
    box-shadow:
        0 0 0 3px color-mix(in oklab, var(--color-error, #dc2626) 10%, white);
}

.field-error {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--color-error, #dc2626);
    padding-left: 0.1rem;
}

.consent-panel {
    border-radius: 24px;
    border: 1px solid color-mix(
        in oklab,
        var(--color-accent-soft-border) 80%,
        white
    );
    background:
        linear-gradient(
            180deg,
            color-mix(in oklab, var(--color-accent-soft) 42%, white) 0%,
            color-mix(in oklab, var(--color-surface) 95%, var(--color-accent-soft))
                100%
        );
    padding: 1rem 1rem 1rem 1.05rem;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.82),
        0 10px 24px rgba(15, 23, 42, 0.05);
}

.consent-panel-error {
    border-color: color-mix(in oklab, var(--color-error, #dc2626) 46%, white);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.82),
        0 0 0 3px color-mix(in oklab, var(--color-error, #dc2626) 10%, white);
}

.consent-check {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
}

.consent-checkbox {
    width: 1.18rem;
    height: 1.18rem;
    flex-shrink: 0;
    margin-top: 0.08rem;
    accent-color: var(--color-accent-darkened);
    cursor: pointer;
}

.consent-copy {
    font-size: 0.92rem;
    line-height: 1.7;
    font-weight: 600;
    color: var(--color-accent-ink);
}

.consent-link {
    color: var(--color-accent-darkened);
    font-weight: 800;
    text-decoration: underline;
    text-decoration-thickness: 2px;
    text-underline-offset: 3px;
}

.consent-link:hover {
    color: var(--color-accent);
}

.consent-note {
    margin-top: 0.75rem;
    padding-left: 2.05rem;
    font-size: 0.78rem;
    line-height: 1.6;
    font-weight: 700;
    color: var(--color-text-subtle);
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

.upsell-overlay {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
}

.upsell-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.46);
    backdrop-filter: blur(6px);
}

.upsell-dialog {
    position: relative;
    overflow: hidden;
    width: min(100%, 34rem);
    padding: 2rem 1.4rem 1.45rem;
    border-radius: 30px;
    background:
        radial-gradient(
            circle at top right,
            color-mix(in oklab, var(--color-accent-soft) 82%, white) 0%,
            transparent 44%
        ),
        linear-gradient(
            155deg,
            color-mix(in oklab, var(--color-accent-soft) 74%, white) 0%,
            color-mix(in oklab, var(--color-surface) 96%, var(--color-accent-soft))
                58%,
            color-mix(in oklab, var(--color-accent-soft) 42%, white) 100%
        );
    border: 1px solid color-mix(
        in oklab,
        var(--color-accent-soft-border) 82%,
        white
    );
    box-shadow:
        0 28px 70px rgba(15, 23, 42, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.82);
    text-align: center;
}

.upsell-grid {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(
            90deg,
            transparent 0,
            transparent calc(50% - 0.5px),
            rgba(11, 79, 91, 0.04) calc(50% - 0.5px),
            rgba(11, 79, 91, 0.04) calc(50% + 0.5px),
            transparent calc(50% + 0.5px)
        ),
        linear-gradient(rgba(11, 79, 91, 0.04) 1px, transparent 1px);
    background-size: 100% 100%, 100% 24px;
    mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.3), transparent 78%);
    pointer-events: none;
}

.upsell-glow {
    position: absolute;
    border-radius: 9999px;
    filter: blur(18px);
    opacity: 0.72;
    z-index: -1;
}

.upsell-glow-primary {
    width: 190px;
    height: 190px;
    top: -60px;
    right: -34px;
    background: color-mix(in oklab, var(--color-accent) 26%, white);
}

.upsell-glow-secondary {
    width: 170px;
    height: 170px;
    bottom: -54px;
    left: -26px;
    background: color-mix(in oklab, var(--color-success) 20%, white);
}

.upsell-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 0.45rem 0.9rem;
    border-radius: 9999px;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--color-accent-darkened);
    background: color-mix(in oklab, var(--color-accent-soft) 68%, white);
    border: 1px solid color-mix(
        in oklab,
        var(--color-accent-soft-border) 78%,
        white
    );
}

.upsell-title {
    margin-top: 1rem;
    font-size: clamp(1.8rem, 4.6vw, 2.6rem);
    line-height: 1.05;
    font-weight: 900;
    letter-spacing: -0.04em;
    color: var(--color-accent-ink);
}

.upsell-text {
    margin: 0.95rem auto 0;
    max-width: 27rem;
    font-size: 1rem;
    line-height: 1.75;
    font-weight: 600;
    color: var(--color-text-muted);
}

.upsell-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
    margin-top: 1.4rem;
}

.upsell-primary,
.upsell-secondary {
    min-height: 54px;
    border-radius: 18px;
    padding: 0 1rem;
    font-size: 0.95rem;
    font-weight: 900;
    border: 0;
    cursor: pointer;
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease,
        filter 0.18s ease;
}

.upsell-primary {
    color: var(--color-surface);
    background: linear-gradient(
        180deg,
        color-mix(in oklab, var(--color-accent) 88%, white) 0%,
        var(--color-accent-darkened) 100%
    );
    box-shadow:
        0 14px 28px rgba(15, 23, 42, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.24);
}

.upsell-secondary {
    color: var(--color-accent-ink);
    background: rgba(255, 255, 255, 0.72);
    border: 1px solid color-mix(
        in oklab,
        var(--color-accent-soft-border) 72%,
        white
    );
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.upsell-primary:hover,
.upsell-secondary:hover {
    transform: translateY(-1px);
}

.success-swap-enter-active,
.success-swap-leave-active {
    transition:
        opacity 0.36s ease,
        transform 0.36s ease,
        filter 0.36s ease;
}

.success-swap-enter-from,
.success-swap-leave-to {
    opacity: 0;
    transform: translateY(14px) scale(0.985);
    filter: blur(6px);
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

.upsell-pop-enter-active,
.upsell-pop-leave-active {
    transition: opacity 0.24s ease;
}

.upsell-pop-enter-active .upsell-dialog,
.upsell-pop-leave-active .upsell-dialog {
    transition:
        transform 0.28s ease,
        opacity 0.28s ease,
        filter 0.28s ease;
}

.upsell-pop-enter-from,
.upsell-pop-leave-to {
    opacity: 0;
}

.upsell-pop-enter-from .upsell-dialog,
.upsell-pop-leave-to .upsell-dialog {
    opacity: 0;
    transform: translateY(10px) scale(0.985);
    filter: blur(8px);
}

@keyframes success-badge-bounce {
    0% {
        opacity: 0;
        transform: scale(0.72);
    }

    55% {
        opacity: 1;
        transform: scale(1.06);
    }

    100% {
        transform: scale(1);
    }
}

@keyframes success-pulse {
    0% {
        opacity: 0.5;
        transform: scale(0.9);
    }

    70% {
        opacity: 0;
        transform: scale(1.14);
    }

    100% {
        opacity: 0;
        transform: scale(1.14);
    }
}

@keyframes success-float {
    0%,
    100% {
        transform: translateY(0);
        opacity: 0.72;
    }

    50% {
        transform: translateY(-10px);
        opacity: 1;
    }
}

@media (prefers-reduced-motion: reduce) {
    .success-ring,
    .success-badge,
    .success-spark,
    .success-swap-enter-active,
    .success-swap-leave-active,
    .upsell-pop-enter-active,
    .upsell-pop-leave-active {
        animation: none !important;
        transition: none !important;
    }
}
</style>
