<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { I18nT, useI18n } from 'vue-i18n';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { dsgApi } from '@/lib/dsgApi';
import { localeOptions } from '@/i18n';
import {
    emailTemplateOptions,
    EmailTemplateFormSchema,
    availableEmailVariables,
    type EmailEventType,
} from '@/types/EmailTemplate';

import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import Loader from '@/components/KERN/cosmetics/Loader.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';
import VariablesLegend from '@/components/VariablesLegend.vue';

import { type EmailTemplate } from '@/types/EmailTemplate';
import Divider from '@/components/KERN/cosmetics/Divider.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const template = ref<EmailTemplate | null>(null);
// Read-only: every template row belongs to exactly one language
const templateLocale = ref<string>('');
const loading = ref(false);
const errorMessage = ref('');

const { handleSubmit, errors, isSubmitting } = useForm({
    validationSchema: toTypedSchema(EmailTemplateFormSchema),
});

const { value: on_event } = useField<string | null>('on_event');
const { value: subject } = useField<string>('subject');
const { value: body } = useField<string>('body');

const noErrors = computed(() => {
    return !errors.value.on_event && !errors.value.subject && !errors.value.body;
});

const loadTemplate = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await dsgApi.get(`/email-templates/${route.params.id}`);
        template.value = response.data as EmailTemplate;

        on_event.value = template.value!.on_event!;
        templateLocale.value = template.value.locale;
        subject.value = template.value.subject;
        body.value = template.value.body;
    } catch {
        errorMessage.value = t('app.admin.emailTemplates.loadError');
    } finally {
        loading.value = false;
    }
};

const saveTemplate = handleSubmit(async (values) => {
    errorMessage.value = '';

    try {
        const payload = {
            ...values,
            on_event: values.on_event || null,
        };

        await dsgApi.patch(`/email-templates/${route.params.id}`, payload);
        router.push({ name: 'admin.instance', query: { templateUpdated: 'true' } });
    } catch (error) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { errors?: Record<string, string[]> } } };
            errorMessage.value = axiosError.response?.data?.errors
                ? Object.values(axiosError.response.data.errors).flat().join(', ')
                : t('app.admin.emailTemplates.saveError');
        } else {
            errorMessage.value = t('app.admin.emailTemplates.saveError');
        }
    }
});

onMounted(loadTemplate);
</script>

<template>
    <div>
        <h1 class="kern-heading text-theme-primary mb-4">{{ t('app.admin.emailTemplates.editTitle') }}</h1>
        <p class="mt-1 mb-6">
            <b>{{ t('app.admin.shared.note') }}</b> <I18nT
                keypath="app.admin.shared.moreInfo"
                scope="global"
            >
                <template #link>
                    <LinkToDocs
                        path="Terminverwaltung/Einzeltermine/"
                        fragment="kalender-integrieren-ical-url-anbinden"
                    />
                </template>
            </I18nT>
        </p>

        <Alert
            v-if="errorMessage"
            :title="t('common.error')"
            :content="errorMessage"
            severity="danger"
            class="mb-4"
        />

        <template v-if="loading">
            <Loader />
        </template>

        <template v-else-if="template">
            <form
                novalidate
                @submit.prevent="saveTemplate"
            >
                <div class="flex flex-column gap-5 mb-5">
                    <InputSelect
                        v-model="on_event"
                        name="on_event"
                        :label="t('app.admin.emailTemplates.onEvent')"
                        :placeholder="t('app.admin.emailTemplates.onEventPlaceholder')"
                        disabled
                        :options="emailTemplateOptions"
                        :errors="errors.on_event"
                    />
                    <InputSelect
                        v-model="templateLocale"
                        name="locale"
                        :label="t('app.admin.emailTemplates.locale')"
                        disabled
                        :options="localeOptions"
                    />

                    <InputText
                        v-model="subject"
                        name="subject"
                        :label="t('app.admin.emailTemplates.subject')"
                        :placeholder="t('app.admin.emailTemplates.subjectPlaceholder')"
                        required
                        :errors="errors.subject"
                    />

                    <InputRichText
                        v-model="body"
                        name="body"
                        :label="t('app.admin.emailTemplates.body')"
                        :placeholder="t('app.admin.emailTemplates.bodyPlaceholder')"
                        required
                        :rows="15"
                        :extended="true"
                        :errors="errors.body"
                    />

                    <VariablesLegend
                        v-if="on_event"
                        :variables="availableEmailVariables[on_event as EmailEventType]"
                    >
                        <template #title>
                            <p class="mt-1 mb-3 font-bold text-theme-primary">
                                {{ t('app.admin.emailTemplates.availableVariables') }}
                            </p>
                        </template>
                    </VariablesLegend>
                </div>

                <div class="flex justify-content-center">
                    <Button
                        variant="primary"
                        type="submit"
                        :label="isSubmitting ? t('app.admin.shared.saving') : t('app.admin.shared.saveChanges')"
                        :disabled="!subject || !body || isSubmitting || !noErrors"
                    />
                </div>
            </form>
        </template>

        <template v-else-if="!loading && !template">
            <Alert
                :title="t('app.admin.emailTemplates.notFoundTitle')"
                :content="t('app.admin.emailTemplates.notFound')"
                severity="warning"
            />
        </template>
    </div>
</template>
