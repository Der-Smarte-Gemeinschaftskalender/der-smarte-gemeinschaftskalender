import zod from "@/lib/zod";
import { DefaultEventFormSchema, DefaultEventSchema } from "@/types/events/DefaultEvents";
import { AddressFormSchema } from "@/types/Mobilizon";


export const UploadedEventSchema = zod.object({
    uid: zod
        .string(),
    summary: zod
        .string()
        .nullable(),
    description: zod
        .string()
        .nullable(),
    category: zod
        .string()
        .optional(),
    dtstart: zod
        .string(),
    dtend: zod
        .string(),
    dtstart_tz: zod
        .string(),
    duration: zod
        .string()
        .regex(/^([0-9]{1,3}):[0-5][0-9]$/, {
            message: 'Bitte eine gültige Dauer im Format HHH:MM eingeben.'
        })
        .nullable(),
    location: zod
        .string()
        .or(AddressFormSchema)
        .nullable(),
    status: zod
        .string(),
    url: zod
        .string()
        .nullable(),
    already_exists: zod
        .boolean()
});

export type UploadedEvent = zod.infer<typeof UploadedEventSchema>;


export const UploadedEventRequestSchema = DefaultEventSchema.extend({
    uploaded_file: zod
        .instanceof(File)
        .nullable()
        .default(null)
        .refine((file) => file?.size ? file.size > 0 : false, 'Bitte eine iCal-Datei hochladen'),
})

export type UploadedEventRequest = zod.infer<typeof UploadedEventRequestSchema>;


export const UploadedEventFormSchema = DefaultEventFormSchema.extend({
    uploaded_file: zod
        .instanceof(File)
        .nullable()
        .default(null)
        .refine((file) => file?.size ? file.size > 0 : false, 'Bitte eine iCal-Datei hochladen'),
});

export type UploadedEventForm = zod.infer<typeof UploadedEventFormSchema>;


export const UploadedEventAcceptPayloadSchema = DefaultEventSchema.extend({
    filename: zod
        .string()
        .nonempty(),
    events: zod.array(UploadedEventSchema)
})

export type UploadedEventAcceptPayload = zod.infer<typeof UploadedEventAcceptPayloadSchema>;