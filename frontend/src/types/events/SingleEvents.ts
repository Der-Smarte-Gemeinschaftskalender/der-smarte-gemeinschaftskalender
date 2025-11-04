import zod from "@/lib/zod";
import dayjs from "@/lib/dayjs";
import { formatInputDate, stripHtml } from "@/lib/helper";
import { DefaultEventFormSchema, DefaultEventSchema } from "@/types/events/DefaultEvents";
import { CreatedEventSchema } from "@/types/events/CreatedEvents";

export const SingleEventRequestSchema = DefaultEventSchema.extend({
    name: zod
        .string()
        .nonempty(),
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
        .nonempty(),
    user_id: zod
        .number()
        .nonnegative()
        .optional(),
    created_event: CreatedEventSchema
});

export type SingleEventResponse = zod.infer<typeof SingleEventResponseSchema>;

export const SingleEventFormSchema = DefaultEventFormSchema.extend({
    name: zod
        .string()
        .min(2),
    description: zod
        .string()
        .refine(
            (val) => stripHtml(val).length > 0,
            { message: "Die Beschreibung darf nicht leer sein." }
        ),
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