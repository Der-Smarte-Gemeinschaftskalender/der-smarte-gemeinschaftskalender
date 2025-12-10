import { USER_TYPES, type Option, Intervall, MobilizonCategoryAndAll, EventStatus } from "@/types/General";

export const intervall_keys = {
    weekly: "Wöchentlich",
    monthly: "Monatlich"
};

export const intervall_notification_options: Option[] = [
    { value: Intervall.WEEKLY, text: `${intervall_keys.weekly} (Montag morgen)` },
    { value: Intervall.MONTHLY, text: `${intervall_keys.monthly} (erster Tag des Monats)` },
];

export const intervall_options: Option[] = [
    { value: Intervall.WEEKLY, text: intervall_keys.weekly },
    { value: Intervall.MONTHLY, text: intervall_keys.monthly },
];

export const user_types_keys = {
    user: "Benutzer*in",
    admin: "Administrator*in"
};

export const user_types_options: Option[] = [
    { value: USER_TYPES.USER, text: user_types_keys.user },
    { value: USER_TYPES.ADMIN, text: user_types_keys.admin }
];

export const user_is_active_options: Option[] = [
    { value: true, text: "Aktiv" },
    { value: false, text: "Inaktiv" }
];

export const mobilizon_category_options: Option[] = [
    { value: MobilizonCategoryAndAll.ARTS, text: "Kunst" },
    { value: MobilizonCategoryAndAll.BOOK_CLUBS, text: "Lesekreise" },
    { value: MobilizonCategoryAndAll.BUSINESS, text: "Wirtschaft & Unternehmen" },
    { value: MobilizonCategoryAndAll.CAUSES, text: "Benefiz & Spendenaktionen" },
    { value: MobilizonCategoryAndAll.COMEDY, text: "Kabarett" },
    { value: MobilizonCategoryAndAll.CRAFTS, text: "Kunsthandwerk" },
    { value: MobilizonCategoryAndAll.FOOD_DRINK, text: "Essen & Trinken" },
    { value: MobilizonCategoryAndAll.HEALTH, text: "Gesundheit" },
    { value: MobilizonCategoryAndAll.MUSIC, text: "Musik" },
    { value: MobilizonCategoryAndAll.AUTO_BOAT_AIR, text: "Autos, Boote, Luftfahrt" },
    { value: MobilizonCategoryAndAll.COMMUNITY, text: "Gemeinschaft und Ehrenamt" },
    { value: MobilizonCategoryAndAll.FAMILY_EDUCATION, text: "Familie" },
    { value: MobilizonCategoryAndAll.FASHION_BEAUTY, text: "Mode & Schönheit" },
    { value: MobilizonCategoryAndAll.FILM_MEDIA, text: "Film & Medien" },
    { value: MobilizonCategoryAndAll.GAMES, text: "Spiele & Gaming" },
    { value: MobilizonCategoryAndAll.LANGUAGE_CULTURE, text: "Sprache & Kultur" },
    { value: MobilizonCategoryAndAll.LEARNING, text: "Bildung" },
    { value: MobilizonCategoryAndAll.LGBTQ, text: "LGBTQIA+" },
    { value: MobilizonCategoryAndAll.MOVEMENTS_POLITICS, text: "Gesellschaft & Politik" },
    { value: MobilizonCategoryAndAll.NETWORKING, text: "Netzwerke" },
    { value: MobilizonCategoryAndAll.PARTY, text: "Tanzen & Feiern" },
    { value: MobilizonCategoryAndAll.PERFORMING_VISUAL_ARTS, text: "Darstellende & bildende Kunst" },
    { value: MobilizonCategoryAndAll.PETS, text: "Tiere & Haustiere" },
    { value: MobilizonCategoryAndAll.PHOTOGRAPHY, text: "Fotografie" },
    { value: MobilizonCategoryAndAll.OUTDOORS_ADVENTURE, text: "Natur & Abenteuer" },
    { value: MobilizonCategoryAndAll.SPIRITUALITY_RELIGION_BELIEFS, text: "Glauben, Religion & Spiritualität" },
    { value: MobilizonCategoryAndAll.SCIENCE_TECH, text: "Wissenschaft & Technologie" },
    { value: MobilizonCategoryAndAll.SPORTS, text: "Sport" },
    { value: MobilizonCategoryAndAll.THEATRE, text: "Theater" },
    { value: MobilizonCategoryAndAll.MEETING, text: "Treffen" }
];

