import zod from '@/lib/zod';
import { stripHtml } from '@/lib/helper';
import { mimeTypes } from '@/types/File';
import {
    mobilizon_category_options,
    mobilizon_event_join_options,
    mobilizon_event_language_options,
    mobilizon_event_status,
} from '@/lib/const';

import { EventPlaceType } from '@/types/General';

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
    id: null,
};

const optionalAddressString = (defaultValue: string) =>
    zod
        .string()
        .nullable()
        .default(defaultValue)
        .transform((value) => value ?? '');

export const AddressFormSchema = zod
    .object({
        description: optionalAddressString(addressDefaults.description),
        country: optionalAddressString(addressDefaults.country),
        region: optionalAddressString(addressDefaults.region),
        locality: optionalAddressString(addressDefaults.locality),
        postalCode: optionalAddressString(addressDefaults.postalCode),
        street: optionalAddressString(addressDefaults.street),
        type: zod.nativeEnum(EventPlaceType).default(EventPlaceType.OFFICE),
        geom: optionalAddressString(addressDefaults.geom),
        timezone: zod.string().nullable().default(addressDefaults.timezone),
        id: zod.string().nullable().default(addressDefaults.id),
    })
    .superRefine((value, ctx) => {
        if (value.description && !value.geom?.trim()) {
            ctx.addIssue({
                code: zod.ZodIssueCode.custom,
                message:
                    'Die Adresse konnte keinem Ort zugeordnet werden. Wählen einen Vorschlag aus der Liste aus. Die Vorschläge erscheinen nur, wenn eine Adresse eingeben wurde, die in OpenStreetMap hinterlegt ist.',
            });
        }
    });

export type AddressForm = zod.infer<typeof AddressFormSchema>;

export const pictureDefaults = {
    name: '',
    alt: '',
};

export const PICTURE_MAX_BYTES = 2_097_152;
export const PICTURE_ALLOWED_MIME_TYPES = ['image/gif', 'image/png', 'image/jpeg', 'image/webp'];

const describeFileFormat = (file: File): string => {
    const knownFormat = mimeTypes[file.type];
    if (knownFormat) return knownFormat;

    const extension = file.name.includes('.') ? file.name.split('.').pop() : '';
    return extension ? extension.toUpperCase() : file.type || 'unbekannt';
};

export const validatePictureFile = (file: File | undefined, ctx: zod.RefinementCtx): void => {
    if (!file) return;

    if (!(file instanceof File) || file.size === 0) {
        ctx.addIssue({
            code: zod.ZodIssueCode.custom,
            message:
                'Die ausgewählte Datei ist leer oder konnte nicht gelesen werden. Bitte wählen Sie sie erneut aus.',
        });
        return;
    }

    if (!PICTURE_ALLOWED_MIME_TYPES.includes(file.type)) {
        ctx.addIssue({
            code: zod.ZodIssueCode.custom,
            message: `Das Format ${describeFileFormat(file)} wird nicht unterstützt. Bitte wählen Sie ein Bild im Format JPG, PNG, GIF oder WEBP.`,
        });
        return;
    }

    if (file.size >= PICTURE_MAX_BYTES) {
        const sizeInMb = (file.size / 1_048_576).toFixed(1).replace('.', ',');
        ctx.addIssue({
            code: zod.ZodIssueCode.custom,
            message: `Das Bild ist mit ${sizeInMb} MB zu groß. Bitte wählen Sie ein Bild mit maximal 2 MB.`,
        });
    }
};

export const PictureSchema = zod.object({
    name: zod.string().nonempty().default(pictureDefaults.name),
    alt: zod.string().nullable().default(pictureDefaults.alt),
    url: zod.string().optional(),
    file: zod.instanceof(File).optional().superRefine(validatePictureFile),
});

