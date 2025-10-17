import zod from "@/lib/zod";
import {
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status
} from "@/lib/const";
import { EventPlaceType } from "@/types/General";

export const addressDefaults = {
    description: '',
    country: 'Deutschland',
    region: 'Schleswig-Holstein',
    locality: '',
    postalCode: '',
    street: '',
    type: EventPlaceType.OFFICE,
    geom: '',
    timezone: 'Europe/Berlin',
    id: null
}

export const AddressFormSchema = zod.object({
    description: zod
        .string()
        .default(addressDefaults.description),
    country: zod
        .string()
        .default(addressDefaults.country),
    region: zod
        .string()
        .default(addressDefaults.region),
    locality: zod
        .string()
        .default(addressDefaults.locality),
    postalCode: zod
        .string()
        .default(addressDefaults.postalCode),
    street: zod
        .string()
        .default(addressDefaults.street),
    type: zod
        .nativeEnum(EventPlaceType)
        .default(EventPlaceType.OFFICE),
    geom: zod
        .string()
        .default(addressDefaults.geom),
    timezone: zod
        .string()
        .nullable()
        .default(addressDefaults.timezone),
    id: zod
        .string()
        .nullable()
        .default(addressDefaults.id),
}).superRefine((value, ctx) => {
    if (value.description && (!value.locality || !value.postalCode || !value.street)) {
        ctx.addIssue({
            code: zod.ZodIssueCode.custom,
            message: 'Wenn eine Beschreibung angegeben ist, müssen Ort, Postleitzahl und Straße ausgefüllt sein.'
        });
    }
});

export type AddressForm = zod.infer<typeof AddressFormSchema>;


export const pictureDefaults = {
    name: '',
    alt: ''
}

export const PictureSchema = zod.object({
    name: zod
        .string()
        .nonempty()
        .default(pictureDefaults.name),
    alt: zod
        .string()
        .nullable()
        .default(pictureDefaults.alt),
    url: zod
        .string()
        .optional(),
    file: zod
        .instanceof(File)
        .optional()
        .refine((file) => {
            if (file === undefined) return true;
            if (file instanceof File && file.size > 0) return ['image/gif', 'image/png', 'image/jpeg', 'image/webp'].includes(file.type);
            return false;
        },
            {
                message: 'Bitte eine gültige Bilddatei auswählen.',
                params: { mimeType: 'image/jpeg, image/png' }
            }
        )

});


export const mobilizonFieldsDefaults = {
    picture: undefined,
    pictureAlt: '',
    description: '',
    category: mobilizon_category_options[0].value,
    joinOptions: mobilizon_event_join_options[0].value,
    externalParticipationUrl: undefined,
    language: mobilizon_event_language_options[0].value,
    status: mobilizon_event_status[1].value,
    visibility: 'PUBLIC',
    onlineAddress: '',
    tags: <Array<string>>[],
}

export const MobilizonFieldsFormSchema = zod.object({
    picture: zod
        .instanceof(File)
        .optional()
        .refine((file) => {
            if (file === undefined) return true;
            if (file instanceof File && file.size > 0) return ['image/gif', 'image/png', 'image/jpeg', 'image/webp'].includes(file.type);
            return false;
        },
            {
                message: 'Bitte eine gültige Bilddatei auswählen.',
                params: { mimeType: 'image/jpeg, image/png' }
            }
        ),
    pictureAlt: zod
        .string()
        .optional()
        .default(mobilizonFieldsDefaults.pictureAlt),
    description: zod
        .string()
        .default(mobilizonFieldsDefaults.description),
    category: zod
        .string()
        .nonempty()
        .default(mobilizonFieldsDefaults.category),
    tags: zod
        .array(zod.string())
        .default(mobilizonFieldsDefaults.tags),
    joinOptions: zod
        .string()
        .nonempty()
        .default(mobilizonFieldsDefaults.joinOptions),
    language: zod
        .string()
        .nonempty()
        .default(mobilizonFieldsDefaults.language),
    status: zod
        .string()
        .nonempty()
        .default(mobilizonFieldsDefaults.status),
    externalParticipationUrl: zod
        .string()
        .nullable()
        .optional()
        .refine(
            (val) =>
                !val ||
                /^(https?:\/\/)([\w-]+\.)+[\w-]{2,}(\/[\w.-]*)*(\?.*)?$/.test(val),
            {
                message:
                    'Bei Angabe einer externen Teilnahme-URL bitte eine gültige URL eingeben.',
            }
        ),
    visibility: zod
        .string()
        .nonempty()
        .default(mobilizonFieldsDefaults.visibility),
    physicalAddress: AddressFormSchema
        .nullable()
        .optional(),
    onlineAddress: zod
        .string()
        .nullable()
        .refine(
            (val) =>
                !val ||
                /^(https?:\/\/)([\w-]+\.)+[\w-]{2,}(\/[\w.-]*)*(\?.*)?$/.test(val),
            {
                message:
                    'Bei Angabe einer Online-Adresse bitte eine gültige URL eingeben.',
            }
        )
        .default(mobilizonFieldsDefaults.onlineAddress)
});

export type MobilizonFieldsForm = zod.infer<typeof MobilizonFieldsFormSchema>;

export const MobilizonFieldsSchema = zod.object({
    picture: PictureSchema
        .optional()
        .nullable(),
    description: zod
        .string(),
    category: zod
        .string()
        .nonempty(),
    joinOptions: zod
        .string()
        .nonempty(),
    externalParticipationUrl: zod
        .string()
        .optional()
        .nullable()
        .refine(
            (val) =>
                !val ||
                /^(https?:\/\/)([\w-]+\.)+[\w-]{2,}(\/[\w.-]*)*(\?.*)?$/.test(val),
            {
                message:
                    'Bei Angabe einer externen Teilnahme-URL bitte eine gültige URL eingeben.',
            }
        ),
    language: zod
        .string()
        .nonempty(),
    status: zod
        .string()
        .nonempty(),
    visibility: zod
        .string()
        .nonempty(),
    tags: zod
        .array(zod.string())
        .optional()
        .nullable(),
    onlineAddress: zod
        .string()
        .nullable()
        .optional()
        .refine(
            (val) =>
                !val ||
                /^(https?:\/\/)([\w-]+\.)+[\w-]{2,}(\/[\w.-]*)*(\?.*)?$/.test(val),
            {
                message:
                    'Bei Angabe einer Online-Adresse bitte eine gültige URL eingeben.',
            }
        ),
    physicalAddress: AddressFormSchema
        .optional()
        .nullable()
});

export type MobilizonFields = zod.infer<typeof MobilizonFieldsSchema>;


export const MobilizonGroupSchema = zod.object({
    id: zod.coerce.number(),
    name: zod.string().nonempty(),
    preferredUsername: zod.string().nonempty()
});

export type MobilizonGroup = zod.infer<typeof MobilizonGroupSchema>;