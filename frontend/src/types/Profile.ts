import zod, { ZodType } from 'zod';
import { SUPPORTED_LOCALES } from '@/i18n';



export const ProfileFormSchema = zod.object({
    email: zod
        .string()
        .nonempty('validation.email.required')
        .email('validation.email.invalid'),
    mobilizon_name: zod
        .string({
            required_error: 'validation.name.required'
        })
        .nonempty('validation.name.required')
        .min(3, 'validation.name.min')
        .max(100, 'validation.name.max')
        .refine((val) => val.trim().length > 0, {
            message: 'validation.name.notEmpty'
        }),
    // Language of the emails sent to the user, does not change the UI language.
    // Optional: unknown until loaded for users whose cached data predates the field.
    locale: zod.enum(SUPPORTED_LOCALES).optional()
});


export type ProfileForm = zod.infer<typeof ProfileFormSchema>;


export const PasswordFormSchema = zod.object({
    current_password: zod
        .string()
        .nonempty('validation.password.currentRequired'),
    new_password: zod
        .string()
        .nonempty('validation.password.newRequired')
        .min(8, 'validation.password.newMin'),
    new_password_confirm: zod
        .string()
        .nonempty('validation.password.confirmRequired')
        .min(8, 'validation.password.confirmMin')
}).superRefine(({ new_password, new_password_confirm }, ctx) => {
    if (new_password !== new_password_confirm) {
        ctx.addIssue({
            path: ['new_password_confirm'],
            code: zod.ZodIssueCode.custom,
            message: 'validation.password.mismatch',
        });
    }
});

export type PasswordForm = zod.infer<typeof PasswordFormSchema>;

