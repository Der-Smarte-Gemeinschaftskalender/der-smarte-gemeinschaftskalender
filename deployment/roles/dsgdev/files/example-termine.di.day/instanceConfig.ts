import { computed } from 'vue';
import i18n from '@/i18n';
import { overwriteConfig } from '@/lib/overwriteConfig';

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

export const instanceInformation: Record<string, string> = mergeWithOverwrite(
    {
        name: 'Der Smarte Gemeinschaftskalender',
        operatedBy: 'Amt Süderbrarup',
    },
    overwriteConfig.instanceInformation
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
    mergeWithOverwrite(
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
        overwriteConfig.landingPage
    )
);

export const searchPage = computed<{ description: string; descriptionHtml: string }>(() =>
    mergeWithOverwrite(
        {
            description: t('instanceConfig.searchPage.description'),
            descriptionHtml: t('instanceConfig.searchPage.descriptionHTML'),
        },
        overwriteConfig.searchPage
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

export const mainHeader = computed<{ showCalendarLink: boolean; externalLinkUrl: string; externalLinkText: string }>(
    () =>
        mergeWithOverwrite(
            {
                showCalendarLink: true,
                externalLinkUrl: 'https://di.day/',
                externalLinkText: t('instanceConfig.mainHeader.externalLinkText'),
            },
            overwriteConfig.mainHeader
        )
);

export const mainFooter = computed<{ disclaimerText: string }>(() =>
    mergeWithOverwrite(
        {
            disclaimerText: t('instanceConfig.mainFooter.disclaimerText'),
        },
        overwriteConfig.mainFooter
    )
);

export const createEventDefaults: { category: string } = mergeWithOverwrite(
    {
        category: 'NETWORKING',
    },
    overwriteConfig.createEventDefaults
);

export const showEventPage = computed<{ eventShareTitle: string }>(() =>
    mergeWithOverwrite(
        {
            eventShareTitle: t('instanceConfig.showEventPage.eventShareTitle'),
        },
        overwriteConfig.showEventPage
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
        initialZoomLevel: 5,
        defaultCenter: { lat: 50.948, lon: 10.2651 },
    },
    overwriteConfig.eventsMap
);
