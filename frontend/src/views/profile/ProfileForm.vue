<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm, useField } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { setUserLocale, updateProfileData, user } from '@/composables/UserComposoable';
import { dsgApi } from '@/lib/dsgApi';
import { LOCALE_LABELS, localeOptions, toSupportedLocale, type SupportedLocale } from '@/i18n';

import Button from '@/components/KERN/Button.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import Alert from '@/components/KERN/Alert.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';

import { ProfileFormSchema, type ProfileForm } from '@/types/Profile';
import type { AxiosError } from 'axios';

const { t } = useI18n();

const errorMessage = ref<string>('');
const successMessage = ref<string>('');

const { errors, handleSubmit, submitCount, isSubmitting, setFieldValue } = useForm<ProfileForm>({
    validationSchema: toTypedSchema(ProfileFormSchema),
    initialValues: {
        email: user.value.email,
        mobilizon_name: user.value?.person?.name || '',
        // A language disabled on this instance since it was stored is shown (and saved) as the default
        locale: user.value?.locale ? toSupportedLocale(user.value.locale) : undefined,
    },
});

const { value: email } = useField<string>('email');
const { value: mobilizon_name } = useField<string>('mobilizon_name');
// Only used for emails, intentionally does not switch the UI language
const { value: locale } = useField<SupportedLocale>('locale');
const preferredUsername = ref<string>(user.value?.person?.preferredUsername || '');

// Users logged in before the email language existed have no locale in the locally cached user.
// Load the stored one instead of guessing, otherwise saving the form would overwrite it.
// Until it is loaded the field stays empty and is not sent (the backend keeps the stored value).
onMounted(async () => {
    if (user.value?.locale) return;

    try {
        const { data } = await dsgApi.post('/auth/me');
        if (!data?.locale) return;

        // Saving the form while the request was pending already cached the current value
        if (!user.value?.locale) setUserLocale(data.locale);
        // Keep a language the user picked while the request was pending
        if (locale.value === undefined) setFieldValue('locale', toSupportedLocale(data.locale));
    } catch (error) {
        console.error('Error loading the email language:', error);
    }
});

const onSubmit = handleSubmit(async (values) => {
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const { data } = await dsgApi.post('/user/updateProfile', values);

        successMessage.value = data.message;
        updateProfileData(data.user, data.person);
    } catch (error: AxiosError | any) {
        errorMessage.value =
            error.response?.data?.error ||
            t('app.profile.form.updateError');
        console.error('Error updating profile:', error);
    }
});
</script>

<template>
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Fieldset>
            <Alert
                severity="info"
                :title="t('app.profile.form.storedDataTitle')"
            >
                <p>
                    {{ t('app.profile.form.storedDataIntro') }}
                </p>
                <ul class="list-disc ml-5">
                    <li><b>{{ t('app.profile.form.storedName') }}</b> {{ mobilizon_name || '–' }}</li>
                    <li><b>{{ t('app.profile.form.storedUsername') }}</b> {{ preferredUsername || '–' }}</li>
                    <li><b>{{ t('app.profile.form.storedEmail') }}</b> {{ email || '–' }}</li>
                    <li><b>{{ t('app.profile.form.storedEmailLanguage') }}</b> {{ (locale && LOCALE_LABELS[locale]) || '–' }}</li>
                </ul>
                <p class="mt-2">
                    {{ t('app.profile.form.storedDataPurpose') }}
                </p>
            </Alert>
            <Alert
                v-if="errorMessage.length"
                :title="t('common.error')"
                :content="errorMessage"
                severity="danger"
            />
            <Alert
                v-if="successMessage.length"
                :title="t('common.success')"
                :content="successMessage"
                severity="success"
            />
            <div class="flex flex-column gap-5">
                <div class="flex flex-column align-items-center md:flex-row gap-5 md:gap-6">
                    <div class="w-full">
                        <InputText
                            v-model="mobilizon_name"
                            name="mobilizon_name"
                            class="w-full"
                            :label="t('app.profile.form.name')"
                            :errors="submitCount === 0 ? undefined : errors.mobilizon_name"
                        />
                    </div>
                    <div class="col-12 md:col-6 px-0">
                        <InputText
                            v-model="preferredUsername"
                            name="mobilizon_preferred_username"
                            class="w-full"
                            :label="t('app.profile.form.username')"
                            :disabled="true"
                        />
                    </div>
                </div>

                <div class="flex flex-column md:flex-row gap-6">
                    <div class="w-full">
                        <InputEmail
                            v-model="email"
                            name="email"
                            :label="t('app.profile.form.email')"
                            required
                            :errors="submitCount === 0 ? undefined : errors.email"
                        />
                    </div>
                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            :title="t('app.profile.information')"
                            severity="info"
                        >
                            <p>{{ t('app.profile.form.emailInfo') }}</p>
                        </Alert>
                    </div>
                </div>

                <div class="flex flex-column md:flex-row gap-6">
                    <div class="w-full">
                        <InputSelect
                            v-model="locale"
                            name="locale"
                            :label="t('app.profile.form.emailLanguage')"
                            :options="localeOptions"
                            :errors="submitCount === 0 ? undefined : errors.locale"
                        />
                    </div>
                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            :title="t('app.profile.information')"
                            severity="info"
                        >
                            <p>{{ t('app.profile.form.emailLanguageInfo') }}</p>
                        </Alert>
                    </div>
                </div>
            </div>
        </Fieldset>
        <div class="text-center">
            <Button
                variant="primary"
                type="submit"
                class="mt-6"
                :disabled="isSubmitting"
            >
                {{ t('app.profile.form.submit') }}
            </Button>
        </div>
    </form>
</template>
