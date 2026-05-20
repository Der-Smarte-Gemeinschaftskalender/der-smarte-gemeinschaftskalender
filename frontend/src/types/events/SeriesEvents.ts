import zod from '@/lib/zod';
import dayjs from '@/lib/dayjs';
import { formatInputDate, stripHtml } from '@/lib/helper';
import { DefaultEventFormSchema, DefaultEventSchema } from './DefaultEvents.ts';

import {
    intervall_options,
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status,
} from '@/lib/const';
import { seriesEventsDaysControlsEnabled } from '@/lib/instanceConfig.ts';


export const SeriesEventsDefaults = {
    name: '',
    start: formatInputDate(),
    end: formatInputDate(dayjs().add(1, 'day').toDate()),
    time: dayjs().startOf('hour').format('HH:mm'),
    intervall: intervall_options[0]!.value,
    mobilizon_group_id: 0,
    duration: '1:00',
    description: '',
    category: mobilizon_category_options.value[0]!.value,
    joinOptions: mobilizon_event_join_options.value[0]!.value,
    language: mobilizon_event_language_options.value[0]!.value,
    status: mobilizon_event_status.value[1]!.value,
    visibility: 'PUBLIC',
    onlineAddress: '',
    tags: <Array<string>>[],
    weekly_day: new Date().getDay(),
    monthly_weeks: [1],
    monthly_week_day: new Date().getDay(),
    monthly_use_start_date_as_default: !seriesEventsDaysControlsEnabled,
    holidays_check: false,
    school_holidays_check: false,
    holidays_state: 'none',
};


export const SeriesEventSchema = DefaultEventSchema.extend({
    id: zod
        .number()
        .nonnegative(),
    name: zod
        .string()
        .min(3),
    start: zod
        .string()
        .date()
        .nonempty(),
    end: zod
        .string()
        .date(),
    time: zod
        .string()
        .nonempty(),
    intervall: zod
        .string()
        .nonempty(),
    duration: zod
        .string()
        .nonempty(),
    user_id: zod
        .number()
        .optional(),
    weekly_day: zod
        .coerce
        .number()
        .min(0)
        .max(6)
        .optional(),
    monthly_weeks: zod
        .array(zod
            .coerce
            .number()
            .refine((val) => val === -1 || (val >= 1 && val <= 4))
        )
        .optional(),
    monthly_week_day: zod
        .coerce
        .number()
        .min(0)
        .max(6)
        .optional(),
    monthly_use_start_date_as_default: zod
        .boolean()
        .optional(),
    holidays_check: zod
        .boolean()
        .optional(),
    school_holidays_check: zod
        .boolean()
        .optional(),
    holidays_state: zod
        .string()
        .optional(),
    created_events: zod.array(zod.object({
        id: zod
            .number()
            .nonnegative(),
        mobilizon_id: zod
            .number()
            .nonnegative(),
        mobilizon_uuid: zod
            .string()
            .nonempty(),
        start: zod
            .string()
            .nonempty(),
        time: zod
            .string()
            .nonempty(),
        duration: zod
            .string()
            .nonempty(),
        user_id: zod
            .number(),
    }))
});

export type SeriesEvent = zod.infer<typeof SeriesEventSchema>;

export type SeriesEventCreateResponse = zod.infer<typeof SeriesEventSchema>;


export const SeriesEventFormSchema = DefaultEventFormSchema.extend({
    name: zod
        .string({
            required_error: 'Der Name ist erforderlich.',
        })
        .min(3, 'Der Name muss mindestens 3 Zeichen lang sein.')
        .max(100, 'Der Name darf maximal 100 Zeichen lang sein.')
        .refine(
            (val) => val.trim().length >= 3, {
                message: "Der Name darf nicht leer sein."
        }),
    description: zod
        .string()
        .refine(
            (val) => stripHtml(val).length > 0, {
                message: "Die Beschreibung darf nicht leer sein."
         }),
    start: zod
        .string()
        .date()
        .nonempty(),
    end: zod
        .string()
        .date()
        .nonempty(),
    time: zod
        .string()
        .nonempty(),
    intervall: zod
        .string()
        .nonempty(),
    duration: zod
        .string()
        .nonempty()
        .regex(/^([0-9]{1,3}):[0-5][0-9]$/, {
            message: 'Bitte eine gültige Dauer im Format HHH:MM eingeben.'
        }),
    weekly_day: zod
        .coerce
        .number()
        .min(0)
        .max(6)
        .optional(),
    monthly_weeks: zod
        .array(zod
            .coerce
            .number()
            .refine((val) => val === -1 || (val >= 1 && val <= 4))
        )
        .optional(),
    monthly_week_day: zod
        .coerce
        .number()
        .min(0)
        .max(6)
        .optional(),
    monthly_use_start_date_as_default: zod
        .boolean()
        .optional(),
    holidays_check: zod
        .boolean()
        .optional(),
    school_holidays_check: zod
        .boolean()
        .optional(),
    holidays_state: zod
        .string()
        .optional(), 
}).superRefine((value, ctx) => {
    if (dayjs(value.start).isAfter(dayjs(value.end))) {
        ctx.addIssue({
            code: zod.ZodIssueCode.invalid_date,
            message: 'Das Startdatum darf nicht nach dem Enddatum liegen.',
            path: ['start'],
        });
    }

    return true;
});

export type SeriesEventForm = zod.infer<typeof SeriesEventFormSchema>;



