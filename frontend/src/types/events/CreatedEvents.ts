import zod from "@/lib/zod";
import { ApprovalRequestResponseSchema, DefaultEventFormSchema, DefaultEventSchema } from "@/types/events/DefaultEvents";
import { stripHtml } from "@/lib/helper";
import { isStrictModeEnabled } from "@/lib/instanceConfig";
import i18n from "@/i18n";

/** Kind of event a created event belongs to. */
export type CreatedEventParentType = 'single_event' | 'series_event' | 'uploaded_event' | 'imported_event';

const createdEventParentTypes: CreatedEventParentType[] = ['series_event', 'single_event', 'imported_event', 'uploaded_event'];

/**
 * Translated label for the kind of event a created event belongs to.
 * The value comes from the backend, so unexpected values fall back to "unknown".
 */
export const getEventTypeLabel = (eventType: string): string => {
    return createdEventParentTypes.includes(eventType as CreatedEventParentType)
        ? i18n.global.t(`app.shared.eventTypes.${eventType}`)
        : i18n.global.t('app.shared.eventTypes.unknown');
};

export const CreatedEventSchema = zod.object({
    id: zod
        .number()
        .nonnegative(),
    mobilizon_id: zod
        .number()
        .nonnegative(),
    mobilizon_uuid: zod
        .string()
        .uuid(),
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
});

export type CreatedEvent = zod.infer<typeof CreatedEventSchema>;

export const CreatedEventDetailsSchema = CreatedEventSchema.extend({
    ical_id: zod
        .string()
        .nullable(),
    ical_update_sequence: zod
        .number()
        .nonnegative()
        .nullable(),
    imported_event: zod
        .any()
        .nullable(),
    imported_events_id: zod
        .number()
        .nonnegative()
        .nullable(),
    uploaded_event: zod
        .any()
        .nullable(),
    uploaded_events_id: zod
        .number()
        .nonnegative()
        .nullable(),
    series_event: zod
        .any()
        .nullable(),
    series_events_id: zod
        .number()
        .nonnegative()
        .nullable(),
    single_event: zod
        .any()
        .nullable(),
    single_events_id: zod
        .number()
        .nonnegative()
        .nullable(),
});


export type CreatedEventDetails = zod.infer<typeof CreatedEventDetailsSchema> & Record<string, any>;


export const CreatedEventRequestSchema = DefaultEventSchema.extend({
    mobilizion_id: zod
        .number()
        .nonnegative(),
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
        .nonempty(),
});

export type CreatedEventRequest = zod.infer<typeof CreatedEventRequestSchema> & Record<string, any>;


export const CreatedEventFormSchema = DefaultEventFormSchema.extend({
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
            (val) => stripHtml(val).length > 0,
            { message: "validation.description.notEmpty" }
        ),
    start: zod
        .string()
        .nonempty(),
    time: zod
        .string()
        .nonempty(),
    duration: zod
        .string()
        .nonempty(),
});

export type CreatedEventForm = zod.infer<typeof CreatedEventFormSchema> & Record<string, any>;

export const CreatedEventUpdateResponseSchema = isStrictModeEnabled
    ? ApprovalRequestResponseSchema
    : CreatedEventDetailsSchema;

export type CreatedEventUpdateResponse = zod.infer<typeof CreatedEventUpdateResponseSchema>;