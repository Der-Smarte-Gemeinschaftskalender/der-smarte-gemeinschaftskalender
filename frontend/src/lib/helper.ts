import dayjs from '@/lib/dayjs';
import zod from '@/lib/zod';
import { isDe } from '@/i18n';

import { mobilizon_event_language_options, mobilizon_main_category_options } from '@/lib/const';

import type { AddressForm } from '@/types/Mobilizon';
import { type IEvent, type Option, MobilizonCategory } from '@/types/General';
import { defaultEventImageBasedOnCategory } from './instanceConfig';

// Server-Speicher-Zeitzone: Eingaben in den Termin-Formularen werden vom
// Backend (Laravel config/app.php) immer als Europa/Berlin interpretiert und
// gespeichert. Die Anzeige im Frontend nutzt aber die jeweilige Browser-TZ
// (dayjs Default), damit Nutzer in anderen Zeitzonen lokale Uhrzeit sehen.
// useTimezoneCheck/TimezoneNotice nutzt diese Konstante als Referenz.
export const DISPLAY_TIMEZONE = 'Europe/Berlin';

// App
/**
 * Formats a date in the format "01.01.2023".
 * @param { dayjs.ConfigType } value - The date to format.
 * @return { string } - The formatted date string.
 */
export const formatDateTime = (value: dayjs.ConfigType): dayjs.ConfigType => {
    return dayjs(value).format('DD.MM.YYYY') || value;
};

/**
 * Formats a date in the format "2023-01-01".
 * @param { dayjs.ConfigType } value - The date to format.
 * @return { string } - The formatted date string.
 */
export const formatInputDate = (value: dayjs.ConfigType = new Date()): string => {
    return dayjs(value).format('YYYY-MM-DD');
};

/**
 * Formats a time in the format "12:00".
 * @param { dayjs.ConfigType } value - The time to format.
 * @param { boolean } tz - Whether to include timezone information.
 * @return { string } - The formatted time string.
 */
export const formatInputTime = (value: dayjs.ConfigType = new Date(), tz: boolean = false): string => {
    const d = dayjs(value);
    return tz ? d.format('HH:mm Z') : d.format('HH:mm');
};

// Public
/**
 * Formats a date and time in the format "Mo. 01. Jan. – 12:00 Uhr".
 * @param { dayjs.ConfigType } date
 * @return { string } - The formatted date and time string.
 */
export const formatOnMonthDayTime = (date: dayjs.ConfigType): string => {
    const uhr = isDe() ? ' [Uhr]' : '';
    return dayjs(date).format(`dd. DD. MMM – HH:mm${uhr}`);
};

/**
 * Formats a date in the format "Mo., 01.01.2023".
 * @param { dayjs.ConfigType } date
 * @return { string } - The formatted date string.
 */
export const formatOnDate = (date: dayjs.ConfigType): string => {
    return dayjs(date).format('dd. DD.MM.YYYY');
};

/**
 * Formats a date in the format "12:00 Uhr".
 * @param { dayjs.ConfigType } date
 * @return { string } - The formatted time string.
 */
export const formatOnTime = (date: dayjs.ConfigType): string => {
    const uhr = isDe() ? ' [Uhr]' : '';
    return dayjs(date).format(`HH:mm${uhr}`);
};

/**
 * Formats a date and time in the format "01.01.2023 12:00 Uhr".
 * @param { dayjs.ConfigType } date
 * @return { string } - The formatted date and time string.
 */
export const formatOnDateTime = (date: dayjs.ConfigType): string => {
    const uhr = isDe() ? ' [Uhr]' : '';
    return dayjs(date).format(`DD.MM.YYYY - HH:mm${uhr}`);
};

/**
 * Formats a date to show only hour and minute in "01 h 23 min" format.
 * @param date - The date to format "12:34"
 * @returns
 */
export const formatHourMinute = (date: string): string => {
    const [hour, minute] = date.split(':');
    let result = '';

    if (!hour || !minute) return 'Invalid date';
    if (hour === '00' && minute === '00') return '0 min';

    if (parseInt(hour) > 0) result += `${hour} h `;

    result += `${minute} min`;
    return result.trim();
};

/**
 * Truncates a string to a specified length and adds an ellipsis if it exceeds that length.
 * @param { string } str - The string to truncate.
 * @param { number } n - The maximum length of the string.
 * @return { string } - The truncated string.
 */
export const truncate = (str: string, n: number): string => {
    return str.length > n ? str.slice(0, n - 1) + '...' : str;
};

/**
 * Builds a formatted address string from an AddressForm object.
 * @param { AddressForm | null } address - The address object to format.
 * @return { string } - The formatted address string.
 */
export const buildAddress = (address: AddressForm | null): string => {
    return [
        address?.street ? normalizeStreet(address.street) : null,
        address?.postalCode && address?.locality ? `${address.postalCode} ${address.locality}` : address?.locality,
    ]
        .filter((part) => part)
        .join(',\n')
        .trim();
};

export const normalizeStreet = (street: string): string => {
    if (!street?.trim()) return '';

    const match = street.match(/^\s*(\d+(?:\s*[-–]\s*\d+)?[a-zA-Z]?)\s+(.+)/) as [string, string, string] | undefined;
    if (match) {
        const [_, number, name] = match;
        return `${name.trim()} ${number.trim()}`;
    }

    return street.trim();
};

/**
 * Returns the main category for a given subcategory.
 * @param { string } category - The subcategory to find the main category for.
 * @return { Option | undefined } - The main category option if found, otherwise undefined.
 */
