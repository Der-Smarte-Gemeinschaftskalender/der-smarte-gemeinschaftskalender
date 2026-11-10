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
            required_error: 'validation.name.eventRequired',
        })
        .min(3, 'validation.name.min')
        .max(100, 'validation.name.max')
        .refine(
            (val) => val.trim().length > 0, {
                message: "validation.name.notEmpty"
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
            required_error: 'validation.name.required',
        })
        .min(3, 'validation.name.min')
        .max(100, 'validation.name.max')
        .refine(
            (val) => val.trim().length >= 3, {
                message: "validation.name.notEmpty"
        }),
    description: zod
        .string()
        .refine(
            (val) => stripHtml(val).length > 0, {
                message: "validation.description.notEmpty"
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
            message: 'validation.time.invalid'
        })
        .default(dayjs().startOf('hour').format('HH:mm')),
    duration: zod
        .string()
        .nonempty()
        //HHH:mm format
        .regex(/^([0-9]{1,3}):[0-5][0-9]$/, {
            message: 'validation.duration.invalid'
        })
        .default('1:00'),

})

export type SingleEventForm = zod.infer<typeof SingleEventFormSchema> & Record<string, any>;