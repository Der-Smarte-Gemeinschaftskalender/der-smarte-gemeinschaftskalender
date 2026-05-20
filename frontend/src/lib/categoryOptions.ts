import { type Option, MobilizonCategoryAndAll } from "@/types/General";
import i18n from '@/i18n';
import { computed } from 'vue';

const t = i18n.global.t;

export const getMobilizonCategoryOptions = (): Option[] => [
    { value: MobilizonCategoryAndAll.ARTS, text: t('categories.ARTS') },
    { value: MobilizonCategoryAndAll.BOOK_CLUBS, text: t('categories.BOOK_CLUBS') },
    { value: MobilizonCategoryAndAll.BUSINESS, text: t('categories.BUSINESS') },
    { value: MobilizonCategoryAndAll.CAUSES, text: t('categories.CAUSES') },
    { value: MobilizonCategoryAndAll.COMEDY, text: t('categories.COMEDY') },
    { value: MobilizonCategoryAndAll.CRAFTS, text: t('categories.CRAFTS') },
    { value: MobilizonCategoryAndAll.FOOD_DRINK, text: t('categories.FOOD_DRINK') },
    { value: MobilizonCategoryAndAll.HEALTH, text: t('categories.HEALTH') },
    { value: MobilizonCategoryAndAll.MUSIC, text: t('categories.MUSIC') },
    { value: MobilizonCategoryAndAll.AUTO_BOAT_AIR, text: t('categories.AUTO_BOAT_AIR') },
    { value: MobilizonCategoryAndAll.COMMUNITY, text: t('categories.COMMUNITY') },
    { value: MobilizonCategoryAndAll.FAMILY_EDUCATION, text: t('categories.FAMILY_EDUCATION') },
    { value: MobilizonCategoryAndAll.FASHION_BEAUTY, text: t('categories.FASHION_BEAUTY') },
    { value: MobilizonCategoryAndAll.FILM_MEDIA, text: t('categories.FILM_MEDIA') },
    { value: MobilizonCategoryAndAll.GAMES, text: t('categories.GAMES') },
    { value: MobilizonCategoryAndAll.LANGUAGE_CULTURE, text: t('categories.LANGUAGE_CULTURE') },
    { value: MobilizonCategoryAndAll.LEARNING, text: t('categories.LEARNING') },
    { value: MobilizonCategoryAndAll.LGBTQ, text: t('categories.LGBTQ') },
    { value: MobilizonCategoryAndAll.MOVEMENTS_POLITICS, text: t('categories.MOVEMENTS_POLITICS') },
    { value: MobilizonCategoryAndAll.NETWORKING, text: t('categories.NETWORKING') },
    { value: MobilizonCategoryAndAll.PARTY, text: t('categories.PARTY') },
    { value: MobilizonCategoryAndAll.PERFORMING_VISUAL_ARTS, text: t('categories.PERFORMING_VISUAL_ARTS') },
    { value: MobilizonCategoryAndAll.PETS, text: t('categories.PETS') },
    { value: MobilizonCategoryAndAll.PHOTOGRAPHY, text: t('categories.PHOTOGRAPHY') },
    { value: MobilizonCategoryAndAll.OUTDOORS_ADVENTURE, text: t('categories.OUTDOORS_ADVENTURE') },
    {
        value: MobilizonCategoryAndAll.SPIRITUALITY_RELIGION_BELIEFS,
        text: t('categories.SPIRITUALITY_RELIGION_BELIEFS'),
    },
    { value: MobilizonCategoryAndAll.SCIENCE_TECH, text: t('categories.SCIENCE_TECH') },
    { value: MobilizonCategoryAndAll.SPORTS, text: t('categories.SPORTS') },
    { value: MobilizonCategoryAndAll.THEATRE, text: t('categories.THEATRE') },
    { value: MobilizonCategoryAndAll.MEETING, text: t('categories.MEETING') },
];