export const getMainCategoryFromSubCategory = (category: string): Option | undefined => {
    return mobilizon_main_category_options.value.find((option) => {
        return option.sub_categories?.includes(category as MobilizonCategory);
    });
};

/**
 * Checks if a value is a valid positive number.
 * @param { Number | number | string | undefined } value - The value to check.
 * @return { boolean } - Returns true if the value is a valid positive number, otherwise false.
 */
export const isValidPositivNumber = (value: Number | number | string | undefined): boolean => {
    if (value === undefined || value === null) return false;
    if (typeof value === 'number') return value >= 0;
    if (value instanceof Number) return value.valueOf() >= 0;

    const parsedValue = parseFloat(value);
    return !isNaN(parsedValue) && parsedValue >= 0;
};

/**
 * Reconstruct options for a select input from an array of options.
 * @param { Array<Option> } options - The array of options to reconstruct.
 * @return { Array<{label: string, value: any}> } - The reconstructed array of options.
 */

export const reconstructOptions = (options: Array<Option>): Array<{ label: string; value: any }> => {
    return options.map((option) => ({
        label: option.text,
        value: option.value,
    }));
};

export const formatCoordinates = (coordinates: string): [number, number] | null => {
    if (!coordinates) {
        return null;
    }
    const coords = coordinates.split(';') as [string, string];
    if (coords.length !== 2) {
        return null;
    }
    const lon = parseFloat(coords[0]);
    const lat = parseFloat(coords[1]);
    if (isNaN(lat) || isNaN(lon)) {
        return null;
    }
    return [lon, lat];
};

export const formattedLanguage = (languageCode: string) => {
    if (!languageCode) return '';
    const language = mobilizon_event_language_options.value.find((lang) => lang.value === languageCode);
    return language ? language.text : 'deutsch';
};

export const getBeginsEndsOn = (searchBegin: string): { beginsOn: dayjs.Dayjs; endsOn: dayjs.Dayjs | null } => {
    let beginsOn: dayjs.Dayjs = dayjs();
    let endsOn: dayjs.Dayjs | null = null;
    switch (searchBegin) {
        case 'all':
            beginsOn = dayjs().startOf('day');
            endsOn = null;
            break;
        case 'today':
            beginsOn = dayjs().startOf('day');
            endsOn = dayjs().endOf('day');
            break;
        case 'tomorrow':
            beginsOn = dayjs().add(1, 'day').startOf('day');
            endsOn = dayjs().add(1, 'day').endOf('day');
            break;
        case 'thisWeek':
            beginsOn = dayjs().startOf('week');
            endsOn = dayjs().endOf('week');
            break;
        case 'thisWeekend':
            beginsOn = dayjs().day(5).startOf('day');
            endsOn = dayjs().endOf('week');
            break;
        case 'nextWeek':
            beginsOn = dayjs().add(1, 'week').startOf('week');
            endsOn = dayjs().add(1, 'week').endOf('week');
            break;
        case 'thisMonth':
            beginsOn = dayjs().startOf('month');
            endsOn = dayjs().endOf('month');
            break;
        case 'nextMonth':
            beginsOn = dayjs().add(1, 'month').startOf('month');
            endsOn = dayjs().add(1, 'month').endOf('month');
            break;
    }
    return { beginsOn, endsOn };
};

/**
 * Strips HTML tags and non-breaking spaces from a string.
 * @param input
 */
export const stripHtml = (input: string | undefined) => {
    if (!input) return '';

    return input
        .replace(/<[^>]*>/g, '')
        .replace(/&nbsp;/g, ' ')
        .trim();
};

export const copyContent = () => {
    const copyText = document.getElementById('copyToClipboardInput') as HTMLInputElement;
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
};

export const getCardImageUrl = (event: IEvent) => {
    if (event.picture) {
        return event.picture.url;
    } else if (defaultEventImageBasedOnCategory) {
        return `/material_generator/event_categories/${event.category.toUpperCase()}.jpg`;
    } else {
        return '/default_card.png';
    }
};

export const isMobile: boolean = window.innerWidth <= 992;

export const convertToUsername = (inputName: string): string => {
    let username = inputName
        .toLowerCase()
        .trim()
        .replace(/ä/g, 'ae')
        .replace(/ö/g, 'oe')
        .replace(/ü/g, 'ue')
        .replace(/ß/g, 'ss')
        // Jegliches andere Zeichen zu Unterstrich ersetzen
        .replace(/[^a-z0-9_]+/g, '_')
        // Mehrere Unterstriche zu einem einzigen Unterstrich zusammenfassen
        .replace(/_+/g, '_')
        // Unterstriche am Anfang und Ende entfernen
        .replace(/^_+|_+$/g, '');

    if (/^[0-9]/.test(username)) {
        username = 'u' + username;
    }

    return username;
};

export const preferredUsernameSchema = zod
    .string()
    .regex(/^[a-z]/, {
        message: 'Der Benutzername muss mit einem Kleinbuchstaben anfangen.',
    })
    .regex(/[a-z0-9]$/, {
        message: 'Der Benutzername muss mit einem Kleinbuchstaben oder einer Zahl enden.',
    })
    .regex(/^[a-z][a-z0-9_]*$/, {
        message: 'Bitte einen gültigen Benutzernamen eingeben. Erlaubt sind Kleinbuchstaben, Zahlen und Unterstrich',
    })
    .regex(/^(?!.*__)/, {
        message: 'Der Benutzername darf keine aufeinanderfolgenden Unterstriche enthalten.',
    });