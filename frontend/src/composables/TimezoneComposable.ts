import dayjs from '@/lib/dayjs';
import { DISPLAY_TIMEZONE } from '@/lib/helper';

export interface TimezoneCheck {
    isMismatch: boolean;
    detectedTz: string;
}

export const useTimezoneCheck = (): TimezoneCheck => {
    const detectedTz = dayjs.tz.guess();
    return {
        isMismatch: detectedTz !== DISPLAY_TIMEZONE,
        detectedTz,
    };
};
