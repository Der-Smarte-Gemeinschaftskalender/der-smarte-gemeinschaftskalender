import zod from 'zod';



export const ProfileFormSchema = zod.object({
    email: zod
        .string()
        .email(),
    mobilizon_name: zod
        .string()
        .min(2, 'Der Name muss mindestens 2 Zeichen lang sein.')
        .max(100, 'Der Name darf maximal 100 Zeichen lang sein.')
});


export type ProfileForm = zod.infer<typeof ProfileFormSchema>;


export const PasswordFormSchema = zod.object({
    current_password: zod.string().min(1, 'Das aktuelle Passwort muss angegeben werden.'),
    new_password: zod.string().min(8, 'Das neue Passwort muss mindestens 8 Zeichen lang sein.'),
    new_password_confirm: zod.string().min(8, 'Die Bestätigung des neuen Passworts muss mindestens 8 Zeichen lang sein.')
});

export type PasswordForm = zod.infer<typeof PasswordFormSchema>;

