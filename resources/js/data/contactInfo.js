import { getBusinessConfig } from "@/utils/appConfig";

const business = getBusinessConfig();

export const contactInfo = {
    city: business.addressLocality || "гр. Пловдив",
    address: business.streetAddress || "ул. Полк. Сава Муткуров 30",
    phones: business.phones || ["0879000685", "0887703365"],
    email: business.email || "office@creditzona.bg",
    workingDays: business.workingDays || "Понеделник - Петък",
    workingHours: business.workingHours || "09:00 - 18:00",
    googleMapsUrl:
        business.googleMapsUrl ||
        "https://www.google.com/maps?cid=17488259411281683573",
};
