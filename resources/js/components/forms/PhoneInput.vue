<template>
    <div
        class="phone-field"
        :class="[
            `phone-field--${variant}`,
            { 'phone-field--error': hasError },
        ]"
    >
        <span :id="prefixId" class="phone-field__prefix">{{ PHONE_DIAL_PREFIX }}</span>

        <input
            :id="id"
            ref="inputRef"
            :value="national"
            type="tel"
            inputmode="numeric"
            :autocomplete="autocomplete"
            :placeholder="placeholder"
            :required="required"
            :aria-describedby="prefixId"
            class="phone-field__input"
            @input="onInput"
            @blur="$emit('blur')"
        />
    </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import {
    PHONE_DIAL_PREFIX,
    composeInternationalPhone,
    toNationalDigits,
} from "@/utils/phone";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    id: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "8XX XXX XXX",
    },
    hasError: {
        type: Boolean,
        default: false,
    },
    autocomplete: {
        type: String,
        default: "tel-national",
    },
    required: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: "compact",
        validator: (value) => ["compact", "default"].includes(value),
    },
});

const emit = defineEmits(["update:modelValue", "blur", "input"]);

const inputRef = ref(null);
const national = ref(toNationalDigits(props.modelValue));

const prefixId = computed(() => (props.id ? `${props.id}-prefix` : undefined));

watch(
    () => props.modelValue,
    (value) => {
        const next = toNationalDigits(value);

        if (next !== national.value) {
            national.value = next;
        }
    },
);

function onInput(event) {
    const sanitized = toNationalDigits(event.target.value);

    national.value = sanitized;

    // Vue skips the DOM patch when the bound value is unchanged, so force the
    // field back in sync whenever sanitisation rejected a character.
    if (event.target.value !== sanitized) {
        event.target.value = sanitized;
    }

    emit("update:modelValue", composeInternationalPhone(sanitized));
    emit("input");
}
</script>

<style scoped>
.phone-field {
    display: flex;
    align-items: center;
    width: 100%;
    transition:
        border-color 0.18s ease,
        box-shadow 0.18s ease,
        transform 0.18s ease,
        background 0.18s ease;
}

.phone-field__prefix {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    font-weight: 600;
    color: var(--color-text-muted);
    user-select: none;
    pointer-events: none;
}

.phone-field__input {
    flex: 1 1 auto;
    min-width: 0;
    height: 100%;
    border: 0;
    background: transparent;
    outline: none;
    color: var(--color-text);
    font: inherit;
}

.phone-field__input::placeholder {
    color: var(--color-text-subtle);
    font-weight: 400;
}

/* Compact variant — matches the lead form .input look. */
.phone-field--compact {
    height: 50px;
    border-radius: 14px;
    border: 1px solid var(--color-border-strong);
    background: linear-gradient(
        180deg,
        var(--color-surface) 0%,
        color-mix(in oklab, var(--color-background) 60%, var(--color-surface) 40%)
            100%
    );
    padding: 0 14px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.phone-field--compact .phone-field__prefix {
    font-size: 0.95rem;
    padding-right: 0.5rem;
    margin-right: 0.5rem;
    border-right: 1px solid var(--color-border-strong);
}

.phone-field--compact .phone-field__input {
    font-size: 0.95rem;
    font-weight: 500;
}

.phone-field--compact:focus-within {
    border-color: var(--color-accent);
    background: var(--color-surface);
    box-shadow:
        0 0 0 4px color-mix(in oklab, var(--color-accent) 15%, white),
        0 4px 14px rgba(15, 23, 42, 0.05);
    transform: translateY(-1px);
}

.phone-field--compact.phone-field--error {
    border-color: color-mix(in oklab, var(--color-error, #dc2626) 75%, white);
    box-shadow: 0 0 0 3px color-mix(in oklab, var(--color-error, #dc2626) 10%, white);
}

/* Default variant — matches the global .input look (contact form). */
.phone-field--default {
    border-radius: 0.75rem;
    border: 1px solid var(--color-border);
    background: var(--color-surface);
    padding: 0.75rem 1rem;
    box-shadow: 0 1px 2px rgb(17 24 39 / 0.06);
}

.phone-field--default .phone-field__prefix {
    font-size: 1rem;
    padding-right: 0.625rem;
    margin-right: 0.625rem;
    border-right: 1px solid var(--color-border);
}

.phone-field--default .phone-field__input {
    font-size: 1rem;
}

.phone-field--default:focus-within {
    border-color: var(--color-accent);
    background-color: color-mix(
        in oklab,
        var(--color-surface) 92%,
        var(--color-accent-soft) 8%
    );
    box-shadow:
        0 0 0 4px var(--color-accent-soft),
        0 1px 2px rgb(17 24 39 / 0.06);
}

.phone-field--default.phone-field--error {
    border-color: var(--color-error, #dc2626);
}

@media (prefers-reduced-motion: reduce) {
    .phone-field {
        transition: none;
    }

    .phone-field--compact:focus-within {
        transform: none;
    }
}
</style>
