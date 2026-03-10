<template>
    <form @submit.prevent="submitForm" class="mt-6 space-y-7">
        <section class="form-card">
            <header class="form-section-head">
                <div class="form-section-marker"></div>
                <h3 class="form-section-title">Основни данни</h3>
                <p class="form-section-text">
                    Попълнете най-важната информация, за да започнем
                    консултацията.
                </p>
            </header>

            <div class="grid gap-4">
                <div class="grid gap-1.5">
                    <label class="input-label">Име и фамилия</label>
                    <input
                        v-model="form.full_name"
                        type="text"
                        placeholder="Иван Иванов"
                        class="input"
                        autocomplete="name"
                        required
                    />
                </div>

                <div class="grid gap-1.5">
                    <label class="input-label">Телефон</label>
                    <input
                        v-model="form.phone"
                        type="text"
                        placeholder="08XXXXXXXX"
                        class="input"
                        inputmode="tel"
                        autocomplete="tel"
                        required
                    />
                </div>

                <div class="grid gap-1.5">
                    <label class="input-label">Имейл (по желание)</label>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="example@email.com"
                        class="input"
                        autocomplete="email"
                    />
                </div>

                <div class="grid gap-1.5">
                    <label class="input-label">Град (по желание)</label>
                    <input
                        v-model="form.city"
                        type="text"
                        placeholder="Пловдив"
                        class="input"
                        autocomplete="address-level2"
                    />
                </div>
            </div>
        </section>

        <section class="form-card">
            <header class="form-section-head">
                <div class="form-section-marker"></div>
                <h3 class="form-section-title">Параметри</h3>
                <p class="form-section-text">
                    Не е задължително, но помага за по-точна ориентация.
                </p>
            </header>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <label class="input-label" for="loan-amount"
                            >Сума</label
                        >
                        <span class="amount-value">
                            {{ formattedAmount }}
                        </span>
                    </div>

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

                    <div class="range-labels">
                        <span>{{ formattedMinAmount }}</span>
                        <span>{{ formattedMaxAmount }}</span>
                    </div>

                    <p id="loan-amount-hint" class="text-xs text-text-subtle">
                        Изберете желаната сума за кредит.
                    </p>
                </div>

                <div class="grid gap-1.5 term-field">
                    <label class="input-label">Срок (месеци)</label>
                    <input
                        v-model="form.term_months"
                        type="number"
                        placeholder="36"
                        class="input input-compact"
                    />
                </div>

                <div class="grid gap-1.5">
                    <label class="input-label">Месечен доход</label>
                    <input
                        v-model="form.monthly_income"
                        type="number"
                        placeholder="2500"
                        class="input"
                    />
                </div>

                <div class="grid gap-1.5">
                    <label class="input-label">Заетост</label>
                    <select v-model="form.employment_type" class="input">
                        <option value="">-- Изберете --</option>
                        <option value="contract">Трудов договор</option>
                        <option value="self_employed">Самоосигуряващ</option>
                        <option value="pensioner">Пенсионер</option>
                        <option value="unemployed">Безработен</option>
                    </select>
                </div>

                <div class="grid gap-1.5 sm:col-span-2">
                    <label class="input-label">Месечни задължения</label>
                    <input
                        v-model="form.monthly_debt"
                        type="number"
                        placeholder="300"
                        class="input"
                    />
                </div>
            </div>
        </section>

        <section class="form-card">
            <header class="form-section-head">
                <div class="form-section-marker"></div>
                <h3 class="form-section-title">Идентификация</h3>
                <p class="form-section-text">
                    Нужна е за предварителна оценка и по-точно насочване.
                </p>
            </header>

            <div class="grid gap-1.5">
                <label class="input-label">ЕГН</label>
                <input
                    v-model="form.egn"
                    type="text"
                    maxlength="10"
                    placeholder="XXXXXXXXXX"
                    class="input"
                    required
                />

                <div class="form-note">
                    <font-awesome-icon
                        icon="fa-solid fa-building-columns"
                        class="mt-0.5 text-accent-darkened"
                    />
                    <span>
                        Използва се само за предварителна оценка и не се споделя
                        извън процеса по консултация.
                    </span>
                </div>
            </div>
        </section>

        <section class="form-card">
            <div class="form-consent">
                <label class="flex items-start gap-3 text-sm text-secondary">
                    <input
                        v-model="form.consent"
                        type="checkbox"
                        class="form-checkbox"
                        required
                    />
                    <span class="leading-6">
                        Съгласен/а съм данните ми да бъдат използвани за целите
                        на консултацията.
                    </span>
                </label>
            </div>

            <button
                type="submit"
                :disabled="loading"
                :aria-busy="loading ? 'true' : 'false'"
                class="primary-button mt-5 cursor-pointer"
            >
                <font-awesome-icon icon="fa-solid fa-paper-plane" />
                <span v-if="!loading">Изпрати запитване</span>
                <span v-else>Изпращане...</span>
            </button>

            <p class="mt-3 text-center text-xs text-text-subtle">
                Ще се свържем с вас възможно най-скоро.
            </p>

            <div v-if="success" class="form-success">
                <font-awesome-icon
                    icon="fa-solid fa-circle-check"
                    class="mt-0.5"
                />
                <span>
                    Запитването е изпратено успешно. Ще се свържем с вас скоро.
                </span>
            </div>
        </section>
    </form>
