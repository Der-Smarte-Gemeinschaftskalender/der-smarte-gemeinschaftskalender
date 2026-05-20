export interface InfoBox {
    infoBoxStartX: number;
    infoBoxStartY: number;
    infoBoxWidth: number;
    infoBoxLength: number;
    headlineStartY?: number;
    organisationInfoStartY?: number;
    backgroundImageDefaultId: string;
    logoStartX: number;
    logoStartY: number;
}

export interface CalendarEvent {
    title: string;
    beginsOn: string;
    endsOn: string;
    uuid: string;
    organisationName?: string;
}

export interface EventInfoPayload {
    title: string;
    beginsOn: string;
    physicalAddress?: { street?: string; postalCode?: string; locality?: string } | null;
    organisation: { name: string };
    uuid: string;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type SerializedFabricObject = Record<string, any>;

export interface MgGlobalSettings {
    selectedFont: string;
    selectedTextColor: string;
    underlineColor: string;
    headlineText: string;
    selectedFontHeadline: string;
    selectedTextColorHeadline: string;
    loadOrganisationLogo: boolean;
    selectedEventCount?: number;
}

export interface MgTemplate {
    id: number;
    mobilizon_group_id: number;
    name: string;
    material_type: 'event' | 'eventList';
    dimension: string;
    created_at: string;
    global_settings: MgGlobalSettings;
    objects_data: {
        userObjects: SerializedFabricObject[];
        backgroundImageDataUrl: string | null;
        autoOverrides?: Record<string, SerializedFabricObject>;
        zOrder?: Array<{ kind: 'bg' } | { kind: 'avatar' } | { kind: 'auto'; autoKey: string } | { kind: 'user'; index: number }>;
    };
}

export const dimensionOptions: Array<{ text: string; value: string }> = [
    { text: 'Social-Media-Beitrag (1080 x 1350 px)', value: 'post' },
    { text: 'Social-Media-Story (1080 x 1920 px)', value: 'story' },
    { text: 'Text', value: 'textWithEmojis' },
];

export const defaultBackgroundImagePaths = [
    { name: 'event_post', path: '/material_generator/event_post_default.png' },
    { name: 'event_story', path: '/material_generator/event_story_default.png' },
    { name: 'eventList_post', path: '/material_generator/eventList_post_default.png' },
    { name: 'eventList_story', path: '/material_generator/eventList_story_default.png' },
];

export const dimensions: Record<
    string,
    {
        width?: number;
        height?: number;
        preview: string | null;
        maxEvents?: number;
        canvasData?: { event: InfoBox; eventList: InfoBox };
    }
> = {
    post: {
        width: 1080,
        height: 1350,
        preview: 'canvas',
        maxEvents: 10,
        canvasData: {
            event: {
                infoBoxStartX: 0,
                infoBoxStartY: 568,
                infoBoxWidth: 800,
                infoBoxLength: 720,
                backgroundImageDefaultId: 'event_post',
                logoStartX: 1024,
                logoStartY: 56,
            },
            eventList: {
                infoBoxStartX: 56,
                infoBoxStartY: 280,
                infoBoxWidth: 958,
                infoBoxLength: 766,
                headlineStartY: 160,
                organisationInfoStartY: 1160,
                backgroundImageDefaultId: 'eventList_post',
                logoStartX: 1024,
                logoStartY: 1148,
            },
        },
    },
    story: {
        width: 1080,
        height: 1920,
        preview: 'canvas',
        maxEvents: 14,
        canvasData: {
            event: {
                infoBoxStartX: 0,
                infoBoxStartY: 1008,
                infoBoxWidth: 800,
                infoBoxLength: 720,
                backgroundImageDefaultId: 'event_story',
                logoStartX: 1024,
                logoStartY: 144,
            },
            eventList: {
                infoBoxStartX: 56,
                infoBoxStartY: 400,
                infoBoxWidth: 958,
                infoBoxLength: 1096,
                headlineStartY: 264,
                organisationInfoStartY: 1680,
                backgroundImageDefaultId: 'eventList_story',
                logoStartX: 1024,
                logoStartY: 1670,
            },
        },
    },
    textWithEmojis: {
        preview: 'text',
    },
    openDocumentText: {
        preview: null,
    },
};


