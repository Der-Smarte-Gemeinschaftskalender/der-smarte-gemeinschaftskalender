import zod from '@/lib/zod';
import type { Option } from './General';
import { stripHtml } from '@/lib/helper';
import i18n, { type SupportedLocale } from '@/i18n';

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
    /** Each event has one template per language */
    locale: SupportedLocale;
    subject: string;
    body: string;
    created_at: string;
    updated_at: string;
}

// Labels / descriptions use getters so they are translated on every access (language-reactive).
const { t } = i18n.global;

export const emailEventTypeLabels: Record<EmailEventType, string> = {
    get [EmailEventType.VALIDATION]() { return t('app.admin.emailTemplates.events.validation'); },
    get [EmailEventType.PASSWORD_RESET]() { return t('app.admin.emailTemplates.events.passwordReset'); },

    get [EmailEventType.APPROVAL_REQUEST_SENT]() { return t('app.admin.emailTemplates.events.approvalRequestSent'); },
    get [EmailEventType.APPROVAL_REQUEST_HANDLED]() { return t('app.admin.emailTemplates.events.approvalRequestHandled'); },

    get [EmailEventType.ORGANISATION_INVITATION]() { return t('app.admin.emailTemplates.events.organisationInvitation'); },
    get [EmailEventType.ORGANISATION_REMOVAL]() { return t('app.admin.emailTemplates.events.organisationRemoval'); },
};

export const availableEmailVariables: Record<EmailEventType, { [key: string]: string }> = {
    [EmailEventType.VALIDATION]: {
        get appName() { return t('app.admin.emailTemplates.variables.appName'); },
    },
    [EmailEventType.PASSWORD_RESET]: {
        get appName() { return t('app.admin.emailTemplates.variables.appName'); },
    },

    [EmailEventType.APPROVAL_REQUEST_SENT]: {
        get appName() { return t('app.admin.emailTemplates.variables.appName'); },
        get requestTypeName() { return t('app.admin.emailTemplates.variables.requestTypeNameSent'); },
    },
    [EmailEventType.APPROVAL_REQUEST_HANDLED]: {
        get appName() { return t('app.admin.emailTemplates.variables.appName'); },
        get requestTypeName() { return t('app.admin.emailTemplates.variables.requestTypeNameHandled'); },
        get handledStatus() { return t('app.admin.emailTemplates.variables.handledStatus'); },
    },

    [EmailEventType.ORGANISATION_INVITATION]: {
        get appName() { return t('app.admin.emailTemplates.variables.appName'); },
        get organisationName() { return t('app.admin.emailTemplates.variables.organisationName'); },
    },
    [EmailEventType.ORGANISATION_REMOVAL]: {
        get appName() { return t('app.admin.emailTemplates.variables.appName'); },
        get organisationName() { return t('app.admin.emailTemplates.variables.organisationName'); },
    },
};

export const emailTemplateOptions: Array<{ value: EmailEventType; text: string }> = (
    Object.keys(emailEventTypeLabels) as EmailEventType[]
).map((value) => ({
    value,
    get text() { return emailEventTypeLabels[value]; },
})) as Option[];

export const EmailTemplateFormSchema = zod.object({
    on_event: zod.nativeEnum(EmailEventType, { message: 'app.admin.emailTemplates.validation.invalidEventType' }),
    subject: zod.string().max(70, { message: 'app.admin.emailTemplates.validation.subjectMax' }),
    body: zod
        .string()
        .refine((val) => stripHtml(val).length > 0, { message: 'app.admin.emailTemplates.validation.bodyRequired' }),
});
