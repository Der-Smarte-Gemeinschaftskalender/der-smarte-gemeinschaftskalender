<script setup lang="ts">
import { ref } from 'vue';
import { useForm, useField } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { updateProfileData, user } from '@/composables/UserComposoable';
import { dsgApi } from '@/lib/dsgApi';

import Button from '@/components/KERN/Button.vue';
import InputText from '@/components/KERN/inputs/InputText.vue';
import Alert from '@/components/KERN/Alert.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';

import { ProfileFormSchema, type ProfileForm } from '@/types/Profile';
import type { AxiosError } from 'axios';

const errorMessage = ref<string>('');
const successMessage = ref<string>('');

const { errors, handleSubmit, submitCount, isSubmitting } = useForm<ProfileForm>({
    validationSchema: toTypedSchema(ProfileFormSchema),
    initialValues: {
        email: user.value.email,
        mobilizon_name: user.value?.person?.name || '',
    },
});

const { value: email } = useField<string>('email');
const { value: mobilizon_name } = useField<string>('mobilizon_name');
const preferredUsername = ref<string>(user.value?.person?.preferredUsername || '');

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
            'Fehler beim Aktualisieren des Profils. Bitte versuchen Sie es später erneut.';
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
                title="Gespeicherte personenbezogene Daten"
            >
                <p>
                    Wir speichern zu deinem Benutzerkonto die folgenden personenbezogenen Daten:
                </p>
                <ul class="list-disc ml-5">
                    <li><b>Name:</b> {{ mobilizon_name || '–' }}</li>
                    <li><b>Benutzername:</b> {{ preferredUsername || '–' }}</li>
                    <li><b>E-Mail-Adresse:</b> {{ email || '–' }}</li>
                </ul>
                <p class="mt-2">
                    Diese Daten werden ausschließlich für die Nutzung deines Kontos und die Anmeldung benötigt.
                </p>
            </Alert>
            <Alert
                v-if="errorMessage.length"
                title="Fehler"
                :content="errorMessage"
                severity="danger"
            />
            <Alert
                v-if="successMessage.length"
                title="Erfolg"
                :content="successMessage"
                severity="success"
            />
            <div class="flex flex-column gap-5">
                <div class="flex flex-column align-items-center md:flex-row gap-5 md:gap-6">
                    <div class="w-full">
                        <InputText
                            name="mobilizon_name"
                            class="w-full"
                            v-model="mobilizon_name"
                            label="Name"
                            :errors="submitCount === 0 ? undefined : errors.mobilizon_name"
                        />
                    </div>
                    <div class="col-12 md:col-6 px-0">
                        <InputText
                            name="mobilizon_preferred_username"
                            class="w-full"
                            v-model="preferredUsername"
                            label="Benutzername"
                            :disabled="true"
                        />
                    </div>
                </div>

                <div class="flex flex-column md:flex-row gap-6">
                    <div class="w-full">
                        <InputEmail
                            name="email"
                            v-model="email"
                            label="E-Mail Adresse"
                            required
                            :errors="submitCount === 0 ? undefined : errors.email"
                        />
                    </div>
                    <div class="md:col-6 px-0 mt-2">
                        <Alert
                            title="Information"
                            severity="info"
                        >
                            <p>Diese E-Mail-Adresse benötigen Sie für den Login.</p>
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
                Änderungen Speichern
            </Button>
        </div>
    </form>
</template>
