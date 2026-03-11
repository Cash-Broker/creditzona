import { computed, reactive, ref } from "vue";

const amountMin = 5000;
const amountMax = 50000;
const amountStep = 500;

function createInitialForm() {
    return {
        credit_type: "",
        first_name: "",
        last_name: "",
        phone: "",
        email: "",
        city: "",
        amount: amountMin,
    };
}

export function useLeadForm() {
    const loading = ref(false);
    const success = ref(false);
    const form = reactive(createInitialForm());

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

    function resetForm() {
        Object.assign(form, createInitialForm());
    }

    async function submitForm() {
        loading.value = true;
        success.value = false;

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

            const response = await fetch("/leads", {
                method: "POST",
                headers,
                body: JSON.stringify(form),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            success.value = true;
            resetForm();
        } catch (error) {
            console.error("Lead submit error:", error);
        } finally {
            loading.value = false;
        }
    }

    return {
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
        resetForm,
    };
}