export const mobilizon_main_category_options: Option[] = [
    {
        value: "CULTURE_CREATIVE", text: "Kultur & Kreatives", color: "#AD0477",
        image: "/material_generator/event_main_category/cards/CULTURE_CREATIVE.jpg",
        sub_categories: [
            MobilizonCategoryAndAll.PERFORMING_VISUAL_ARTS,
            MobilizonCategoryAndAll.FILM_MEDIA,
            MobilizonCategoryAndAll.THEATRE,
            MobilizonCategoryAndAll.LANGUAGE_CULTURE,
            MobilizonCategoryAndAll.PHOTOGRAPHY,
            MobilizonCategoryAndAll.MUSIC,
            MobilizonCategoryAndAll.CRAFTS,
            MobilizonCategoryAndAll.COMEDY,
            MobilizonCategoryAndAll.ARTS
        ]
    },
    {
        value: "COMMUNITY_COMMITMENT", text: "Gemeinschaft & Engagement", color: "#6338B2",
        image: "/material_generator/event_main_category/cards/COMMUNITY_COMMITMENT.jpg",
        sub_categories: [
            MobilizonCategoryAndAll.COMMUNITY,
            MobilizonCategoryAndAll.LGBTQ,
            MobilizonCategoryAndAll.NETWORKING,
            MobilizonCategoryAndAll.MOVEMENTS_POLITICS,
            MobilizonCategoryAndAll.SPIRITUALITY_RELIGION_BELIEFS,
            MobilizonCategoryAndAll.BOOK_CLUBS,
            MobilizonCategoryAndAll.MEETING,
            MobilizonCategoryAndAll.CAUSES,
        ]
    },
    {
        value: "FAMILY_LEISURE", text: "Familie & Freizeit", color: "#005CD3",
        image: "/material_generator/event_main_category/cards/FAMILY_LEISURE.png",
        sub_categories: [
            MobilizonCategoryAndAll.OUTDOORS_ADVENTURE,
            MobilizonCategoryAndAll.PETS,
            MobilizonCategoryAndAll.PARTY,
            MobilizonCategoryAndAll.FASHION_BEAUTY,
            MobilizonCategoryAndAll.GAMES,
            MobilizonCategoryAndAll.AUTO_BOAT_AIR,
            MobilizonCategoryAndAll.FOOD_DRINK,
            MobilizonCategoryAndAll.FAMILY_EDUCATION
        ]
    },
    {
        value: "HEALTH_EXERCISE", text: "Gesundheit & Bewegung", color: "#008158",
        image: "/material_generator/event_main_category/cards/HEALTH_EXERCISE.png",
        sub_categories: [
            MobilizonCategoryAndAll.HEALTH,
            MobilizonCategoryAndAll.SPORTS
        ]
    },
    {
        value: "ECONOMY_EDUCATION", text: "Wirtschaft & Bildung", color: "#06313B",
        image: "/material_generator/event_main_category/cards/ECONOMY_EDUCATION.png",
        sub_categories: [
            MobilizonCategoryAndAll.SCIENCE_TECH,
            MobilizonCategoryAndAll.LEARNING,
            MobilizonCategoryAndAll.BUSINESS
        ]
    },
]

export const mobilizon_category_options_all: Option[] = [
    { value: MobilizonCategoryAndAll.ALL, text: "Alle Kateogrien" },
    ...mobilizon_category_options
];

export const mobilizon_event_join_options: Option[] = [
    { value: "FREE", text: "Keine Online-Anmeldung" },
    { value: "EXTERNAL", text: "Anmeldung über externen Link erforderlich" }
];
export const mobilizon_event_language_options: Option[] = [
    { value: "de", text: "Deutsch" },
    { value: "da", text: "Dänisch" },
    { value: "en", text: "Englisch" },
    { value: "fy", text: "Friesisch" },
    { value: "nds", text: "Plattdeutsch" },
    { value: "sw", text: "Schwedisch" },
    { value: "no", text: "Norwegisch" },
    { value: "fr", text: "Französisch" },
    { value: "it", text: "Italienisch" },
    { value: "es", text: "Spanisch" },
    { value: "es", text: "Spanisch" },
    { value: "wen", text: "Sorbisch" },
    { value: "ar", text: "Arabisch" },
];
export const mobilizon_event_status: Option[] = [
    { value: EventStatus.TENTATIVE, text: "Vorläufig" },
    { value: EventStatus.CONFIRMED, text: "Bestätigt" },
    { value: EventStatus.CANCELLED, text: "Abgesagt" }
];

export const fontSelectionOptions: Array<{ text: string; value: string }> = [
    { text: 'Arial', value: 'Arial' },
    { text: 'Verdana', value: 'Verdana' },
    { text: 'Fira Sans', value: 'Fira Sans' },
    { text: 'Times New Roman', value: 'Times New Roman' },
    { text: 'Garamond', value: 'Garamond' },
];

export const requested_organisation_status = {
    REQUESTED: "Angefragt",
    REQUEST_DENIED: "Abgelehnt",
    ACTIVE: "Aktiv",
    INACTIVE: "Inaktiv",
}

export const member_role = {
    ADMINISTRATOR: "Administrator",
    INVITED: "Eingeladen",
}