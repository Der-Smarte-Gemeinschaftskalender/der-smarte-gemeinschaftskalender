import type { Option } from '@/types/General';

/**
 * Default timezone of the instance (VITE_APP_TIMEZONE, should match APP_TIMEZONE of the backend).
 * Used for events without an own timezone (Mobilizon event option "timezone").
 * Dates are displayed in the browser timezone (dayjs default); useTimezoneCheck warns when it differs from this one.
 */
export const INSTANCE_TIMEZONE: string = import.meta.env?.VITE_APP_TIMEZONE || 'Europe/Berlin';

const formatOffset = (timeZone: string): string => {
    try {
        const parts = new Intl.DateTimeFormat('en-US', { timeZone, timeZoneName: 'longOffset' }).formatToParts(new Date());
        const offset = parts.find((part) => part.type === 'timeZoneName')?.value ?? '';
        return offset === 'GMT' ? 'UTC+00:00' : offset.replace('GMT', 'UTC');
    } catch {
        return '';
    }
};

const supportedTimezones = (): string[] => {
    const zones = typeof Intl.supportedValuesOf === 'function' ? Intl.supportedValuesOf('timeZone') : [];
    return zones.includes(INSTANCE_TIMEZONE) ? zones : [INSTANCE_TIMEZONE, ...zones];
};

/**
 * All IANA timezones as select options, e.g. "Europe/Berlin (UTC+01:00)".
 */
export const timezoneOptions: Option[] = supportedTimezones().map((zone) => {
    const offset = formatOffset(zone);
    return { value: zone, text: offset ? `${zone} (${offset})` : zone };
});
