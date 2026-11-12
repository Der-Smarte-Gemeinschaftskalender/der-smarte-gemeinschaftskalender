import { computed } from 'vue';
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

// Labels / descriptions are computeds so they follow a language switch.
const { t } = i18n.global;

export const emailEventTypeLabels = computed<Record<EmailEventType, string>>(() => ({
    [EmailEventType.VALIDATION]: t('app.admin.emailTemplates.events.validation'),
    [EmailEventType.PASSWORD_RESET]: t('app.admin.emailTemplates.events.passwordReset'),

    [EmailEventType.APPROVAL_REQUEST_SENT]: t('app.admin.emailTemplates.events.approvalRequestSent'),
    [EmailEventType.APPROVAL_REQUEST_HANDLED]: t('app.admin.emailTemplates.events.approvalRequestHandled'),

    [EmailEventType.ORGANISATION_INVITATION]: t('app.admin.emailTemplates.events.organisationInvitation'),
    [EmailEventType.ORGANISATION_REMOVAL]: t('app.admin.emailTemplates.events.organisationRemoval'),
}));

export const availableEmailVariables = computed<Record<EmailEventType, { [key: string]: string }>>(() => {
    const appName = t('app.admin.emailTemplates.variables.appName');
    const organisationName = t('app.admin.emailTemplates.variables.organisationName');

    return {
        [EmailEventType.VALIDATION]: { appName },
        [EmailEventType.PASSWORD_RESET]: { appName },

        [EmailEventType.APPROVAL_REQUEST_SENT]: {
            appName,
            requestTypeName: t('app.admin.emailTemplates.variables.requestTypeNameSent'),
        },
        [EmailEventType.APPROVAL_REQUEST_HANDLED]: {
            appName,
            requestTypeName: t('app.admin.emailTemplates.variables.requestTypeNameHandled'),
            handledStatus: t('app.admin.emailTemplates.variables.handledStatus'),
        },

        [EmailEventType.ORGANISATION_INVITATION]: { appName, organisationName },
        [EmailEventType.ORGANISATION_REMOVAL]: { appName, organisationName },
    };
});

export const emailTemplateOptions = computed<Option[]>(() =>
    (Object.entries(emailEventTypeLabels.value) as Array<[EmailEventType, string]>).map(([value, text]) => ({
        value,
        text,
    }))
);

export const EmailTemplateFormSchema = zod.object({
    on_event: zod.nativeEnum(EmailEventType, { message: 'validation.emailTemplate.invalidEventType' }),
    subject: zod.string().max(70, { message: 'validation.emailTemplate.subjectMax' }),
    body: zod
        .string()
        .refine((val) => stripHtml(val).length > 0, { message: 'validation.emailTemplate.bodyRequired' }),
});
