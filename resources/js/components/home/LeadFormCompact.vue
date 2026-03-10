<template>
    <form class="mt-5 grid gap-4">
        <input type="hidden" name="service_type" value="" />
        <input type="hidden" name="source" value="" />
        <input type="hidden" name="utm_source" value="" />
        <input type="hidden" name="utm_campaign" value="" />
        <input type="hidden" name="utm_medium" value="" />
        <input type="hidden" name="gclid" value="" />

        <div class="grid gap-1.5">
            <label for="full_name" class="text-sm font-medium text-secondary">
                Име и фамилия
            </label>
            <input
                id="full_name"
                name="full_name"
                type="text"
                placeholder="Иван Иванов"
                class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft"
                required
            />
        </div>

        <div class="grid gap-1.5">
            <label for="phone" class="text-sm font-medium text-secondary">
                Телефон
            </label>
            <input
                id="phone"
                name="phone"
                type="text"
                placeholder="08XXXXXXXX"
                class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft"
                required
            />
        </div>

        <div class="grid gap-1.5">
            <label for="email" class="text-sm font-medium text-secondary">
                Имейл (по желание)
            </label>
            <input
                id="email"
                name="email"
                type="email"
                placeholder="example@email.com"
                class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft"
            />
        </div>

        <div class="grid gap-1.5">
            <label for="city" class="text-sm font-medium text-secondary">
                Град (по желание)
            </label>
            <input
                id="city"
                name="city"
                type="text"
                placeholder="Пловдив"
                class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft"
            />
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="grid gap-1.5">
                <label for="amount" class="text-sm font-medium text-secondary">
                    Сума (по желание)
                </label>
                <input
                    id="amount"
                    name="amount"
                    type="number"
                    min="0"
                    placeholder="15000"
                    class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft"
                />
            </div>

            <div class="grid gap-1.5">
                <label
                    for="term_months"
                    class="text-sm font-medium text-secondary"
                >
                    Срок в месеци (по желание)
                </label>
                <input
                    id="term_months"
                    name="term_months"
                    type="number"
                    min="1"
                    placeholder="36"
                    class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft"
                />
            </div>
        </div>

        <div class="border-t pt-4">
            <div class="grid gap-1.5">
                <label for="egn" class="text-sm font-medium text-secondary">
                    ЕГН (задължително)
                </label>
                <input
                    id="egn"
                    name="egn"
                    type="text"
                    maxlength="10"
                    placeholder="XXXXXXXXXX"
                    class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft"
                    required
                />
                <small class="text-xs text-text-subtle">
                    Използва се за предварителна оценка и подготовка на оферта.
                </small>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="grid gap-1.5">
                <label
                    for="monthly_income"
                    class="text-sm font-medium text-secondary"
                >
                    Месечен доход (по желание)
                </label>
                <input
                    id="monthly_income"
                    name="monthly_income"
                    type="number"
                    min="0"
                    placeholder="2500"
                    class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft"
                />
            </div>

            <div class="grid gap-1.5">
                <label
                    for="employment_type"
                    class="text-sm font-medium text-secondary"
                >
                    Заетост (по желание)
                </label>
                <select
                    id="employment_type"
                    name="employment_type"
                    class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft bg-surface"
                >
                    <option value="">-- Изберете --</option>
                    <option value="contract">
                        Трудов договор
                    </option>
                    <option value="self_employed">
                        Самоосигуряващ
                    </option>
                    <option value="pensioner">Пенсионер</option>
                    <option value="unemployed">Безработен</option>
                </select>
            </div>
        </div>

        <div class="grid gap-1.5">
            <label
                for="monthly_debt"
                class="text-sm font-medium text-secondary"
            >
                Месечни задължения (по желание)
            </label>
            <input
                id="monthly_debt"
                name="monthly_debt"
                type="number"
                min="0"
                placeholder="300"
                class="w-full rounded-xl border border-border-strong px-4 py-3 outline-none transition focus:border-accent focus:ring-2 focus:ring-accent-soft"
            />
        </div>

        <label class="flex items-start gap-3 text-sm text-secondary">
            <input
                type="checkbox"
                name="consent"
                value="1"
                class="mt-1 h-4 w-4 rounded border-border-strong text-accent focus:ring-accent-hover"
                required
            />
            <span>
                Съгласен/а съм, че данните ми да
                бъдат обработени за целите на
                консултацията.
            </span>
        </label>

        <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl bg-accent px-5 py-3 font-semibold text-primary transition hover:bg-accent-hover"
        >
            Изпрати
        </button>
    </form>
</template>

<script setup></script>
