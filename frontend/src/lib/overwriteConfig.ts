type OverwriteConfig = {
    isStrictModeEnabled?: boolean;
    showPhysicalAddressRouting?: Record<string, boolean>;
    instanceInformation?: Record<string, string>;
    allowedEventCreationsMethods?: {
        singleEvent?: boolean;
        seriesEvent?: boolean;
        uploadedEvent?: boolean;
        importedEvent?: boolean;
    };
    landingPage?: {
        heading?: string;
        description?: string;
        descriptionHtml?: string;
        showNotification?: boolean;
        showCategories?: boolean;
        numberOfUpcomingEvents?: number;
        upcomingEventsMapTitle?: string;
        upcomingEventsMapBeforeDateFromNowInDays?: number | null;
        showNotificationImageAlt?: string;
    };
    searchPage?: {
        description?: string;
        descriptionHtml?: string;
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
        externalLinkText?: string;
        externalLinkUrl2?: string | null;
        externalLinkText2?: string | null;
    };
    mainFooter?: {
        disclaimerText?: string;
    };
    createEventDefaults?: {
        category?: string;
    };
    showEventPage?: {
        eventShareTitle?: string;
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
};
