import zod from "@/lib/zod";
import dayjs from "@/lib/dayjs";
import { formatInputDate, stripHtml } from "@/lib/helper";
import {
    ApprovalRequestResponseSchema,
    DefaultEventFormSchema,
    DefaultEventSchema,
} from "@/types/events/DefaultEvents";
import { CreatedEventSchema } from "@/types/events/CreatedEvents";
import { isStrictModeEnabled } from "@/lib/instanceConfig";

export const SingleEventRequestSchema = DefaultEventSchema.extend({
    name: zod
        .string({
            required_error: 'Der Name des Termins ist erforderlich.',
        })
        .min(3, 'Der Name muss mindestens 3 Zeichen lang sein.')
        .max(100, 'Der Name darf maximal 100 Zeichen lang sein.')
        .refine(
            (val) => val.trim().length > 0, {
                message: "Der Name darf nicht leer sein."
        }),
    start: zod
        .string()
        .date()
        .nonempty(),
    time: zod
        .string()
        .nonempty(),
    duration: zod
        .string()
        .nonempty()
});

export type SingleEventRequest = zod.infer<typeof SingleEventRequestSchema>;

export const SingleEventResponseSchema = DefaultEventSchema.extend({
    id: zod
        .number()
        .nonnegative(),
    name: zod
        .string()
        .min(3),
    user_id: zod
        .number()
        .nonnegative()
        .optional(),
    created_event: CreatedEventSchema
});

export type SingleEventResponse = zod.infer<typeof SingleEventResponseSchema>;

export const SingleEventCreateResponseSchema = isStrictModeEnabled
    ? ApprovalRequestResponseSchema
    : SingleEventResponseSchema;

export type SingleEventCreateResponse = zod.infer<typeof SingleEventCreateResponseSchema>;

export const SingleEventFormSchema = DefaultEventFormSchema.extend({
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
        .nonempty()
        .default(formatInputDate()),
    time: zod
        //HH:mm format
        .string()
        .nonempty()
        .regex(/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/, {
            message: 'Bitte eine gültige Uhrzeit im Format HH:mm eingeben.'
        })
        .default(dayjs().startOf('hour').format('HH:mm')),
    duration: zod
        .string()
        .nonempty()
        //HHH:mm format
        .regex(/^([0-9]{1,3}):[0-5][0-9]$/, {
            message: 'Bitte eine gültige Dauer im Format HHH:MM eingeben.'
        })
        .default('1:00'),

})

export type SingleEventForm = zod.infer<typeof SingleEventFormSchema> & Record<string, any>;