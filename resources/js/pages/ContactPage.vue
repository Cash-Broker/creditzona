<template>
    <div class="mx-auto max-w-6xl px-4 py-10 md:py-12">
        <section class="max-w-3xl">
            <span
                class="inline-flex items-center rounded-full border border-accent-soft-border bg-accent-soft px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-accent-darkened"
            >
                Ð’Ñ€ÑŠÐ·ÐºÐ° Ñ Ð½Ð°Ñ
            </span>

            <h1
                class="mt-4 text-3xl font-extrabold tracking-tight text-text md:text-4xl"
            >
                ÐšÐ¾Ð½Ñ‚Ð°ÐºÑ‚Ð¸
            </h1>

            <p class="mt-4 text-sm leading-7 text-text-muted sm:text-base">
                ÐÐºÐ¾ Ð¸Ð¼Ð°Ñ‚Ðµ Ð²ÑŠÐ¿Ñ€Ð¾ÑÐ¸ Ð¸Ð»Ð¸ Ð½ÑƒÐ¶Ð´Ð° Ð¾Ñ‚ ÐºÐ¾Ð½ÑÑƒÐ»Ñ‚Ð°Ñ†Ð¸Ñ, Ð¸Ð·Ð¿Ñ€Ð°Ñ‚ÐµÑ‚Ðµ ÑÑŠÐ¾Ð±Ñ‰ÐµÐ½Ð¸Ðµ
                Ñ‡Ñ€ÐµÐ· Ñ„Ð¾Ñ€Ð¼Ð°Ñ‚Ð°. Ð©Ðµ ÑÐµ ÑÐ²ÑŠÑ€Ð¶ÐµÐ¼ Ñ Ð²Ð°Ñ Ñ Ð½Ð°ÑÐ¾ÐºÐ° ÑÐ¿Ñ€ÑÐ¼Ð¾ ÐºÐ¾Ð½ÐºÑ€ÐµÑ‚Ð½Ð¸Ñ
                ÑÐ»ÑƒÑ‡Ð°Ð¹.
            </p>
        </section>

        <section class="mt-10 grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
            <aside class="space-y-4">
                <div class="content-section">
                    <h2 class="text-xl font-semibold text-text">
                        Ð˜Ð½Ñ„Ð¾Ñ€Ð¼Ð°Ñ†Ð¸Ñ Ð·Ð° ÐºÐ¾Ð½Ñ‚Ð°ÐºÑ‚
                    </h2>

                    <p class="mt-3 text-sm leading-7 text-text-muted">
                        Ð¡Ð²ÑŠÑ€Ð¶ÐµÑ‚Ðµ ÑÐµ Ñ Ð½Ð°Ñ Ð´Ð¸Ñ€ÐµÐºÑ‚Ð½Ð¾ Ð¿Ð¾ Ñ‚ÐµÐ»ÐµÑ„Ð¾Ð½ Ð¸Ð»Ð¸ Ð¸Ð¼ÐµÐ¹Ð», Ð¸Ð»Ð¸
                        Ð¸Ð·Ð¿Ð¾Ð»Ð·Ð²Ð°Ð¹Ñ‚Ðµ Ñ„Ð¾Ñ€Ð¼Ð°Ñ‚Ð° Ð·Ð° Ð¾Ð±Ñ‰Ð¾ Ð·Ð°Ð¿Ð¸Ñ‚Ð²Ð°Ð½Ðµ.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-border bg-surface p-5 shadow-sm"
                >
                    <div
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened"
                    >
                        <font-awesome-icon icon="fa-solid fa-location-dot" />
                    </div>

                    <h3 class="mt-3 text-base font-semibold text-text">
                        ÐÐ´Ñ€ÐµÑ
                    </h3>

                    <p class="mt-2 text-sm text-text-muted">{{ city }}</p>
                    <p class="mt-1 text-sm text-text-muted">{{ address }}</p>

                    <a
                        :href="googleMapsUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg border border-accent-soft-border bg-accent-soft px-3 py-2 text-sm font-medium text-accent-darkened transition-colors hover:text-accent-ink"
                    >
                        Ð’Ð¸Ð¶ Ð² Google Maps
                    </a>
                </div>

                <div
                    class="rounded-2xl border border-border bg-surface p-5 shadow-sm"
                >
                    <div
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened"
                    >
                        <font-awesome-icon icon="fa-solid fa-phone" />
                    </div>

                    <h3 class="mt-3 text-base font-semibold text-text">
                        Ð¢ÐµÐ»ÐµÑ„Ð¾Ð½
                    </h3>

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

                <div
                    class="rounded-2xl border border-border bg-surface p-5 shadow-sm"
                >
                    <div
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened"
                    >
                        <font-awesome-icon icon="fa-solid fa-envelope" />
                    </div>

                    <h3 class="mt-3 text-base font-semibold text-text">
                        Ð˜Ð¼ÐµÐ¹Ð»
                    </h3>

                    <a
                        :href="`mailto:${email}`"
                        class="mt-2 block text-sm font-medium text-accent-darkened hover:underline"
                    >
                        {{ email }}
                    </a>
                </div>

                <div
                    class="rounded-2xl border border-border bg-surface p-5 shadow-sm"
                >
                    <div
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-accent-soft text-accent-darkened"
                    >
                        <font-awesome-icon icon="fa-solid fa-clock" />
                    </div>

                    <h3 class="mt-3 text-base font-semibold text-text">
                        Ð Ð°Ð±Ð¾Ñ‚Ð½Ð¾ Ð²Ñ€ÐµÐ¼Ðµ
                    </h3>

                    <p class="mt-2 text-sm text-text-muted">
                        {{ workingDays }}
                    </p>
                    <p class="mt-1 text-sm text-text-muted">
                        {{ workingHours }}
                    </p>
                </div>
            </aside>

            <form
                class="form-card space-y-5"
                @submit.prevent="submitForm"
                novalidate
            >
                <div class="grid gap-4">
                    <div class="grid gap-1.5">
                        <label class="input-label">Ð˜Ð¼Ðµ Ð¸ Ñ„Ð°Ð¼Ð¸Ð»Ð¸Ñ</label>

                        <input
                            v-model="form.full_name"
                            type="text"
                            class="input"
                            placeholder="Ð˜Ð²Ð°Ð½ Ð˜Ð²Ð°Ð½Ð¾Ð²"
                            required
                        />

                        <p
                            v-if="getFieldError('full_name')"
                            class="text-xs text-error"
                        >
                            {{ getFieldError("full_name") }}
                        </p>
                    </div>

                    <div class="grid gap-1.5 sm:grid-cols-2 sm:gap-4">
                        <div class="grid gap-1.5">
                            <label class="input-label">Ð¢ÐµÐ»ÐµÑ„Ð¾Ð½</label>

                            <input
                                v-model="form.phone"
                                type="text"
                                class="input"
                                placeholder="08XXXXXXXX"
                                required
                            />

                            <p
                                v-if="getFieldError('phone')"
                                class="text-xs text-error"
                            >
                                {{ getFieldError("phone") }}
                            </p>
                        </div>

                        <div class="grid gap-1.5">
                            <label class="input-label">Ð˜Ð¼ÐµÐ¹Ð»</label>

                            <input
                                v-model="form.email"
                                type="email"
                                class="input"
                                placeholder="example@email.com"
                                required
                            />

                            <p
                                v-if="getFieldError('email')"
                                class="text-xs text-error"
                            >
                                {{ getFieldError("email") }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <label class="input-label">Ð¡ÑŠÐ¾Ð±Ñ‰ÐµÐ½Ð¸Ðµ</label>

                        <textarea
                            v-model="form.message"
                            class="input min-h-36 resize-y"
                            placeholder="ÐžÐ¿Ð¸ÑˆÐµÑ‚Ðµ Ð½Ð°ÐºÑ€Ð°Ñ‚ÐºÐ¾ Ð²Ð°ÑˆÐ°Ñ‚Ð° ÑÐ¸Ñ‚ÑƒÐ°Ñ†Ð¸Ñ Ð¸ ÐºÐ°ÐºÐ²Ð° Ð½Ð°ÑÐ¾ÐºÐ° Ñ‚ÑŠÑ€ÑÐ¸Ñ‚Ðµ."
                            required
                        ></textarea>

                        <p
                            v-if="getFieldError('message')"
                            class="text-xs text-error"
                        >
                            {{ getFieldError("message") }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="generalError"
                    class="rounded-xl border border-error/30 bg-error/10 px-4 py-3 text-sm text-error"
                >
                    {{ generalError }}
                </p>

                <div v-if="success" class="form-success">
                    Ð¡ÑŠÐ¾Ð±Ñ‰ÐµÐ½Ð¸ÐµÑ‚Ð¾ Ð±ÐµÑˆÐµ Ð¸Ð·Ð¿Ñ€Ð°Ñ‚ÐµÐ½Ð¾ ÑƒÑÐ¿ÐµÑˆÐ½Ð¾.
                </div>

                <button
                    type="submit"
                    class="primary-button cursor-pointer"
                    :disabled="loading"
                >
                    <span v-if="loading">Ð˜Ð·Ð¿Ñ€Ð°Ñ‰Ð°Ð½Ðµ...</span>
                    <span v-else>Ð˜Ð·Ð¿Ñ€Ð°Ñ‚Ð¸ ÑÑŠÐ¾Ð±Ñ‰ÐµÐ½Ð¸Ðµ</span>
                </button>
            </form>
        </section>
    </div>
</template>

<script setup>
import { contactInfo } from "@/data/contactInfo";
import { useContactForm } from "@/composables/useContactForm";

const { form, loading, success, generalError, getFieldError, submitForm } =
    useContactForm();

const {
    city,
    address,
    phones,
    email,
    workingDays,
    workingHours,
    googleMapsUrl,
} = contactInfo;
</script>
