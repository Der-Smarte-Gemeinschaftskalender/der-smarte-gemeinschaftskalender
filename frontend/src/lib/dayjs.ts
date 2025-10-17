import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import 'dayjs/locale/de';

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.locale('de');
dayjs.tz.setDefault('Europe/Berlin');


/* expose dayjs globally */
// window.dayjs = dayjs;

export default dayjs;