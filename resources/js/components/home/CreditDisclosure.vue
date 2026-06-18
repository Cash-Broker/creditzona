<template>
    <section
        :id="sectionId"
        class="content-section"
        aria-labelledby="credit-disclosure-title"
    >
        <component
            :is="headingTag"
            id="credit-disclosure-title"
            class="text-xs font-semibold text-text-muted"
        >
            {{ creditDisclosure.heading }}
        </component>

        <!-- Целият блок е спокоен, ситен fine-print стил (като Xtra/Vivacredit).
             Задължителните факти (ГЛП, ГПР, срок) остават отделни четими редове,
             не в падащо меню. -->
        <p class="mt-2 max-w-3xl text-[11px] leading-[1.7] text-text-subtle">
            {{ creditDisclosure.partnerNote }}
        </p>

        <dl class="mt-3 space-y-1">
            <div
                v-for="item in creditDisclosure.items"
                :key="item.label"
                class="text-[11px] leading-[1.6]"
            >
                <dt class="inline font-semibold text-text-muted">{{ item.label }}:</dt>
                <dd class="inline text-text-subtle">
                    {{ item.value }}<template v-if="item.note">
                        — {{ item.note }}</template
                    >
                </dd>
            </div>
        </dl>

        <p class="mt-3 max-w-3xl text-[11px] leading-[1.7] text-text-subtle">
            {{ creditDisclosure.representativeExample }}
        </p>

        <p class="mt-3 text-[11px] text-text-subtle">
            <span class="font-semibold text-text-muted">Адрес:</span>
            {{ contactInfo.city }}, {{ contactInfo.address }}
        </p>
    </section>
</template>

<script setup>
import { contactInfo } from "@/data/contactInfo";
import { creditDisclosure } from "@/data/creditDisclosure";

defineProps({
    headingTag: {
        type: String,
        default: "h2",
    },
    sectionId: {
        type: String,
        default: "credit-disclosure",
    },
});
</script>
