import * as zod from "zod";
import i18next from "i18next";
import { zodI18nMap } from "zod-i18n-map";
// Import your language translation files
import translation from "@/translations/de/zod.json";

// lng and resources key depend on your locale.
i18next.init({
  lng: "de",
  resources: {
    de: { zod: translation },
  },
});
zod.setErrorMap(zodI18nMap);

// export configured zod instance
export default zod;
