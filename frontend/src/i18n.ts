import { createI18n } from 'vue-i18n';
import i18next from 'i18next';
import { switchDayjsLocale } from '@/lib/dayjs';
import de from './locales/de.json' with { type: 'json' };
import en from './locales/en.json' with { type: 'json' };
import type { Option } from '@/types/General';

type MessageSchema = typeof de;

export const SUPPORTED_LOCALES = ['de', 'en'] as const;
export type SupportedLocale = (typeof SUPPORTED_LOCALES)[number];

/** Names of the supported languages in their own language (intentionally not translated). */
export const LOCALE_LABELS: Record<SupportedLocale, string> = {
    de: 'Deutsch',
    en: 'English',
};

/** Select options for choosing a language, e.g. the email language of a user. */
export const localeOptions: Option[] = SUPPORTED_LOCALES.map((code) => ({ value: code, text: LOCALE_LABELS[code] }));

const isSupportedLocale = (value: unknown): value is SupportedLocale =>
    SUPPORTED_LOCALES.includes(value as SupportedLocale);

const getDefaultLocale = (): SupportedLocale => {
    if (typeof window === 'undefined') return 'de';

    const stored = window.localStorage.getItem('locale');
    if (isSupportedLocale(stored)) {
        return stored;
    }

    const browserLang = window.navigator.language.split('-')[0];
    return isSupportedLocale(browserLang) ? browserLang : 'de';
};

const i18n = createI18n<[MessageSchema], SupportedLocale, false>({
    legacy: false,
    locale: getDefaultLocale(),
    fallbackLocale: 'de',
    messages: {
        de,
        en
    }
});

/**
 * Switches the language of the whole app (vue-i18n, dayjs, zod messages) and remembers it.
 */
export const setLocale = (langCode: string): void => {
    if (!isSupportedLocale(langCode)) return;

    i18n.global.locale.value = langCode;
    localStorage.setItem('locale', langCode);
    document.documentElement.setAttribute('lang', langCode);
    switchDayjsLocale(langCode);
    // i18next (zod messages) is initialized lazily in lib/zod.ts with the current locale
    if (i18next.isInitialized) i18next.changeLanguage(langCode);
};

/**
 * Translates a message if it is an i18n key (e.g. zod messages like "validation.nameMin"),
 * otherwise returns it unchanged (already translated or backend messages).
 */
export const translateMessage = (message: string): string =>
    message && i18n.global.te(message) ? i18n.global.t(message) : message;

export const isDe = (): boolean => i18n.global.locale.value === 'de';
export const isEn = (): boolean => i18n.global.locale.value === 'en';

export default i18n;
