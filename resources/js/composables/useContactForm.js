import { reactive, ref } from "vue";

function createInitialForm() {
    return {
        full_name: "",
        phone: "",
        email: "",
        message: "",
    };
}

export function useContactForm() {
    const loading = ref(false);
    const success = ref(false);
    const generalError = ref("");
    const errors = reactive({});
    const form = reactive(createInitialForm());

    function clearErrors() {
        generalError.value = "";

        for (const key of Object.keys(errors)) {
            delete errors[key];
        }
    }

    function resetForm() {
        Object.assign(form, createInitialForm());
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

            const rawText = await response.text();
            console.log("STATUS:", response.status);
            console.log("RAW RESPONSE:", rawText);

            let payload = null;

            try {
                payload = rawText ? JSON.parse(rawText) : null;
            } catch (err) {
                console.warn("Response is not valid JSON");
            }

            if (response.status === 422) {
                if (payload?.errors && typeof payload.errors === "object") {
                    Object.assign(errors, payload.errors);
                }

                generalError.value =
                    payload?.message || "Моля, коригирайте маркираните полета.";
                return;
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            success.value = true;
            resetForm();
        } catch (e) {
            console.error("SUBMIT ERROR:", e);
            generalError.value =
                "Възникна проблем при изпращане на съобщението. Моля, опитайте отново след малко.";
        } finally {
            loading.value = false;
        }
    }

    return {
        form,
        loading,
        success,
        generalError,
        errors,
        clearErrors,
        resetForm,
        getFieldError,
        submitForm,
    };
}
