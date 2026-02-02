import { USER_TYPES, type Option, Intervall, EventStatus } from "@/types/General";
import { mobilizon_category_options, mobilizon_main_category_options, mobilizon_category_options_all } from "./categoryOptions";

export const intervall_keys = {
    weekly: "Wöchentlich",
    monthly: "Monatlich"
};

export const intervall_notification_options: Option[] = [
    { value: Intervall.WEEKLY, text: `${intervall_keys.weekly} (Montag morgen)` },
    { value: Intervall.MONTHLY, text: `${intervall_keys.monthly} (erster Tag des Monats)` },
];

export const intervall_options: Option[] = [
    { value: Intervall.WEEKLY, text: intervall_keys.weekly },
    { value: Intervall.MONTHLY, text: intervall_keys.monthly },
];

export const user_types_keys = {
    user: "Benutzer*in",
    admin: "Administrator*in"
};

export const user_types_options: Option[] = [
    { value: USER_TYPES.USER, text: user_types_keys.user },
    { value: USER_TYPES.ADMIN, text: user_types_keys.admin }
];

export const user_is_active_options: Option[] = [
    { value: true, text: "Aktiv" },
    { value: false, text: "Inaktiv" }
];

export const mobilizon_event_join_options: Option[] = [
    { value: "FREE", text: "Keine Online-Anmeldung" },
    { value: "EXTERNAL", text: "Anmeldung über externen Link erforderlich" }
];
export const mobilizon_event_language_options: Option[] = [
    { value: "de", text: "Deutsch" },
    { value: "da", text: "Dänisch" },
    { value: "en", text: "Englisch" },
    { value: "fy", text: "Friesisch" },
    { value: "nds", text: "Plattdeutsch" },
    { value: "sw", text: "Schwedisch" },
    { value: "no", text: "Norwegisch" },
    { value: "fr", text: "Französisch" },
    { value: "it", text: "Italienisch" },
    { value: "es", text: "Spanisch" },
    { value: "wen", text: "Sorbisch" },
    { value: "ar", text: "Arabisch" },
];
export const mobilizon_event_status: Option[] = [
    { value: EventStatus.TENTATIVE, text: "Vorläufig" },
    { value: EventStatus.CONFIRMED, text: "Bestätigt" },
    { value: EventStatus.CANCELLED, text: "Abgesagt" }
];

export const fontSelectionOptions: Array<{ text: string; value: string }> = [
    { text: 'Arial', value: 'Arial' },
    { text: 'Verdana', value: 'Verdana' },
    { text: 'Fira Sans', value: 'Fira Sans' },
    { text: 'Times New Roman', value: 'Times New Roman' },
    { text: 'Garamond', value: 'Garamond' },
];

export const requested_organisation_status = {
    REQUESTED: "Angefragt",
    REQUEST_DENIED: "Abgelehnt",
    ACTIVE: "Aktiv",
    INACTIVE: "Inaktiv",
}

export const member_role = {
    ADMINISTRATOR: "Administrator",
    INVITED: "Eingeladen",
}

export {
    mobilizon_category_options,
    mobilizon_main_category_options,
    mobilizon_category_options_all
}