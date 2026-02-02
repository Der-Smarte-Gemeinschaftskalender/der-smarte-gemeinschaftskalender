import zod from "@/lib/zod";
import { MobilizonFieldsFormSchema, MobilizonFieldsSchema } from "@/types/Mobilizon";

export const ApprovalRequestResponseSchema = zod.object({
    message: zod.string(),
    approval_request_id: zod.number(),
});

export type ApprovalRequestResponse = zod.infer<typeof ApprovalRequestResponseSchema>;

export const DefaultEventSchema = zod.object({
    organisation_preferred_username: zod
        .string()
        .optional(),
    mobilizon_group_id: zod
        .number()
        .or(zod.string())
        //to number if string and positive
        .refine((value => (typeof value === 'number' && value > 0) || (typeof value === 'string' && !isNaN(Number(value)) && Number(value) >= 0))),
    mobilizon_fields: MobilizonFieldsSchema
})

export type DefaultEvent = zod.infer<typeof DefaultEventSchema>  & Record<string, any>;


export const DefaultEventFormSchema = MobilizonFieldsFormSchema.extend({
    mobilizon_group_id: zod
        .number()
        .or(zod.string())
        .refine((value => (typeof value === 'number' && value > 0) || (typeof value === 'string' && !isNaN(Number(value)) && Number(value) >= 0))),
});

export type DefaultEventForm = zod.infer<typeof DefaultEventFormSchema> & Record<string, any>;