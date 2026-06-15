export const PHONE_DIAL_PREFIX = "+359";

const MAX_NATIONAL_LENGTH = 9;

/**
 * Reduce any user input to the Bulgarian national subscriber digits, mirroring
 * the server-side PhoneNormalizer so the +359 prefix input stays consistent
 * with what the backend stores. Strips the country code (with or without 00),
 * any leading zeros, and caps the result at 9 digits.
 */
export function toNationalDigits(raw) {
    let digits = String(raw ?? "").replace(/\D/g, "");

    if (digits.startsWith("00")) {
        digits = digits.slice(2);
    }

    if (digits.startsWith("359")) {
        digits = digits.slice(3);
    }

    return digits.replace(/^0+/, "").slice(0, MAX_NATIONAL_LENGTH);
}

/**
 * A valid Bulgarian mobile subscriber number: exactly 9 digits starting with
 * 8 or 9 (e.g. 88X XXX XXX), i.e. the part that follows +359.
 */
export function isValidNationalMobile(national) {
    return /^[89]\d{8}$/.test(national);
}

/**
 * Combine the national subscriber digits with the +359 prefix. Returns an empty
 * string when there is nothing to send so the "required" rule still triggers.
 */
export function composeInternationalPhone(national) {
    return national === "" ? "" : PHONE_DIAL_PREFIX + national;
}
