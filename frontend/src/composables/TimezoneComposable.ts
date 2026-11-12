import dayjs from '@/lib/dayjs';
import { INSTANCE_TIMEZONE } from '@/lib/timezone';

export interface TimezoneCheck {
    isMismatch: boolean;
    detectedTz: string;
}

export const useTimezoneCheck = (): TimezoneCheck => {
    const detectedTz = dayjs.tz.guess();
    return {
        isMismatch: detectedTz !== INSTANCE_TIMEZONE,
        detectedTz,
    };
};
