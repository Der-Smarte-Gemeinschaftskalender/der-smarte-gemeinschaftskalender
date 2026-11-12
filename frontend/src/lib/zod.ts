import * as zod from "zod";
import i18next from "i18next";
import { zodI18nMap } from "zod-i18n-map";
// Import your language translation files
import translationDe from "@/translations/de/zod.json";
import translationEn from "zod-i18n-map/locales/en/zod.json";
import i18n, { DEFAULT_LOCALE } from "@/i18n";

// lng and resources key depend on your locale.
i18next.init({
  lng: i18n.global.locale.value,
  fallbackLng: DEFAULT_LOCALE,
  resources: {
    de: { zod: translationDe },
    en: { zod: translationEn },
  },
});
zod.setErrorMap(zodI18nMap);

// export configured zod instance
export default zod;
