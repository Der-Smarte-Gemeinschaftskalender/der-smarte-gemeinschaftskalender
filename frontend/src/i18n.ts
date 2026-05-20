import { createI18n } from 'vue-i18n';
import de from './locales/de.json' with { type: 'json' };
import en from './locales/en.json' with { type: 'json' };

type MessageSchema = typeof de;

const getDefaultLocale = (): string => {
    if (typeof window === 'undefined') return 'de';

    const stored = window.localStorage.getItem('locale');
    if (stored && ['de', 'en'].includes(stored)) {
        return stored;
    }

    const browserLang = window.navigator.language.split('-')[0];
    return browserLang && ['de', 'en'].includes(browserLang) ? browserLang : 'de';
};

const i18n = createI18n<[MessageSchema], 'de' | 'en'>({
    legacy: false,
    locale: getDefaultLocale(),
    fallbackLocale: 'de',
    messages: {
        de,
        en
    }
});

export const isDe = (): boolean => i18n.global.locale.value === 'de';
export const isEn = (): boolean => i18n.global.locale.value === 'en';

export default i18n;
