import { computed, reactive, ref, watch } from "vue";

const amountMin = 5000;
const amountMax = 50000;
const amountStep = 500;
const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const allowedCreditTypes = new Set(["consumer", "mortgage"]);
const allowedPropertyTypes = new Set(["house", "apartment"]);

function createInitialForm(initialCreditType = "") {
    return {
        credit_type: initialCreditType,
        first_name: "",
        last_name: "",
        phone: "",
        email: "",
        city: "",
        amount: amountMin,
        property_type: "",
        property_location: "",
    };
}

export function useLeadForm(options = {}) {
    const { initialCreditType = "", lockCreditType = false } = options;

    const loading = ref(false);
    const success = ref(false);
    const submitError = ref("");
    const form = reactive(createInitialForm(initialCreditType));
    const errors = reactive({});
    const touched = reactive({});

    const isMortgage = computed(() => form.credit_type === "mortgage");

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

    watch(
        () => form.credit_type,
        (creditType) => {
            if (creditType !== "mortgage") {
                form.property_type = "";
                form.property_location = "";
                clearFieldError("property_type");
                clearFieldError("property_location");
                delete touched.property_type;
                delete touched.property_location;
            } else {
                validateIfTouched("property_type");
                validateIfTouched("property_location");
            }

            validateIfTouched("credit_type");
        },
    );

    function normalized(value) {
        return typeof value === "string" ? value.trim() : "";
    }

    function setFieldError(field, message) {
        errors[field] = message;
        return false;
    }

    function clearFieldError(field) {
        delete errors[field];
    }

    function clearAllErrors() {
        Object.keys(errors).forEach((key) => delete errors[key]);
    }

    function touchField(field) {
        touched[field] = true;
    }

    function validateIfTouched(field) {
        if (touched[field]) {
            validateField(field);
        }
    }

    function validateField(field) {
        const firstName = normalized(form.first_name);
        const lastName = normalized(form.last_name);
        const phone = normalized(form.phone);
        const email = normalized(form.email);
        const city = normalized(form.city);
        const propertyLocation = normalized(form.property_location);
        const amount = Number(form.amount);

        switch (field) {
            case "credit_type":
                if (!form.credit_type) {
                    return setFieldError(
                        "credit_type",
                        "Моля, изберете тип кредит.",
                    );
                }

                if (!allowedCreditTypes.has(form.credit_type)) {
                    return setFieldError(
                        "credit_type",
                        "Моля, изберете валиден тип кредит.",
                    );
                }

                break;
            case "first_name":
                if (!firstName) {
                    return setFieldError(
                        "first_name",
                        "Моля, въведете вашето име.",
                    );
                }

                if (firstName.length > 60) {
                    return setFieldError(
                        "first_name",
                        "Името не може да бъде по-дълго от 60 символа.",
                    );
                }

                break;
            case "last_name":
                if (!lastName) {
                    return setFieldError(
                        "last_name",
                        "Моля, въведете вашата фамилия.",
                    );
                }

                if (lastName.length > 60) {
                    return setFieldError(
                        "last_name",
                        "Фамилията не може да бъде по-дълга от 60 символа.",
                    );
                }

                break;
            case "phone":
                if (!phone) {
                    return setFieldError(
                        "phone",
                        "Моля, въведете телефон за връзка.",
                    );
                }

                if (phone.length > 30) {
                    return setFieldError(
                        "phone",
                        "Телефонът не може да бъде по-дълъг от 30 символа.",
                    );
                }

                break;
            case "email":
                if (!email) {
                    return setFieldError(
                        "email",
                        "Моля, въведете имейл адрес.",
                    );
                }

                if (email.length > 120) {
                    return setFieldError(
                        "email",
                        "Имейл адресът не може да бъде по-дълъг от 120 символа.",
                    );
                }

                if (!emailPattern.test(email)) {
                    return setFieldError(
                        "email",
                        "Моля, въведете валиден имейл адрес.",
                    );
                }

                break;
            case "city":
                if (!city) {
                    return setFieldError(
                        "city",
                        "Моля, въведете вашия град.",
                    );
                }

                if (city.length > 120) {
                    return setFieldError(
                        "city",
                        "Градът не може да бъде по-дълъг от 120 символа.",
                    );
                }

                break;
            case "amount":
                if (!Number.isInteger(amount)) {
                    return setFieldError(
                        "amount",
                        "Сумата трябва да бъде цяло число.",
                    );
                }

                if (amount < amountMin) {
                    return setFieldError(
                        "amount",
                        "Сумата трябва да бъде поне 5000.",
                    );
                }

                if (amount > amountMax) {
                    return setFieldError(
                        "amount",
                        "Сумата не може да бъде повече от 50000.",
                    );
                }

                break;
            case "property_type":
                if (!isMortgage.value) {
                    break;
                }

                if (!form.property_type) {
                    return setFieldError(
                        "property_type",
                        "Моля, изберете вид на имота.",
                    );
                }

                if (!allowedPropertyTypes.has(form.property_type)) {
                    return setFieldError(
                        "property_type",
                        "Моля, изберете валиден вид на имота.",
                    );
                }

                break;
            case "property_location":
                if (!isMortgage.value) {
                    break;
                }

                if (!propertyLocation) {
                    return setFieldError(
                        "property_location",
                        "Моля, въведете местонахождение на имота.",
                    );
                }

                if (propertyLocation.length > 120) {
                    return setFieldError(
                        "property_location",
                        "Местонахождението на имота не може да бъде по-дълго от 120 символа.",
                    );
                }

                break;
            default:
                break;
        }

        clearFieldError(field);
        return true;
    }

    function validateForm() {
        const fields = [
            "credit_type",
            "first_name",
            "last_name",
            "phone",
            "email",
            "city",
            "amount",
        ];

        if (isMortgage.value) {
            fields.push("property_type", "property_location");
        }

        fields.forEach((field) => touchField(field));

        return fields.every((field) => validateField(field));
    }

    function handleBlur(field) {
        touchField(field);
        validateField(field);
    }

    function handleInput(field) {
        validateIfTouched(field);
    }

    function applyServerErrors(responseData) {
        if (!responseData?.errors || typeof responseData.errors !== "object") {
            return;
        }

        Object.entries(responseData.errors).forEach(([field, fieldErrors]) => {
            if (Array.isArray(fieldErrors) && fieldErrors.length > 0) {
                errors[field] = String(fieldErrors[0]);
                touched[field] = true;
            }
        });
    }

    function resetForm() {
        Object.assign(form, createInitialForm(initialCreditType));
        clearAllErrors();
        Object.keys(touched).forEach((key) => delete touched[key]);
        submitError.value = "";
    }

    async function submitForm() {
        success.value = false;
        submitError.value = "";

        if (!validateForm()) {
            return;
        }

        loading.value = true;

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

            const payload = {
                ...form,
                property_type: isMortgage.value ? form.property_type : null,
                property_location: isMortgage.value
                    ? form.property_location
                    : null,
            };

            const response = await fetch("/leads", {
                method: "POST",
                headers,
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const responseData = await response
                    .json()
                    .catch(() => null);

                if (response.status === 422) {
                    applyServerErrors(responseData);
                    submitError.value =
                        responseData?.message ??
                        "Моля, проверете въведените данни.";
                    return;
                }

                throw new Error(`HTTP ${response.status}`);
            }

            success.value = true;
            resetForm();
        } catch (error) {
            console.error("Lead submit error:", error);
            submitError.value =
                "Възникна грешка при изпращането на заявката. Моля, опитайте отново.";
        } finally {
            loading.value = false;
        }
    }

    return {
        form,
        loading,
        success,
        submitError,
        errors,
        isMortgage,
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
        resetForm,
    };
}
