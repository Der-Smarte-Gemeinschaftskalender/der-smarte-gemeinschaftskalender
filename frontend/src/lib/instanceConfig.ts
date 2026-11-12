import { computed } from 'vue';
import i18n, { DEFAULT_LOCALE, type SupportedLocale } from '@/i18n';
import { overwriteConfig, type LocalizedText } from '@/lib/overwriteConfig';

/** 
 * IMPORTANT: 
 * Do not change the default values in this file. If you want to change any default value, please add it to overwriteConfig in overwriteConfig.ts. 
 * This way, we can ensure that no configuration value is missing after updates.
 */

const { t } = i18n.global;

const isObject = (value: unknown): value is Record<string, unknown> => {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
};

const mergeWithOverwrite = <T>(defaults: T, overwrite?: unknown): T => {
    if (overwrite === undefined) return defaults;

    if (isObject(defaults) && isObject(overwrite)) {
        const merged: Record<string, unknown> = { ...defaults };

        Object.entries(overwrite).forEach(([key, value]) => {
            const defaultValue = (defaults as Record<string, unknown>)[key];
            merged[key] = mergeWithOverwrite(defaultValue, value);
        });

        return merged as T;
    }

    return overwrite as T;
};

/**
 * Picks the text for the current language from a per-language overwrite.
 * Languages without an own text fall back to the default language's text, then to any other given text.
 * The built-in default is only used if the overwrite has no text at all: it describes the original
 * instance (e.g. "Amt Süderbrarup") and would be wrong for an instance that overwrites the text.
 */
const pickLocalizedText = (text: Partial<Record<SupportedLocale, string | null>>, fallback: unknown): unknown => {
    const locale = i18n.global.locale.value as SupportedLocale;
    return text[locale] ?? text[DEFAULT_LOCALE] ?? Object.values(text).find((value) => value != null) ?? fallback;
};

/**
 * Like mergeWithOverwrite, but the given text keys may be overwritten per language
 * (`{ de: '…', en: '…' }`, see LocalizedText and pickLocalizedText).
 */
const mergeWithLocalizedOverwrite = <T extends Record<string, unknown>>(
    defaults: T,
    overwrite: Partial<Record<keyof T, unknown>> | undefined,
    textKeys: Array<keyof T>
): T => {
    const merged = mergeWithOverwrite(defaults, overwrite);

    textKeys.forEach((key) => {
        const text = overwrite?.[key] as LocalizedText | null | undefined;
        if (text && typeof text === 'object') merged[key] = pickLocalizedText(text, defaults[key]) as T[keyof T];
    });

    return merged;
};

export const isStrictModeEnabled = mergeWithOverwrite(
    String(import.meta.env?.VITE_DSG_STRICT_MODE ?? '').toLowerCase() === 'true',
    overwriteConfig.isStrictModeEnabled
);

export const showPhysicalAddressRouting: Record<string, boolean> = mergeWithOverwrite(
    {
        googleMaps: true,
        appleMaps: true,
        copyAdressButton: true,
        openGeoCoordinates: true,
    },
    overwriteConfig.showPhysicalAddressRouting
);

export const instanceInformation = computed<{ name: string; operatedBy: string }>(() =>
    mergeWithLocalizedOverwrite(
        {
            name: 'Der Smarte Gemeinschaftskalender',
            operatedBy: 'Amt Süderbrarup',
        },
        overwriteConfig.instanceInformation,
        ['name', 'operatedBy']
    )
);

export const allowedEventCreationsMethods: {
    singleEvent: boolean;
    seriesEvent: boolean;
    uploadedEvent: boolean;
    importedEvent: boolean;
} = mergeWithOverwrite(
    {
        singleEvent: true,
        seriesEvent: true,
        uploadedEvent: true,
        importedEvent: true,
    },
    overwriteConfig.allowedEventCreationsMethods
);

