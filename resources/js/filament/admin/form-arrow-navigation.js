const focusableFieldSelector = [
    'input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([disabled]):not([readonly])',
    'textarea:not([disabled]):not([readonly])',
    'select:not([disabled])',
    '[role="combobox"][tabindex]:not([tabindex="-1"])',
].join(", ");

function isVisible(element) {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    const style = window.getComputedStyle(element);

    return (
        style.display !== "none" &&
        style.visibility !== "hidden" &&
        element.offsetParent !== null
    );
}

function normalizeFocusableField(element) {
    if (!(element instanceof HTMLElement)) {
        return null;
    }

    if (element.matches('[role="combobox"][tabindex]')) {
        return element;
    }

    return element.closest('[role="combobox"][tabindex]') ?? element;
}

function getFocusableFields(form) {
    return Array.from(form.querySelectorAll(focusableFieldSelector))
        .map((element) => normalizeFocusableField(element))
        .filter((element, index, elements) => {
            if (!(element instanceof HTMLElement) || !isVisible(element)) {
                return false;
            }

            return elements.indexOf(element) === index;
        });
}

function canUseArrowNavigation(target) {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    if (target.matches("textarea, [contenteditable='true']")) {
        return false;
    }

    if (
        target.matches("[role='combobox']") ||
        target.closest("[role='combobox']") ||
        target.closest(".ts-wrapper") ||
        target.closest(".choices")
    ) {
        return false;
    }

    return target.matches("input, select");
}

function focusField(element) {
    if (!(element instanceof HTMLElement)) {
        return;
    }

    element.focus({ preventScroll: true });
    element.scrollIntoView({
        block: "nearest",
        inline: "nearest",
    });

    if (element instanceof HTMLInputElement && element.type !== "number") {
        element.select();
    }
}

document.addEventListener("keydown", (event) => {
    if (!["ArrowDown", "ArrowUp"].includes(event.key)) {
        return;
    }

    if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
        return;
    }

    const target = event.target;

    if (!(target instanceof HTMLElement) || !target.closest(".fi-body")) {
        return;
    }

    if (!canUseArrowNavigation(target)) {
        return;
    }

    const form = target.closest("form");

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const fields = getFocusableFields(form);
    const currentField = normalizeFocusableField(target);
    const currentIndex = fields.findIndex((field) => field === currentField);

    if (currentIndex === -1) {
        return;
    }

    const nextIndex = currentIndex + (event.key === "ArrowDown" ? 1 : -1);
    const nextField = fields[nextIndex];

    if (!(nextField instanceof HTMLElement)) {
        return;
    }

    event.preventDefault();
    focusField(nextField);
});
