<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
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
import type { MobilizonUser } from '@/types/Mobilizon';

const { t } = useI18n();
const router = useRouter();
const route = useRoute();
const showLoginErrorMessage = ref(false);
const errorMessageContent = ref('');

// Pages redirecting to the login can show one of these messages via ?message=<key>. Only known
// keys are translated, the query parameter must not be able to inject arbitrary text.
const LOGIN_MESSAGE_KEYS = ['auth.validate.success'];
const loginMessage = computed(() => {
    const key = route.query.message;
    return typeof key === 'string' && LOGIN_MESSAGE_KEYS.includes(key) ? t(key) : t('auth.login.successDefault');
});

interface LoginForm {
    email: string;
    password: string;
}

const validationSchema = toTypedSchema(
    zod.object({
        email: zod
            .string()
            .nonempty('validation.email.required')
            .email('validation.email.invalid')
            .default(import.meta.env.VITE_LOGIN_EMAIL || ''),
        password: zod
            .string()
            .nonempty('validation.password.required')
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
        const { data } = await dsgApi.post<MobilizonUser>('/auth/login', <LoginForm>{
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
            errorMessageContent.value = t('auth.login.invalidCredentials');
        } else if (error.status === 403) {
            errorMessageContent.value = t('auth.login.accountDisabled');
        } else if (error.status === 404) {
            errorMessageContent.value = t('auth.login.emailNotVerified');
        } else {
            errorMessageContent.value = t('auth.errorOccurred');
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
                :title="t('auth.login.title')"
                class="p-2"
            >
                <Fieldset class="px-1">
                    <Alert
                        v-if="route.query.message"
                        :title="t('common.success')"
                        :content="loginMessage"
                        severity="success"
                    />
                    <Alert
                        v-if="showLoginErrorMessage"
                        :title="t('common.error')"
                        :content="errorMessageContent"
                        severity="danger"
                    />
                    <form
                        novalidate
                        @submit.prevent="onSubmit"
                    >
                        <InputEmail
                            v-model="email"
                            :label="t('auth.email')"
                            name="email"
                            required
                            :errors="submitCount === 0 ? undefined : errors.email"
                        />
                        <InputPassword
                            v-model="password"
                            :label="t('auth.password')"
                            name="password"
                            :errors="submitCount === 0 ? undefined : errors.password"
                        />
                        <Button
                            type="submit"
                            class="mt-3"
                            :disabled="isSubmitting"
                        >
                            {{ t('auth.login.submit') }}
                        </Button>
                    </form>
                </Fieldset>
                <div class="mt-3 text-sm">
                    <RouterLink :to="{ name: 'register' }">{{ t('auth.login.noAccount') }}</RouterLink>
                </div>
                <div
                    v-if="isStrictModeEnabled" 
                    class="mt-2 text-sm"
                >
                    <RouterLink :to="{ name: 'registerWithProfileAndOrganisation' }">{{ t('auth.login.registerWithOrganisation') }}</RouterLink>
                </div>
                <div class="mt-2 text-sm">
                    <RouterLink :to="{ name: 'forgotPassword' }">{{ t('auth.login.forgotPassword') }}</RouterLink>
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
