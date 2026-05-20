import zod from "@/lib/zod";
import { DefaultEventFormSchema, DefaultEventSchema } from '@/types/events/DefaultEvents';

export const ImportedEventSchema = DefaultEventSchema.extend({
    id: zod
        .number(),
    url: zod
        .string()
        .nonempty(),
    is_active: zod
        .boolean()
        .or(zod.number())
        .transform(val => Boolean(val)),
    mobilizon_fields: zod.object({
        description: zod
            .string()
            .optional(),
    }),
});

export type ImportedEvent = zod.infer<typeof ImportedEventSchema>;

export const ImportedEventCreateResponseSchema = ImportedEventSchema;

export type ImportedEventCreateResponse = zod.infer<typeof ImportedEventCreateResponseSchema>;


export const ImportedEventFormSchema = DefaultEventFormSchema.extend({
    description: zod.string().optional(),
    url: zod
        .string()
        .default('')
        .refine((val) => /^(https?:\/\/)?([\w-]+\.)+[\w-]{2,}(\/[\w.%+-]*)*(\?.*)?$/.test(val), {
            message: 'Bitte eine gültige URL eingeben.',
        }),
    is_active: zod.boolean().default(true),
});

export type ImportedEventForm = zod.infer<typeof ImportedEventFormSchema>