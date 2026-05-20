import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import 'dayjs/locale/de';
import 'dayjs/locale/en';

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.locale((typeof window !== 'undefined' && window.localStorage.getItem('locale')) || 'de');
dayjs.tz.setDefault('Europe/Berlin');

export const switchDayjsLocale = (langCode: string) => {
    dayjs.locale(langCode);
};



export default dayjs;