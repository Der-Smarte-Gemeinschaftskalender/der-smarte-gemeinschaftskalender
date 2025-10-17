import zod from "@/lib/zod";
import { DefaultEventFormSchema, DefaultEventSchema } from "@/types/events/DefaultEvents";

export const ImportedEventSchema = DefaultEventSchema.extend({
    id: zod.number(),
    url: zod.string().nonempty(),
    is_active: zod.boolean(),
});

export type ImportedEvent = zod.infer<typeof ImportedEventSchema>;


export const ImportedEventFormSchema = DefaultEventFormSchema.extend({
    url: zod
        .string()
        .default('')
        .refine(
            (val) => val === '' || /^(https?:\/\/)?([\w-]+\.)+[\w-]{2,}(\/[\w.-]*)*(\?.*)?$/.test(val),
            { message: 'Bitte eine gültige URL eingeben.' }
        ),
    is_active: zod
        .boolean()
        .default(true),
});

export type ImportedEventForm = zod.infer<typeof ImportedEventFormSchema>