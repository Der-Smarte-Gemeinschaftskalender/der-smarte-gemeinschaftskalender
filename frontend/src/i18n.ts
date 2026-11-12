import { createI18n } from 'vue-i18n';
import i18next from 'i18next';
import { switchDayjsLocale } from '@/lib/dayjs';
import de from './locales/de.json' with { type: 'json' };
import en from './locales/en.json' with { type: 'json' };
import type { Option } from '@/types/General';

type MessageSchema = typeof de;

/** Languages the frontend has translations for. Which of them are enabled is configured per instance. */
const AVAILABLE_LOCALES = ['de', 'en'] as const;
export type SupportedLocale = (typeof AVAILABLE_LOCALES)[number];

const STORAGE_KEY = 'locale';

const isAvailableLocale = (value: unknown): value is SupportedLocale =>
    AVAILABLE_LOCALES.includes(value as SupportedLocale);

/**
 * Default language of the instance (VITE_APP_LOCALE, must match APP_LOCALE of the backend).
 * Used when neither a stored choice nor a browser language matches an enabled language.
 */
export const DEFAULT_LOCALE: SupportedLocale = isAvailableLocale(import.meta.env?.VITE_APP_LOCALE)
    ? import.meta.env.VITE_APP_LOCALE
    : 'de';

/**
 * Languages enabled on this instance (VITE_SUPPORTED_LOCALES, e.g. "de,en", must match
 * APP_SUPPORTED_LOCALES of the backend). The default language is always enabled and comes first.
 */
export const SUPPORTED_LOCALES: readonly [SupportedLocale, ...SupportedLocale[]] = [
    DEFAULT_LOCALE,
    ...String(import.meta.env?.VITE_SUPPORTED_LOCALES || AVAILABLE_LOCALES.join(','))
        .split(',')
        .map((code) => code.trim())
        .filter(isAvailableLocale)
        .filter((code, index, codes) => code !== DEFAULT_LOCALE && codes.indexOf(code) === index),
];

const isSupportedLocale = (value: unknown): value is SupportedLocale =>
    SUPPORTED_LOCALES.includes(value as SupportedLocale);

/**
 * A stored language preference (e.g. the email language of a user) as a language enabled on this
 * instance. Missing languages and languages that are no longer enabled become the default language,
 * so forms never preselect a value their select cannot show and validation would reject.
 */
export const toSupportedLocale = (value: unknown): SupportedLocale =>
    isSupportedLocale(value) ? value : DEFAULT_LOCALE;

/** Names of the supported languages in their own language (intentionally not translated). */
export const LOCALE_LABELS: Record<SupportedLocale, string> = {
    de: 'Deutsch',
    en: 'English',
};

/** Select options for choosing a language, e.g. the email language of a user. */
export const localeOptions: Option[] = SUPPORTED_LOCALES.map((code) => ({ value: code, text: LOCALE_LABELS[code] }));

const readStoredLocale = (): string | null => {
    try {
        return window.localStorage.getItem(STORAGE_KEY);
    } catch {
        // e.g. storage disabled in the browser
        return null;
    }
};

/**
 * Language for a new page load: the language the user picked in the LanguageSwitch, otherwise the
 * first enabled language from the browser preferences, otherwise the instance default.
 */
const getInitialLocale = (): SupportedLocale => {
    if (typeof window === 'undefined') return DEFAULT_LOCALE;

    const stored = readStoredLocale();
    if (isSupportedLocale(stored)) return stored;

    // navigator.languages holds all preferred languages in order ("fr, en" -> en),
    // navigator.language only the first one.
    const browserLanguages = window.navigator.languages?.length
        ? window.navigator.languages
        : [window.navigator.language];

    for (const language of browserLanguages) {
        const code = language?.split('-')[0]?.toLowerCase();
        if (isSupportedLocale(code)) return code;
    }

    return DEFAULT_LOCALE;
};

const i18n = createI18n<[MessageSchema], SupportedLocale, false>({
    legacy: false,
    locale: getInitialLocale(),
    fallbackLocale: DEFAULT_LOCALE,
    messages: {
        de,
        en
    }
});

// Modules may format dates on import, before main.ts applies the locale to the rest of the app.
switchDayjsLocale(i18n.global.locale.value);

/**
 * Applies a language to the whole app (vue-i18n, dayjs, zod messages, <html lang>) without
 * remembering it, so the browser language keeps being detected until the user picks one.
 */
export const applyLocale = (langCode: string): void => {
    if (!isSupportedLocale(langCode)) return;

    i18n.global.locale.value = langCode;
    document.documentElement.setAttribute('lang', langCode);
    switchDayjsLocale(langCode);
    // i18next (zod messages) is initialized lazily in lib/zod.ts with the current locale
    if (i18next.isInitialized) i18next.changeLanguage(langCode);
};

/**
 * Switches the language on behalf of the user (LanguageSwitch) and remembers the choice.
 */
export const setLocale = (langCode: string): void => {
    if (!isSupportedLocale(langCode)) return;

    applyLocale(langCode);
    try {
        window.localStorage.setItem(STORAGE_KEY, langCode);
    } catch {
        // Not remembered, the language still applies to this page load.
    }
};

/**
 * Custom zod messages are i18n keys in the "validation" namespace (e.g. "validation.name.min").
 * Translates those and returns everything else unchanged (zod-i18n-map and backend messages are
 * already translated).
 */
export const translateMessage = (message: string): string =>
    message?.startsWith('validation.') ? i18n.global.t(message) : message;

export const isDe = (): boolean => i18n.global.locale.value === 'de';
export const isEn = (): boolean => i18n.global.locale.value === 'en';

export default i18n;
