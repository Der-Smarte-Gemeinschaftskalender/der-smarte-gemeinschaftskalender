import type { AddressForm } from "@/types/Mobilizon";

export interface Option {
    value: any; // eslint-disable-line
    text: string;
    color?: string;
    sub_categories?: MobilizonCategory[];
    image?: string;
}

export interface Column {
    key: string;
    name: string;
    numeric?: boolean;
    format?: (value: any, row: any) => any;
    align?: 'left' | 'center' | 'right';
}

export enum USER_TYPES {
    USER = "user",
    ADMIN = "admin",
}

export interface MobilizonCategoryOption {
    value: MobilizonCategory;
    text: string;
}

export enum MobilizonCategory {
    ARTS = "ARTS",
    BOOK_CLUBS = "BOOK_CLUBS",
    BUSINESS = "BUSINESS",
    CAUSES = "CAUSES",
    COMEDY = "COMEDY",
    CRAFTS = "CRAFTS",
    FOOD_DRINK = "FOOD_DRINK",
    HEALTH = "HEALTH",
    MUSIC = "MUSIC",
    AUTO_BOAT_AIR = "AUTO_BOAT_AIR",
    COMMUNITY = "COMMUNITY",
    FAMILY_EDUCATION = "FAMILY_EDUCATION",
    FASHION_BEAUTY = "FASHION_BEAUTY",
    FILM_MEDIA = "FILM_MEDIA",
    GAMES = "GAMES",
    LANGUAGE_CULTURE = "LANGUAGE_CULTURE",
    LEARNING = "LEARNING",
    LGBTQ = "LGBTQ",
    MOVEMENTS_POLITICS = "MOVEMENTS_POLITICS",
    NETWORKING = "NETWORKING",
    PARTY = "PARTY",
    PERFORMING_VISUAL_ARTS = "PERFORMING_VISUAL_ARTS",
    PETS = "PETS",
    PHOTOGRAPHY = "PHOTOGRAPHY",
    OUTDOORS_ADVENTURE = "OUTDOORS_ADVENTURE",
    SPIRITUALITY_RELIGION_BELIEFS = "SPIRITUALITY_RELIGION_BELIEFS",
    SCIENCE_TECH = "SCIENCE_TECH",
    SPORTS = "SPORTS",
    THEATRE = "THEATRE",
    MEETING = "MEETING",
}

export const MobilizonCategoryAndAll = {
    ...MobilizonCategory,
    ALL: "ALL"
}

export enum MobilizonEventJoinOptions {
    FREE = "FREE",
    EXTERNAL = "EXTERNAL",
}


export enum Intervall {
    WEEKLY = "WEEKLY",
    MONTHLY = "MONTHLY",
}

export enum EventPlaceType {
    "HOUSE" = "HOUSE",
    "APARTMENT" = "APARTMENT",
    "OFFICE" = "OFFICE",
    "OUTDOOR" = "OUTDOOR",
    "OTHER" = "OTHER"
}

export interface MobilizonStatistics {
    numberOfComments: number
    numberOfEvents: number
    numberOfGroups: number
    numberOfUsers: number
    numberOfInstanceFollowers: number
    numberOfInstanceFollowings: number
    numberOfLocalComments: number
    numberOfLocalEvents: number
    numberOfLocalGroups: number
}

export interface EventsStatistics {
    singleEventsCount: number
    seriesEventsCount: number
    uploadedEventsCount: number
    importedEventsCount: number
    confirmedEventsCount: number
    tentativeEventsCount: number
    cancelledEventsCount: number
    incomingEventsCount: number
}

export interface ServerError {
    message: string;
    errors: Record<string, string[]>;
}


export enum EventStatus {
    "TENTATIVE" = "TENTATIVE",
    "CONFIRMED" = "CONFIRMED",
    "CANCELLED" = "CANCELLED"
}

export interface IEvent {
    attributedTo: {
        id: string;
        domain: string | null;
        name: string | null;
        __typename: string;
    }
    uuid: string;
    title: string;
    category: MobilizonCategory;
    status: EventStatus;
    url: string;
    picture: {
        url: string;
    } | null;
    beginsOn: string;
    endsOn: string;
    __typename: string;
}

export interface IEventDetailed extends IEvent {
    attributedTo: {
        preferredUsername: string | null;
    } & IEvent['attributedTo'];
    physicalAddress: AddressForm | null;
    onlineAddress: string | null;
    joinOptions: MobilizonEventJoinOptions;
    tags: {
        title: string
    }[];
    description: string | null;
    externalParticipationUrl: string | null;
    language: string;
    relatedEvents: IEventDetailed[];
}

export interface IOrganisation {
    avatar: {
        alt: string | null;
        contentUrl: string;
        name: string;
        size: number | null;
        url: string;
        __typename: string;
    } | null;
    domain: string | null;
    members: {
        elements: Array<Member>;
    }
    id: string;
    name: string;
    physicalAddress: AddressForm | null;
    preferredUsername: string;
    summary: string | null;
    type: string
    url: string;
    __typename: string;

}

export interface EventTag {
    title: string
    slug: string
}

export interface Member {
    id: string;
    name: string;
    preferredUsername: string;
    suspended: boolean;
}