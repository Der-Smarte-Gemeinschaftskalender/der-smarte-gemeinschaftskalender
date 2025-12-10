import zod, { ZodType } from 'zod';



export const ProfileFormSchema = zod.object({
    email: zod
        .string()
        .nonempty('Die E-Mail-Adresse ist erforderlich.')
        .email('Bitte geben Sie eine gültige E-Mail-Adresse ein.'),
    mobilizon_name: zod
        .string({
            required_error: 'Der Name ist erforderlich.'
        })
        .nonempty('Der Name ist erforderlich.')
        .min(3, 'Der Name muss mindestens 3 Zeichen lang sein.')
        .max(100, 'Der Name darf maximal 100 Zeichen lang sein.')
        .refine((val) => val.trim().length > 0, {
            message: 'Der Name darf nicht leer sein.'
        })
});


export type ProfileForm = zod.infer<typeof ProfileFormSchema>;


export const PasswordFormSchema = zod.object({
    current_password: zod
        .string()
        .nonempty('Das aktuelle Passwort ist erforderlich.'),
    new_password: zod
        .string()
        .nonempty('Das neue Passwort ist erforderlich.')
        .min(8, 'Das neue Passwort muss mindestens 8 Zeichen lang sein.'),
    new_password_confirm: zod
        .string()
        .nonempty('Die Bestätigung des neuen Passworts ist erforderlich.')
        .min(8, 'Die Bestätigung des neuen Passworts muss mindestens 8 Zeichen lang sein.')
}).superRefine(({ new_password, new_password_confirm }, ctx) => {
    if (new_password !== new_password_confirm) {
        ctx.addIssue({
            path: ['new_password_confirm'],
            code: zod.ZodIssueCode.custom,
            message: 'Die Passwörter stimmen nicht überein.',
        });
    }
});

export type PasswordForm = zod.infer<typeof PasswordFormSchema>;

