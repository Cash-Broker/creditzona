<template>
    <div class="mx-auto max-w-6xl px-4 py-10 md:py-12">
        <section class="max-w-3xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Връзка с нас
            </span>

            <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-text md:text-4xl">
                Контакти
            </h1>

            <p class="mt-4 text-sm leading-7 text-text-muted sm:text-base">
                Ако имате въпроси или нужда от консултация, изпратете съобщение
                чрез формата. Ще се свържем с вас с насока спрямо конкретния
                случай.
            </p>
        </section>

        <section class="mt-10 grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
            <aside class="space-y-4">
                <div class="content-section">
                    <h2 class="text-xl font-semibold text-text">
                        Информация за контакт
                    </h2>

                    <p class="mt-3 text-sm leading-7 text-text-muted">
                        Свържете се с нас директно по телефон или имейл, или
                        използвайте формата за общо запитване.
                    </p>
                </div>

                <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                    <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened">
                        <font-awesome-icon icon="fa-solid fa-location-dot" />
                    </div>

                    <h3 class="mt-3 text-base font-semibold text-text">Адрес</h3>

                    <p class="mt-2 text-sm text-text-muted">{{ city }}</p>
                    <p class="mt-1 text-sm text-text-muted">{{ address }}</p>

                    <a
                        :href="googleMapsUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg border border-accent-soft-border bg-accent-soft px-3 py-2 text-sm font-medium text-accent-darkened transition-colors hover:text-accent-ink"
                    >
                        Виж в Google Maps
                    </a>
                </div>

                <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                    <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened">
                        <font-awesome-icon icon="fa-solid fa-phone" />
                    </div>

                    <h3 class="mt-3 text-base font-semibold text-text">Телефон</h3>

                    <div class="mt-2 space-y-1.5">
                        <a
                            v-for="phone in phones"
                            :key="phone"
                            :href="`tel:${phone}`"
                            class="block text-sm font-medium text-accent-darkened hover:underline"
                        >
                            {{ phone }}
                        </a>
                    </div>
                </div>

                <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                    <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened">
                        <font-awesome-icon icon="fa-solid fa-envelope" />
                    </div>

                    <h3 class="mt-3 text-base font-semibold text-text">Имейл</h3>

                    <a
                        :href="`mailto:${email}`"
                        class="mt-2 block text-sm font-medium text-accent-darkened hover:underline"
                    >
                        {{ email }}
                    </a>
                </div>

                <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                    <div class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened">
                        <font-awesome-icon icon="fa-solid fa-clock" />
                    </div>

                    <h3 class="mt-3 text-base font-semibold text-text">Работно време</h3>

                    <p class="mt-2 text-sm text-text-muted">{{ workingDays }}</p>
                    <p class="mt-1 text-sm text-text-muted">{{ workingHours }}</p>
                </div>
            </aside>

            <form class="form-card space-y-5" @submit.prevent="submitForm" novalidate>
                <div class="grid gap-4">
                    <div class="grid gap-1.5">
                        <label class="input-label">Име и фамилия</label>

                        <input
                            v-model="form.full_name"
                            type="text"
                            class="input"
                            placeholder="Иван Иванов"
                            required
                        />

                        <p v-if="getFieldError('full_name')" class="text-xs text-error">
                            {{ getFieldError("full_name") }}
                        </p>
                    </div>

                    <div class="grid gap-1.5 sm:grid-cols-2 sm:gap-4">
                        <div class="grid gap-1.5">
                            <label class="input-label">Телефон</label>

                            <input
                                v-model="form.phone"
                                type="text"
                                class="input"
                                placeholder="08XXXXXXXX"
                            />

                            <p v-if="getFieldError('phone')" class="text-xs text-error">
                                {{ getFieldError("phone") }}
                            </p>
                        </div>

                        <div class="grid gap-1.5">
                            <label class="input-label">Имейл</label>

                            <input
                                v-model="form.email"
                                type="email"
                                class="input"
                                placeholder="example@email.com"
                            />

                            <p v-if="getFieldError('email')" class="text-xs text-error">
                                {{ getFieldError("email") }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs leading-5 text-text-subtle">
                        Посочете поне един канал за обратна връзка: телефон или
                        имейл.
                    </p>

                    <div class="grid gap-1.5">
                        <label class="input-label">Тема</label>

                        <input
                            v-model="form.subject"
                            type="text"
                            class="input"
                            placeholder="Напр. Консултация за рефинансиране"
                        />

                        <p v-if="getFieldError('subject')" class="text-xs text-error">
                            {{ getFieldError("subject") }}
                        </p>
                    </div>

                    <div class="grid gap-1.5">
                        <label class="input-label">Съобщение</label>

                        <textarea
                            v-model="form.message"
                            class="input min-h-36 resize-y"
                            placeholder="Опишете накратко вашата ситуация и каква насока търсите."
                            required
                        ></textarea>

                        <p v-if="getFieldError('message')" class="text-xs text-error">
                            {{ getFieldError("message") }}
                        </p>
                    </div>
                </div>

                <p v-if="generalError" class="rounded-xl border border-error/30 bg-error/10 px-4 py-3 text-sm text-error">
                    {{ generalError }}
                </p>

                <div v-if="success" class="form-success">
                    Съобщението беше изпратено успешно.
                </div>

                <button type="submit" class="primary-button cursor-pointer" :disabled="loading">
                    <span v-if="loading">Изпращане...</span>
                    <span v-else>Изпрати съобщение</span>
                </button>
            </form>
        </section>
    </div>
</template>

<script setup>
import { reactive, ref } from "vue";

const city = "гр. Пловдив";
const address = "ул. Полк. Сава Муткуров 30";
const phones = ["0879000685", "0887703365"];
const email = "office@creditzona.bg";
const workingDays = "Понеделник – Петък";
const workingHours = "9:00 – 18:00";
const googleMapsUrl = "https://www.google.com/maps?cid=17488259411281683573";

const loading = ref(false);
const success = ref(false);
const generalError = ref("");
const errors = reactive({});

const form = reactive({
    full_name: "",
    phone: "",
    email: "",
    subject: "",
    message: "",
});

function clearErrors() {
    generalError.value = "";

    for (const key of Object.keys(errors)) {
        delete errors[key];
    }
}

function resetForm() {
    form.full_name = "";
    form.phone = "";
    form.email = "";
    form.subject = "";
    form.message = "";
}

function getFieldError(field) {
    const fieldErrors = errors[field];

    if (Array.isArray(fieldErrors) && fieldErrors.length > 0) {
        return fieldErrors[0];
    }

    return "";
}

async function submitForm() {
    loading.value = true;
    success.value = false;
    clearErrors();

    try {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        const headers = {
            "Content-Type": "application/json",
            Accept: "application/json",
        };

        if (csrfToken) {
            headers["X-CSRF-TOKEN"] = csrfToken;
        }

        const response = await fetch("/api/contact-messages", {
            method: "POST",
            headers,
            body: JSON.stringify(form),
        });

        const payload = await response.json().catch(() => null);

        if (response.status === 422) {
            if (payload?.errors && typeof payload.errors === "object") {
                Object.assign(errors, payload.errors);
            }

            generalError.value =
                payload?.message || "Моля, коригирайте маркираните полета.";
            return;
        }

        if (!response.ok) {
            throw new Error("Неуспешно изпращане на съобщението.");
        }

        success.value = true;
        resetForm();
    } catch (e) {
        console.error(e);
        generalError.value =
            "Възникна проблем при изпращане на съобщението. Моля, опитайте отново след малко.";
    } finally {
        loading.value = false;
    }
}
</script>
