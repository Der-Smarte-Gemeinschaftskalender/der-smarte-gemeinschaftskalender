import { USER_TYPES, type Option, Intervall, EventStatus } from "@/types/General";
import { mobilizon_category_options, mobilizon_main_category_options, mobilizon_category_options_all } from "./categoryOptions";
import { computed } from 'vue';
import i18n from '@/i18n';

const { t } = i18n.global;

// Language-dependent labels/options are computeds so they follow a language switch.
export const intervall_keys = computed(() => ({
    weekly: t('common.weekly'),
    monthly: t('common.monthly'),
}));

export const intervall_notification_options = computed<Option[]>(() => [
    {
        value: Intervall.WEEKLY,
        text: `${t('common.weekly')} (${t('common.mondayMorning')})`,
    },
    {
        value: Intervall.MONTHLY,
        text: `${t('common.monthly')} (${t('common.firstDayOfMonth')})`,
    },
]);

export const intervall_options = computed<Option[]>(() => [
    { value: Intervall.WEEKLY, text: intervall_keys.value.weekly },
    { value: Intervall.MONTHLY, text: intervall_keys.value.monthly },
]);

export const user_types_keys = computed<Record<USER_TYPES, string>>(() => ({
    [USER_TYPES.USER]: t('app.const.userTypes.user'),
    [USER_TYPES.ADMIN]: t('app.const.userTypes.admin'),
}));

export const user_types_options = computed<Option[]>(() => [
    { value: USER_TYPES.USER, text: user_types_keys.value[USER_TYPES.USER] },
    { value: USER_TYPES.ADMIN, text: user_types_keys.value[USER_TYPES.ADMIN] },
]);

export const user_is_active_options = computed<Option[]>(() => [
    { value: true, text: t('app.const.userIsActive.active') },
    { value: false, text: t('app.const.userIsActive.inactive') },
]);

export const mobilizon_event_join_options = computed<Option[]>(() => [
    { value: "FREE", text: t('public.eventJoinOptions.free') },
    { value: "EXTERNAL", text: t('public.eventJoinOptions.external') }
]);
export const mobilizon_event_language_options = computed<Option[]>(() => [
    { value: 'de', text: t('languages.de') },
    { value: 'da', text: t('languages.da') },
    { value: 'en', text: t('languages.en') },
    { value: 'fy', text: t('languages.fy') },
    { value: 'nds', text: t('languages.nds') },
    { value: 'sw', text: t('languages.sw') },
    { value: 'no', text: t('languages.no') },
    { value: 'fr', text: t('languages.fr') },
    { value: 'it', text: t('languages.it') },
    { value: 'es', text: t('languages.es') },
    { value: 'wen', text: t('languages.wen') },
    { value: 'ar', text: t('languages.ar') },
]);
export const mobilizon_event_status = computed<Option[]>(() => [
    { value: EventStatus.TENTATIVE, text: t('public.eventStatus.tentative') },
    { value: EventStatus.CONFIRMED, text: t('public.eventStatus.confirmed') },
    { value: EventStatus.CANCELLED, text: t('public.eventStatus.cancelled') }
]);

export const fontSelectionOptions: Array<{ text: string; value: string }> = [
    { text: 'Arial', value: 'Arial' },
    { text: 'Arial Black', value: 'Arial Black' },
    { text: 'Verdana', value: 'Verdana' },
    { text: 'Tahoma', value: 'Tahoma' },
    { text: 'Trebuchet MS', value: 'Trebuchet MS' },
    { text: 'Helvetica', value: 'Helvetica' },
    { text: 'Fira Sans', value: 'Fira Sans' },
    { text: 'Times New Roman', value: 'Times New Roman' },
    { text: 'Georgia', value: 'Georgia' },
    { text: 'Garamond', value: 'Garamond' },
    { text: 'Palatino', value: 'Palatino' },
    { text: 'Book Antiqua', value: 'Book Antiqua' },
    { text: 'Courier New', value: 'Courier New' },
    { text: 'Lucida Console', value: 'Lucida Console' },
    { text: 'Monaco', value: 'Monaco' },
    { text: 'Comic Sans MS', value: 'Comic Sans MS' },
    { text: 'Impact', value: 'Impact' },
    { text: 'Lucida Sans', value: 'Lucida Sans' },
    { text: 'Century Gothic', value: 'Century Gothic' },
    { text: 'Candara', value: 'Candara' },
    { text: 'Optima', value: 'Optima' },
    { text: 'Gill Sans', value: 'Gill Sans' },
    { text: 'Segoe UI', value: 'Segoe UI' },
    { text: 'Calibri', value: 'Calibri' },
    { text: 'Cambria', value: 'Cambria' },
    { text: 'Consolas', value: 'Consolas' },
    { text: 'Futura', value: 'Futura' },
    { text: 'Rockwell', value: 'Rockwell' },
    { text: 'Copperplate', value: 'Copperplate' },
    { text: 'Didot', value: 'Didot' },
];

export const requested_organisation_status = computed(() => ({
    REQUESTED: t('app.const.organisationStatus.requested'),
    REQUEST_DENIED: t('app.const.organisationStatus.requestDenied'),
    ACTIVE: t('app.const.organisationStatus.active'),
    INACTIVE: t('app.const.organisationStatus.inactive'),
}));

export const member_role = computed(() => ({
    ADMINISTRATOR: t('app.const.memberRole.administrator'),
    INVITED: t('app.const.memberRole.invited'),
}));

/** Week of the month for monthly series events; -1 is the last week. */
export const monthlyIntervalWeekOptions = computed<Array<{ value: number; text: string }>>(() => [
    { value: 1, text: t('app.const.monthWeek.first') },
    { value: 2, text: t('app.const.monthWeek.second') },
    { value: 3, text: t('app.const.monthWeek.third') },
    { value: 4, text: t('app.const.monthWeek.fourth') },
    { value: -1, text: t('app.const.monthWeek.last') },
]);

export const getMonthWeekLabel = (value: number): string | undefined =>
    monthlyIntervalWeekOptions.value.find((option) => option.value === value)?.text;

/** Weekdays Monday first; value is the JS weekday (0 = Sunday). */
export const weekDayOptions = computed<Array<{ value: number; label: string; shortLabel: string }>>(() =>
    (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const).map((day, index) => ({
        value: (index + 1) % 7,
        label: t(`app.shared.weekDays.long.${day}`),
        shortLabel: t(`app.shared.weekDays.short.${day}`),
    }))
);

export const getWeekDayLabel = (value: number): string | undefined =>
    weekDayOptions.value.find((option) => option.value === value)?.label;

export {
    mobilizon_category_options,
    mobilizon_main_category_options,
    mobilizon_category_options_all
}