<script lang="ts" setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { dsgApi } from '@/lib/dsgApi';
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

const route = useRoute();
const router = useRouter();

const template = ref<EmailTemplate | null>(null);
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
        subject.value = template.value.subject;
        body.value = template.value.body;
    } catch {
        errorMessage.value = 'Fehler beim Laden der Vorlage.';
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
                : 'Fehler beim Speichern der Vorlage.';
        } else {
            errorMessage.value = 'Fehler beim Speichern der Vorlage.';
        }
    }
});

onMounted(loadTemplate);
</script>

<template>
    <div>
        <h1 class="kern-heading text-theme-primary mb-4">E-Mail-Vorlage bearbeiten</h1>
        <p class="mt-1 mb-6">
            <b>Hinweis:</b>
            Weitere Informationen finden Sie im
            <LinkToDocs
                path="Terminverwaltung/Einzeltermine/"
                fragment="kalender-integrieren-ical-url-anbinden"
            />.
        </p>

        <Alert
            v-if="errorMessage"
            title="Fehler"
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
                        label="Bei welchem Ereignis"
                        placeholder="Ereignis auswählen"
                        disabled
                        :options="emailTemplateOptions"
                        :errors="errors.on_event"
                    />

                    <InputText
                        v-model="subject"
                        name="subject"
                        label="Betreff"
                        placeholder="E-Mail Betreff"
                        required
                        :errors="errors.subject"
                    />

                    <InputRichText
                        v-model="body"
                        name="body"
                        label="Nachricht"
                        placeholder="E-Mail Inhalt"
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
                                Verfügbare Variablen für Betreff und Nachricht:
                            </p>
                        </template>
                    </VariablesLegend>
                </div>

                <div class="flex justify-content-center">
                    <Button
                        variant="primary"
                        type="submit"
                        :label="isSubmitting ? 'Speichern...' : 'Änderungen speichern'"
                        :disabled="!subject || !body || isSubmitting || !noErrors"
                    />
                </div>
            </form>
        </template>

        <template v-else-if="!loading && !template">
            <Alert
                title="Nicht gefunden"
                content="Die angeforderte E-Mail-Vorlage wurde nicht gefunden."
                severity="warning"
            />
        </template>
    </div>
</template>