export const landingPage = computed<{
    heading: string;
    description: string;
    descriptionHtml: string;
    showNotification: boolean;
    showCategories: boolean;
    numberOfUpcomingEvents: number;
    upcomingEventsMapTitle: string;
    upcomingEventsMapBeforeDateFromNowInDays: number | null;
    showNotificationImageAlt: string;
}>(() =>
    mergeWithLocalizedOverwrite(
        {
            heading: t('instanceConfig.landingPage.heading'),
            description: t('instanceConfig.landingPage.description'),
            descriptionHtml: '',
            showNotification: true,
            showCategories: true,
            numberOfUpcomingEvents: 11,
            upcomingEventsMapTitle: t('instanceConfig.landingPage.upcomingEventsMapTitle'),
            upcomingEventsMapBeforeDateFromNowInDays: 30,
            showNotificationImageAlt: t('instanceConfig.landingPage.showNotificationImageAlt'),
        },
        overwriteConfig.landingPage,
        ['heading', 'description', 'descriptionHtml', 'upcomingEventsMapTitle', 'showNotificationImageAlt']
    )
);

export const searchPage = computed<{ description: string; descriptionHtml: string }>(() =>
    mergeWithLocalizedOverwrite(
        {
            description: t('instanceConfig.searchPage.description'),
            descriptionHtml: t('instanceConfig.searchPage.descriptionHTML'),
        },
        overwriteConfig.searchPage,
        ['description', 'descriptionHtml']
    )
);

export const searchDefaults: {
    searchTerm: string;
    searchRadius: number;
    locationAddress: string;
    locationGeoHash: string;
    target: string;
} = mergeWithOverwrite(
    {
        searchTerm: '',
        searchRadius: 10,
        locationAddress: '24392 Süderbrarup, Schleswig-Holstein',
        locationGeoHash: 'u1wyy3xs',
        target: 'INTERNAL',
    },
    overwriteConfig.searchDefaults
);

export const mainHeader = computed<{
    showCalendarLink: boolean;
    externalLinkUrl: string;
    externalLinkText: string;
    externalLinkUrl2: string | null;
    externalLinkText2: string | null;
}>(() =>
    mergeWithLocalizedOverwrite(
        {
            showCalendarLink: true,
            externalLinkUrl: 'https://der-smarte-gemeinschaftskalender.de/',
            externalLinkText: t('instanceConfig.mainHeader.externalLinkText'),
            externalLinkUrl2: null,
            externalLinkText2: null,
        },
        overwriteConfig.mainHeader,
        ['externalLinkText', 'externalLinkText2']
    )
);

export const mainFooter = computed<{ disclaimerText: string }>(() =>
    mergeWithLocalizedOverwrite(
        {
            disclaimerText: t('instanceConfig.mainFooter.disclaimerText'),
        },
        overwriteConfig.mainFooter,
        ['disclaimerText']
    )
);

export const createEventDefaults: { category: string } = mergeWithOverwrite(
    {
        category: 'NETWORKING',
    },
    overwriteConfig.createEventDefaults
);

export const showEventPage = computed<{ eventShareTitle: string }>(() =>
    mergeWithLocalizedOverwrite(
        {
            eventShareTitle: t('instanceConfig.showEventPage.eventShareTitle'),
        },
        overwriteConfig.showEventPage,
        ['eventShareTitle']
    )
);

export const materialGeneratorDefaults: { underlineColor: string } = mergeWithOverwrite(
    {
        underlineColor: '#800080',
    },
    overwriteConfig.materialGeneratorDefaults
);

export const defaultEventImageBasedOnCategory: boolean = mergeWithOverwrite(
    false,
    overwriteConfig.defaultEventImageBasedOnCategory
);

export const eventsMap: { initialZoomLevel: number; defaultCenter: { lat: number; lon: number } } = mergeWithOverwrite(
    {
        initialZoomLevel: 11,
        defaultCenter: { lat: 54.627027, lon: 9.756282 },
    },
    overwriteConfig.eventsMap
);

export const seriesEventsDaysControlsEnabled: boolean = true;

export const seriesEventsHolidaysFilter: { enabled: boolean; state: string } = mergeWithOverwrite(
    {
        enabled: true,
        state: 'sh',
    },
    overwriteConfig.seriesEventsHolidaysFilter
);
