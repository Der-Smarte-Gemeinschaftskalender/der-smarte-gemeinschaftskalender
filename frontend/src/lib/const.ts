import { USER_TYPES, type Option, Intervall, EventStatus } from "@/types/General";
import { mobilizon_category_options, mobilizon_main_category_options, mobilizon_category_options_all } from "./categoryOptions";
import { computed } from 'vue';
import i18n from '@/i18n';

const { t } = i18n.global;

// Label maps / option lists below use getters so that the text is translated on every access
// (language-reactive) while consumers can keep using them like plain objects.
export const intervall_keys = {
    get weekly() { return t('common.weekly'); },
    get monthly() { return t('common.monthly'); },
};

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

export const intervall_options: Option[] = [
    { value: Intervall.WEEKLY, get text() { return intervall_keys.weekly; } },
    { value: Intervall.MONTHLY, get text() { return intervall_keys.monthly; } },
];

export const user_types_keys = {
    get user() { return t('app.const.userTypes.user'); },
    get admin() { return t('app.const.userTypes.admin'); },
};

export const user_types_options: Option[] = [
    { value: USER_TYPES.USER, get text() { return user_types_keys.user; } },
    { value: USER_TYPES.ADMIN, get text() { return user_types_keys.admin; } }
];

export const user_is_active_options: Option[] = [
    { value: true, get text() { return t('app.const.userIsActive.active'); } },
    { value: false, get text() { return t('app.const.userIsActive.inactive'); } }
];

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

export const requested_organisation_status = {
    get REQUESTED() { return t('app.const.organisationStatus.requested'); },
    get REQUEST_DENIED() { return t('app.const.organisationStatus.requestDenied'); },
    get ACTIVE() { return t('app.const.organisationStatus.active'); },
    get INACTIVE() { return t('app.const.organisationStatus.inactive'); },
}

export const member_role = {
    get ADMINISTRATOR() { return t('app.const.memberRole.administrator'); },
    get INVITED() { return t('app.const.memberRole.invited'); },
}

export const monthlyIntervalWeekOptions: Array<{ value: number; readonly text: string }> = [
    { value: 1, get text() { return t('app.const.monthWeek.first'); } },
    { value: 2, get text() { return t('app.const.monthWeek.second'); } },
    { value: 3, get text() { return t('app.const.monthWeek.third'); } },
    { value: 4, get text() { return t('app.const.monthWeek.fourth'); } },
    { value: -1, get text() { return t('app.const.monthWeek.last'); } },
];

export {
    mobilizon_category_options,
    mobilizon_main_category_options,
    mobilizon_category_options_all
}