</template>

<script setup>
import { reactive, ref, computed } from "vue";

const loading = ref(false);
const success = ref(false);
const amountMin = 5000;
const amountMax = 50000;
const amountStep = 500;

const form = reactive({
    full_name: "",
    phone: "",
    email: "",
    city: "",
    amount: amountMin,
    term_months: "",
    egn: "",
    monthly_income: "",
    employment_type: "",
    monthly_debt: "",
    consent: false,
});

const formattedAmount = computed(() => {
    return `${Number(form.amount).toLocaleString("bg-BG")} €`;
});
const formattedMinAmount = computed(() => {
    return `${amountMin.toLocaleString("bg-BG")} €`;
});
const formattedMaxAmount = computed(() => {
    return `${amountMax.toLocaleString("bg-BG")} €`;
});
const amountProgress = computed(() => {
    const value = Number(form.amount) || amountMin;
    const clamped = Math.min(amountMax, Math.max(amountMin, value));
    return ((clamped - amountMin) / (amountMax - amountMin)) * 100;
});
const creditRangeStyle = computed(() => ({
    "--range-progress": `${amountProgress.value}%`,
}));

async function submitForm() {
    loading.value = true;
    success.value = false;

    try {
        const response = await fetch("/leads", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "",
                Accept: "application/json",
            },
            body: JSON.stringify(form),
        });

        if (!response.ok) {
            throw new Error("Грешка при изпращане на формата.");
        }

        success.value = true;

        form.full_name = "";
        form.phone = "";
        form.email = "";
        form.city = "";
        form.amount = amountMin;
        form.term_months = "";
        form.egn = "";
        form.monthly_income = "";
        form.employment_type = "";
        form.monthly_debt = "";
        form.consent = false;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>
.amount-value {
    font-size: 1rem;
    font-weight: 700;
    color: #b7791f;
}

.range-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: #7c8798;
    margin-top: 0.2rem;
}

.credit-range {
    --range-progress: 0%;
    --range-active: #d6a11b;
    --range-track: #e7eaf0;

    -webkit-appearance: none;
    appearance: none;
    width: 100%;
    height: 6px;
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
    transition: background 0.2s ease;
}

.credit-range:focus-visible {
    box-shadow: 0 0 0 3px rgba(214, 161, 27, 0.18);
}

.credit-range::-webkit-slider-runnable-track {
    height: 6px;
    border-radius: 9999px;
    background: transparent;
}

.credit-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 9999px;
    background: #ffffff;
    border: 2px solid #d6a11b;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
    cursor: pointer;
    margin-top: -6px;
    transition: transform 0.15s ease;
}

.credit-range::-webkit-slider-thumb:hover {
    transform: scale(1.05);
}

.credit-range::-moz-range-track {
    height: 6px;
    border: none;
    border-radius: 9999px;
    background: #e7eaf0;
}

.credit-range::-moz-range-progress {
    height: 6px;
    border: none;
    border-radius: 9999px;
    background: #d6a11b;
}

.credit-range::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border: 2px solid #d6a11b;
    border-radius: 9999px;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
    cursor: pointer;
}

.term-field {
    align-self: start;
}

.input-compact {
    max-width: 270px;
}
</style>
