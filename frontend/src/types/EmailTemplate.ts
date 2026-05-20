import zod from '@/lib/zod';
import type { Option } from './General';
import { stripHtml } from '@/lib/helper';

export enum EmailEventType {
    // Auth
    VALIDATION = 'VALIDATION',
    PASSWORD_RESET = 'PASSWORD_RESET',

    // Approval Request
    APPROVAL_REQUEST_SENT = 'APPROVAL_REQUEST_SENT',
    APPROVAL_REQUEST_HANDLED = 'APPROVAL_REQUEST_HANDLED',

    // Organisation
    ORGANISATION_INVITATION = 'ORGANISATION_INVITATION',
    ORGANISATION_REMOVAL = 'ORGANISATION_REMOVAL',
}

export interface EmailTemplate {
    id: number;
    on_event: EmailEventType | null;
    subject: string;
    body: string;
    created_at: string;
    updated_at: string;
}

export const emailEventTypeLabels: Record<EmailEventType, string> = {
    [EmailEventType.VALIDATION]: 'Validierung der E-Mail-Adresse',
    [EmailEventType.PASSWORD_RESET]: 'Passwort zurücksetzen',

    [EmailEventType.APPROVAL_REQUEST_SENT]: 'Genehmigungsanfrage gesendet',
    [EmailEventType.APPROVAL_REQUEST_HANDLED]: 'Genehmigungsanfrage bearbeitet',

    [EmailEventType.ORGANISATION_INVITATION]: 'Einladung zur Organisation',
    [EmailEventType.ORGANISATION_REMOVAL]: 'Aus der Organisation entfernt',
};

export const availableEmailVariables: Record<EmailEventType, { [key: string]: string }> = {
    [EmailEventType.VALIDATION]: {
        appName: 'Der Name der Anwendung',
    },
    [EmailEventType.PASSWORD_RESET]: {
        appName: 'Der Name der Anwendung',
    },

    [EmailEventType.APPROVAL_REQUEST_SENT]: {
        appName: 'Der Name der Anwendung',
        requestTypeName: 'Angabetyp der Anfrage (z.B. "Serientermin: Erstellen")',
    },
    [EmailEventType.APPROVAL_REQUEST_HANDLED]: {
        appName: 'Der Name der Anwendung',
        requestTypeName: 'Der Name des Anfragenden (z.B. "Serientermin: Erstellen")',
        handledStatus: 'Der Status der Anfrage (z.B. "genehmigt" oder "abgelehnt")',
    },

    [EmailEventType.ORGANISATION_INVITATION]: {
        appName: 'Der Name der Anwendung',
        organisationName: 'Der Name der Organisation',
    },
    [EmailEventType.ORGANISATION_REMOVAL]: {
        appName: 'Der Name der Anwendung',
        organisationName: 'Der Name der Organisation',
    },
};

export const emailTemplateOptions: Array<{ value: EmailEventType; text: string }> = Object.entries(
    emailEventTypeLabels
).map(([value, text]) => ({
    value: value as EmailEventType,
    text,
})) as Option[];

export const EmailTemplateFormSchema = zod.object({
    on_event: zod.nativeEnum(EmailEventType, { message: 'Ungültiger Ereignistyp' }),
    subject: zod.string().max(70, { message: 'Der Betreff darf maximal 70 Zeichen lang sein.' }),
    body: zod
        .string()
        .refine((val) => stripHtml(val).length > 0, { message: 'Die Beschreibung darf nicht leer sein.' }),
});
