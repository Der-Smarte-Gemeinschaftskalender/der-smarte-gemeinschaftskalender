import type { SupportedLocale } from '@/i18n';

/**
 * A text shown to visitors: either one text for all languages or one per language, e.g.
 * `{ de: 'Termine in Süderbrarup', en: 'Events in Süderbrarup' }`. Languages without an own
 * text show the text of the default language (VITE_APP_LOCALE), or else any other given text.
 */
export type LocalizedText = string | Partial<Record<SupportedLocale, string>>;

type OverwriteConfig = {
    isStrictModeEnabled?: boolean;
    showPhysicalAddressRouting?: Record<string, boolean>;
    instanceInformation?: {
        name?: LocalizedText;
        operatedBy?: LocalizedText;
    };
    allowedEventCreationsMethods?: {
        singleEvent?: boolean;
        seriesEvent?: boolean;
        uploadedEvent?: boolean;
        importedEvent?: boolean;
    };
    landingPage?: {
        heading?: LocalizedText;
        description?: LocalizedText;
        descriptionHtml?: LocalizedText;
        showNotification?: boolean;
        showCategories?: boolean;
        numberOfUpcomingEvents?: number;
        upcomingEventsMapTitle?: LocalizedText;
        upcomingEventsMapBeforeDateFromNowInDays?: number | null;
        showNotificationImageAlt?: LocalizedText;
    };
    searchPage?: {
        description?: LocalizedText;
        descriptionHtml?: LocalizedText;
    };
    searchDefaults?: {
        searchTerm?: string;
        searchRadius?: number;
        locationAddress?: string;
        locationGeoHash?: string;
        target?: string;
    };
    mainHeader?: {
        showCalendarLink?: boolean;
        externalLinkUrl?: string;
        externalLinkText?: LocalizedText;
        externalLinkUrl2?: string | null;
        externalLinkText2?: LocalizedText | null;
    };
    mainFooter?: {
        disclaimerText?: LocalizedText;
    };
    createEventDefaults?: {
        category?: string;
    };
    showEventPage?: {
        eventShareTitle?: LocalizedText;
    };
    materialGeneratorDefaults?: {
        underlineColor?: string;
    };
    defaultEventImageBasedOnCategory?: boolean;
    eventsMap?: {
        initialZoomLevel?: number;
        defaultCenter?: {
            lat?: number;
            lon?: number;
        };
    };
    seriesEventsHolidaysFilter?: {
        enabled?: boolean;
        state?: string;
    };
};

export const overwriteConfig: OverwriteConfig = {
    // Add only values you want to overwrite.
    // All missing keys keep their defaults from instanceConfig.
    // Texts can be given per language, see LocalizedText.
};
