<script lang="ts" setup>
import { ref, watch } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import zod from '@/lib/zod';
import { useRouter } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { dsgApi } from '@/lib/dsgApi';
import { isStrictModeEnabled } from '@/lib/instanceConfig';
import { stripHtml, convertToUsername, preferredUsernameSchema } from '@/lib/helper';

import Fieldset from '@/components/KERN/Fieldset.vue';
import InputText from '@/components/KERN/inputs/InputEmail.vue';
import InputRichText from '@/components/KERN/inputs/InputRichText.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { ZodType } from 'zod';
import type { AxiosError } from 'axios';


const { t } = useI18n();
const router = useRouter();
const showErrorMessage = ref(false);
const errorMessageContent = ref('');

interface RequestOrganisationForm {
    preferredUsername: string;
    name: string;
    summary: string;
}

const validationSchema = toTypedSchema(
    zod.object({
        name: zod
            .string({
                required_error: 'app.organisations.validation.nameRequired',
            })
            .nonempty('app.organisations.validation.nameNotEmpty'),
        preferredUsername: preferredUsernameSchema,
        summary: zod
            .string({
                required_error: 'app.organisations.validation.summaryRequired',
            })
            .refine((val) => stripHtml(val).length > 0, { message: 'app.organisations.validation.summaryNotEmpty' })
    }) satisfies ZodType<RequestOrganisationForm>
);
const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});
const { value: name } = useField<string>('name');
const { value: preferredUsername } = useField<string>('preferredUsername');
const { value: summary } = useField<string>('summary');

const onSubmit = handleSubmit(async (values) => {
    try {
        await dsgApi.post('/organisations/requestOrganisationCreation', <RequestOrganisationForm>{
            ...values,
        });

        await router.push({
            name: 'app.myOrganisations',
            query: isStrictModeEnabled ? { requestSent: 'true' } : {},
        });
    } catch (error: any | AxiosError) {
        showErrorMessage.value = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        errorMessageContent.value = error?.response?.data?.error || t('app.organisations.errorOccurred');
    }
});
watch(name, async (newName) => {
    preferredUsername.value = convertToUsername(newName);
});
</script>
<template>
    <h1 class="kern-heading text-theme-primary">{{ t('app.organisations.request.title') }}</h1>
    <p class="mt-1 mb-6">
        <b>{{ t('app.organisations.hint') }}</b> <I18nT scope="global" keypath="app.organisations.request.hint">
            <template #docs>
                <LinkToDocs
                    path="Terminverwaltung/Organisation/"
                    fragment="organisation-erstellen-zugang-fur-veranstalter-innen"
                />
            </template>
        </I18nT>
    </p>
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Alert
            v-if="showErrorMessage"
            class="mb-4"
            :title="t('common.error')"
            :content="errorMessageContent || t('app.organisations.errorOccurred')"
            severity="danger"
        />

        <Fieldset>
            <div class="flex flex-column gap-5">
                <div class="flex flex-column md:flex-row gap-6">
                    <InputText
                        v-model="name"
                        class="w-full"
                        :label="t('app.organisations.request.name')"
                        name="name"
                        :errors="submitCount === 0 ? undefined : errors.name"
                    />
                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            :title="t('app.organisations.information')"
                            severity="info"
                        >
                            <p>
                                {{ t('app.organisations.request.nameInfo') }}
                            </p>
                        </Alert>
                    </div>
                </div>
                <div class="flex flex-column md:flex-row gap-6">
                    <InputText
                        v-model="preferredUsername"
                        class="w-full"
                        :label="t('app.organisations.request.username')"
                        name="preferredUsername"
                        :errors="submitCount === 0 ? undefined : errors.preferredUsername"
                    />

                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            :title="t('app.organisations.information')"
                            severity="info"
                        >
                            <p>
                                {{ t('app.organisations.request.usernameInfo') }}
                            </p>
                            <p>
                                <I18nT scope="global" keypath="app.organisations.request.usernameMoreInfo">
                                    <template #docs>
                                        <LinkToDocs
                                            path="Terminverwaltung/Organisation/"
                                            fragment="schritt-2-formular-zur-organisationserstellung"
                                        />
                                    </template>
                                </I18nT>
                            </p>
                        </Alert>
                    </div>
                </div>

                <InputRichText
                    v-model="summary"
                    :label="t('app.organisations.request.summary')"
                    name="summary"
                    :errors="submitCount === 0 ? undefined : errors.summary"
                />
            </div>
        </Fieldset>

        <div class="flex justify-content-center mt-5">
            <Button
                :disabled="isSubmitting"
                type="submit"
                icon-left="add"
            >
                {{ t('app.organisations.request.submit') }}
            </Button>
        </div>
    </form>
</template>
