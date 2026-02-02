export const isStrictModeEnabled = false;

export const showPhysicalAddressRouting: Record<string, boolean> = {
    googleMaps: false,
    appleMaps: false,
    copyAdressButton: true,
    openGeoCoordinates: true,
}

export const instanceInformation: Record<string, string> = {
    name: 'DIDay Veranstaltungen',
    operatedBy: 'Chaos Computer Club Flensburg e.V.',
}

export const allowedEventCreationsMethods: { singleEvent: boolean, seriesEvent: boolean, uploadedEvent: boolean, importedEvent: boolean } = {
    singleEvent: true,
    seriesEvent: true,
    uploadedEvent: true,
    importedEvent: true
}

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
    heading: 'DIDay Veranstaltungen',
    description: 'Digital Independence Day – immer am ersten Sonntag im Monat #DIDit #DUTgemacht',
    descriptionHtml: '',
    showNotification: false,
    showCategories: false,
    numberOfUpcomingEvents: 59,
    upcomingEventsMapTitle: 'Karte – Termine der nächsten 30 Tage',
    upcomingEventsMapBeforeDateFromNowInDays: 30,
}

export const searchPage: { description: string; descriptionHtml: string } = {
    description: 'Digital Independence Day – immer am ersten Sonntag im Monat #DIDit #DUTgemacht',
    descriptionHtml: '',
}

export const searchDefaults: { searchTerm: string, searchRadius: number, locationAddress: string, locationGeoHash: string, target: string } = {
    searchTerm: '',
    searchRadius: 10,
    locationAddress: '',
    locationGeoHash: '',
    target: 'INTERNAL',
}

export const mainHeader: { showCalendarLink: boolean, externalLinkUrl: string, externalLinkText: string } = {
    showCalendarLink: false,
    externalLinkUrl: 'https://di.day/',
    externalLinkText: 'Mehr zum Digital Independence Day',
}

export const mainFooter: { disclaimerText: string } = {
    disclaimerText: 'Alle Termine werden eigenständig von den jeweiligen Veranstalter*innen eingestellt und gepflegt.<br />Bitte wende Dich bei Fragen direkt an die im Termin genannte Organisation.<br />Wenn du Bedenken zu einer Veranstaltung oder einer Organisation hast, wende Dich an uns.'
}

export const createEventDefaults: { category: string } = {
    category: 'NETWORKING',
}

export const materialGeneratorDefaults: { underlineColor: string } = {
    underlineColor: '#800080',
}
export const defaultEventImageBasedOnCategory: boolean = false;

export const showEventPage: { eventShareTitle: string } = {
    eventShareTitle: 'Lade andere ein, mit dir zu wechseln!',
};

export const eventsMap: { initialZoomLevel: number; defaultCenter: { lat: number; lon: number } } = {
    initialZoomLevel: 5,
    defaultCenter: { lat: 50.948, lon: 10.2651 },
};