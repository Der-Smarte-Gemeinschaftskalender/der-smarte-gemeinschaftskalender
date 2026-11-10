import dayjs from 'dayjs';
// Explizite .js-Endungen: dayjs hat keine "exports"-Map, und Node-ESM (z. B. die e2e-Tests, die
// über instanceConfig -> i18n diese Datei laden) löst Subpfade sonst nicht auf. Vite stört das nicht.
import utc from 'dayjs/plugin/utc.js';
import timezone from 'dayjs/plugin/timezone.js';
import 'dayjs/locale/de.js';
import 'dayjs/locale/en.js';
import { INSTANCE_TIMEZONE } from '@/lib/timezone';

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.locale((typeof window !== 'undefined' && window.localStorage.getItem('locale')) || 'de');
dayjs.tz.setDefault(INSTANCE_TIMEZONE);

export const switchDayjsLocale = (langCode: string) => {
    dayjs.locale(langCode);
};



export default dayjs;