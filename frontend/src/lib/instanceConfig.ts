export const isStrictModeEnabled = String(import.meta.env.VITE_DSG_STRICT_MODE ?? '').toLowerCase() === 'true';

export const showPhysicalAddressRouting: Record<string, boolean> = {
    googleMaps: true,
    appleMaps: true,
    copyAdressButton: true,
    openGeoCoordinates: true,
};

export const instanceInformation: Record<string, string> = {
    name: 'Der Smarte Gemeinschaftskalender',
    operatedBy: 'Amt Süderbrarup',
};

export const allowedEventCreationsMethods: {
    singleEvent: boolean;
    seriesEvent: boolean;
    uploadedEvent: boolean;
    importedEvent: boolean;
} = {
    singleEvent: true,
    seriesEvent: true,
    uploadedEvent: true,
    importedEvent: true,
};

export const landingPage: {
    heading: string;
    description: string;
    descriptionHtml: string;
    showNotification: boolean;
    showCategories: boolean;
    numberOfUpcomingEvents: number;
    upcomingEventsMapTitle: string;
    upcomingEventsMapBeforeDateFromNowInDays: number | null;
} = {
    heading: 'Veranstaltungen im Amt Süderbrarup',
    description: 'Termine, Angebote & Veranstaltungen im Amt Süderbrarup',
    descriptionHtml: ``,
    showNotification: false,
    showCategories: false,
    numberOfUpcomingEvents: 11,
    upcomingEventsMapTitle: 'Karte – Termine der nächsten 30 Tage',
    upcomingEventsMapBeforeDateFromNowInDays: 30,
};

export const searchPage: { description: string; descriptionHtml: string } = {
    description: 'Öffentliche Termine von Vereinen, Organisationen & der Gemeinde',
    descriptionHtml: ``,
};

export const searchDefaults: {
    searchTerm: string;
    searchRadius: number;
    locationAddress: string;
    locationGeoHash: string;
    target: string;
} = {
    searchTerm: '',
    searchRadius: 10,
    locationAddress: '24392 Süderbrarup, Schleswig-Holstein',
    locationGeoHash: 'u1wyy3xs',
    target: 'INTERNAL',
};

export const mainHeader: { showCalendarLink: boolean; externalLinkUrl: string; externalLinkText: string } = {
    showCalendarLink: true,
    externalLinkUrl: 'https://di.day/',
    externalLinkText: 'Mehr zum Digital Independence Day',
};

export const mainFooter: { disclaimerText: string } = {
    disclaimerText:
        'Alle Termine werden eigenständig von den jeweiligen Veranstalter*innen eingestellt und gepflegt.<br />Bitte wende Dich bei Fragen direkt an die im Termin genannte Organisation.<br />Wenn du Bedenken zu einer Veranstaltung oder einer Organisation hast, wende Dich an uns.',
};

export const createEventDefaults: { category: string } = {
    category: 'NETWORKING',
};

export const showEventPage: { eventShareTitle: string } = {
    eventShareTitle: 'Veranstaltung teilen',
};

export const materialGeneratorDefaults: { underlineColor: string } = {
    underlineColor: '#800080',
};

export const defaultEventImageBasedOnCategory: boolean = false;

export const eventsMap: { initialZoomLevel: number; defaultCenter: { lat: number; lon: number } } = {
    initialZoomLevel: 5,
    defaultCenter: { lat: 50.948, lon: 10.2651 },
};
/*
export const eventsMap: { initialZoomLevel: number; defaultCenter: { lat: number; lon: number } } = {
    initialZoomLevel: 8,
    defaultCenter: { lat: 54.18553758, lon: 9.82209589 },
};

*/