export const getMobilizonMainCategoryOptions = (): Option[] => [
    {
        value: 'CULTURE_CREATIVE',
        text: t('categories.mainCategories.CULTURE_CREATIVE'),
        color: '#AD0477',
        image: '/material_generator/event_main_category/cards/CULTURE_CREATIVE.jpg',
        sub_categories: [
            MobilizonCategoryAndAll.PERFORMING_VISUAL_ARTS,
            MobilizonCategoryAndAll.FILM_MEDIA,
            MobilizonCategoryAndAll.THEATRE,
            MobilizonCategoryAndAll.LANGUAGE_CULTURE,
            MobilizonCategoryAndAll.PHOTOGRAPHY,
            MobilizonCategoryAndAll.MUSIC,
            MobilizonCategoryAndAll.CRAFTS,
            MobilizonCategoryAndAll.COMEDY,
            MobilizonCategoryAndAll.ARTS,
        ],
    },
    {
        value: 'COMMUNITY_COMMITMENT',
        text: t('categories.mainCategories.COMMUNITY_COMMITMENT'),
        color: '#6338B2',
        image: '/material_generator/event_main_category/cards/COMMUNITY_COMMITMENT.jpg',
        sub_categories: [
            MobilizonCategoryAndAll.COMMUNITY,
            MobilizonCategoryAndAll.LGBTQ,
            MobilizonCategoryAndAll.NETWORKING,
            MobilizonCategoryAndAll.MOVEMENTS_POLITICS,
            MobilizonCategoryAndAll.SPIRITUALITY_RELIGION_BELIEFS,
            MobilizonCategoryAndAll.BOOK_CLUBS,
            MobilizonCategoryAndAll.MEETING,
            MobilizonCategoryAndAll.CAUSES,
        ],
    },
    {
        value: 'FAMILY_LEISURE',
        text: t('categories.mainCategories.FAMILY_LEISURE'),
        color: '#005CD3',
        image: '/material_generator/event_main_category/cards/FAMILY_LEISURE.png',
        sub_categories: [
            MobilizonCategoryAndAll.OUTDOORS_ADVENTURE,
            MobilizonCategoryAndAll.PETS,
            MobilizonCategoryAndAll.PARTY,
            MobilizonCategoryAndAll.FASHION_BEAUTY,
            MobilizonCategoryAndAll.GAMES,
            MobilizonCategoryAndAll.AUTO_BOAT_AIR,
            MobilizonCategoryAndAll.FOOD_DRINK,
            MobilizonCategoryAndAll.FAMILY_EDUCATION,
        ],
    },
    {
        value: 'HEALTH_EXERCISE',
        text: t('categories.mainCategories.HEALTH_EXERCISE'),
        color: '#008158',
        image: '/material_generator/event_main_category/cards/HEALTH_EXERCISE.png',
        sub_categories: [MobilizonCategoryAndAll.HEALTH, MobilizonCategoryAndAll.SPORTS],
    },
    {
        value: 'ECONOMY_EDUCATION',
        text: t('categories.mainCategories.ECONOMY_EDUCATION'),
        color: '#06313B',
        image: '/material_generator/event_main_category/cards/ECONOMY_EDUCATION.png',
        sub_categories: [
            MobilizonCategoryAndAll.SCIENCE_TECH,
            MobilizonCategoryAndAll.LEARNING,
            MobilizonCategoryAndAll.BUSINESS,
        ],
    },
];

export const getMobilizonCategoryOptionsAll = (): Option[] => [
    { value: MobilizonCategoryAndAll.ALL, text: t('categories.ALL') },
    ...getMobilizonCategoryOptions(),
];

// Reactive computed refs that update automatically when locale changes
export const mobilizon_category_options = computed(() => getMobilizonCategoryOptions());
export const mobilizon_main_category_options = computed(() => getMobilizonMainCategoryOptions());
export const mobilizon_category_options_all = computed(() => getMobilizonCategoryOptionsAll());