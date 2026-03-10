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
                <div class="grid gap-1.5">
                    <label class="input-label">Сума</label>
                    <input
                        v-model="form.amount"
                        type="number"
                        placeholder="15000"
                        class="input"
                    />
                </div>

                <div class="grid gap-1.5">
                    <label class="input-label">Срок (месеци)</label>
                    <input
                        v-model="form.term_months"
                        type="number"
                        placeholder="36"
                        class="input"
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
                        Използва се само за предварителна оценка и не се
                        споделя извън процеса по консултация.
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
import { reactive, ref } from "vue";

const loading = ref(false);
const success = ref(false);

const form = reactive({
    full_name: "",
    phone: "",
    email: "",
    city: "",
    amount: "",
    term_months: "",
    egn: "",
    monthly_income: "",
    employment_type: "",
    monthly_debt: "",
    consent: false,
});

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
        form.amount = "";
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
