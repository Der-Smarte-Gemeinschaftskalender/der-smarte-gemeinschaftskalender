<script setup lang="ts">
import { ref } from 'vue';
import zod from '@/lib/zod';

import { dsgApi, checkAuthDsgApi } from '@/lib/dsgApi';
import { setUserData, checkLogin } from '@/composables/UserComposoable';
import { setOrganisationData } from '@/composables/OrganisationComposable';
import { useRouter, useRoute } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { isStrictModeEnabled } from '@/lib/instanceConfig';

import Card from '@/components/KERN/Card.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import InputPassword from '@/components/KERN/inputs/InputPassword.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';

import type { AxiosError } from "axios";

const router = useRouter();
const route = useRoute();
const showLoginErrorMessage = ref(false);
const errorMessageContent = ref('');

interface LoginForm {
    email: string;
    password: string;
}

const validationSchema = toTypedSchema(
    zod.object({
        email: zod
            .string()
            .nonempty('Die E-Mail-Adresse ist erforderlich.')
            .email('Bitte geben Sie eine gültige E-Mail-Adresse ein.')
            .default(import.meta.env.VITE_LOGIN_EMAIL || ''),
        password: zod
            .string()
            .nonempty('Das Passwort ist erforderlich.')
            .trim()
            .default(import.meta.env.VITE_LOGIN_PASSWORD || '')
    })
);
const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});
const { value: email } = useField<string>('email');
const { value: password } = useField<string>('password');

const onSubmit = handleSubmit(async (values) => {
    try {
        const { data } = await dsgApi.post('/auth/login', <LoginForm>{
            ...values,
        });

        setUserData(data.user, data.access_token, data.person);
        checkAuthDsgApi();
        await setOrganisationData();
        if (!data?.person) {
            router.push('/app/profile/register-person');
        } else if (route?.query?.redirect) {
            router.push(route.query.redirect as string);
            return;
        } else {
            router.push('/app');
        }
    } catch (error: any | AxiosError) {
        console.error(error);
        showLoginErrorMessage.value = true;
        if (error.status === 401) {
            errorMessageContent.value = 'E-Mail-Adresse und Passwort sind ungültig.';
        } else if (error.status === 403) {
            errorMessageContent.value = 'Benutzerkonto ist deaktiviert.';
        } else if (error.status === 404) {
            errorMessageContent.value = 'E-Mail-Adresse wurde noch nicht bestätigt.';
        } else {
            errorMessageContent.value = 'Es ist ein Fehler aufgetreten.';
        }
    }
});

if (checkLogin()) {
    router.push('/app');
}
</script>
<template>
    <div class="flex align-self-center align-items-center justify-content-center">
        <div class="login-card">
            <Card
                title="Login"
                class="p-2"
            >
                <Fieldset class="px-1">
                    <Alert
                        v-if="route.query.message"
                        title="Erfolg"
                        :content="route?.query?.message || 'Vorgang wurde erfolgreich durchgeführt.'"
                        severity="success"
                    />
                    <Alert
                        v-if="showLoginErrorMessage"
                        title="Fehler"
                        :content="errorMessageContent"
                        severity="danger"
                    />
                    <form
                        novalidate
                        @submit.prevent="onSubmit"
                    >
                        <InputEmail
                            v-model="email"
                            label="E-Mail-Adresse"
                            name="email"
                            required
                            :errors="submitCount === 0 ? undefined : errors.email"
                        />
                        <InputPassword
                            v-model="password"
                            label="Passwort"
                            name="password"
                            :errors="submitCount === 0 ? undefined : errors.password"
                        />
                        <Button
                            type="submit"
                            class="mt-3"
                            :disabled="isSubmitting"
                        >
                            Einloggen
                        </Button>
                    </form>
                </Fieldset>
                <div class="mt-3 text-sm">
                    <RouterLink :to="{ name: 'register' }">Noch kein Konto? Jetzt registrieren!</RouterLink>
                </div>
                <div
                    v-if="isStrictModeEnabled" 
                    class="mt-2 text-sm"
                >
                    <RouterLink :to="{ name: 'registerWithProfileAndOrganisation' }">Neues Konto mit Organisation anlegen!</RouterLink>
                </div>
                <div class="mt-2 text-sm">
                    <RouterLink :to="{ name: 'forgotPassword' }">Passwort vergessen?</RouterLink>
                </div>
            </Card>
        </div>
    </div>
</template>
<style lang="scss">
.login-card {
    max-width: 685px !important;
}
</style>