export const mobilizonFieldsDefaults = {
    picture: undefined,
    pictureAlt: '',
    description: '',
    category: mobilizon_category_options.value[0]!.value,
    joinOptions: mobilizon_event_join_options.value[0]!.value,
    externalParticipationUrl: undefined,
    language: mobilizon_event_language_options.value[0]!.value,
    status: mobilizon_event_status.value[1]!.value,
    visibility: 'PUBLIC',
    onlineAddress: '',
    tags: <Array<string>>[],
};

export const MobilizonFieldsFormSchema = zod.object({
    picture: zod.instanceof(File).optional().superRefine(validatePictureFile),
    pictureAlt: zod.string().optional().default(mobilizonFieldsDefaults.pictureAlt),
    description: zod.string().default(mobilizonFieldsDefaults.description),
    category: zod.string().nonempty().default(mobilizonFieldsDefaults.category),
    tags: zod.array(zod.string()).default(mobilizonFieldsDefaults.tags),
    joinOptions: zod.string().nonempty().default(mobilizonFieldsDefaults.joinOptions),
    language: zod.string().nonempty().default(mobilizonFieldsDefaults.language),
    status: zod.string().nonempty().default(mobilizonFieldsDefaults.status),
    externalParticipationUrl: zod
        .string()
        .nullable()
        .optional()
        .refine((val) => !val || /^(https?:\/\/)([\w-]+\.)+[\w-]{2,}(\/[\w.-]*)*(\?.*)?$/.test(val), {
            message: 'Bei Angabe einer externen Teilnahme-URL bitte eine gültige URL eingeben.',
        }),
    visibility: zod.string().nonempty().default(mobilizonFieldsDefaults.visibility),
    physicalAddress: AddressFormSchema.nullable().optional(),
    onlineAddress: zod
        .string()
        .nullable()
        .refine((val) => !val || /^(https?:\/\/)([\w-]+\.)+[\w-]{2,}(\/[\w.-]*)*(\?.*)?$/.test(val), {
            message: 'Bei Angabe einer Online-Adresse bitte eine gültige URL eingeben.',
        })
        .default(mobilizonFieldsDefaults.onlineAddress),
});

export type MobilizonFieldsForm = zod.infer<typeof MobilizonFieldsFormSchema>;

export const MobilizonFieldsSchema = zod.object({
    picture: PictureSchema.optional().nullable(),
    description: zod
        .string()
        .refine((val) => stripHtml(val).length > 0, { message: 'Die Beschreibung darf nicht leer sein.' }),
    category: zod.string().nonempty(),
    joinOptions: zod.string().nonempty(),
    externalParticipationUrl: zod
        .string()
        .optional()
        .nullable()
        .refine((val) => !val || /^(https?:\/\/)([\w-]+\.)+[\w-]{2,}(\/[\w.-]*)*(\?.*)?$/.test(val), {
            message: 'Bei Angabe einer externen Teilnahme-URL bitte eine gültige URL eingeben.',
        }),
    language: zod.string().nonempty(),
    status: zod.string().nonempty(),
    visibility: zod.string().nonempty(),
    tags: zod.array(zod.string()).optional().nullable(),
    onlineAddress: zod
        .string()
        .nullable()
        .optional()
        .refine((val) => !val || /^(https?:\/\/)([\w-]+\.)+[\w-]{2,}(\/[\w.-]*)*(\?.*)?$/.test(val), {
            message: 'Bei Angabe einer Online-Adresse bitte eine gültige URL eingeben.',
        }),
    physicalAddress: AddressFormSchema.optional().nullable(),
});

export type MobilizonFields = zod.infer<typeof MobilizonFieldsSchema>;

export const MobilizonGroupSchema = zod.object({
    id: zod.coerce.number(),
    name: zod.string().nonempty(),
    preferredUsername: zod.string().nonempty(),
});

export type MobilizonGroup = zod.infer<typeof MobilizonGroupSchema>;

export type MobilizonUser = {
    id: number;
    name: string;
    preferredUsername: string;
    access_token: string;
    user: object;
    person: {
        id: number;
        name: string;
        preferredUsername: string;
    };
    groups: MobilizonGroup[];
};
