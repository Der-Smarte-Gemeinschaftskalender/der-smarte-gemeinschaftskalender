<script setup lang="ts">
import zod from '@/lib/zod';
import { ref } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';
import { dsgApi } from '@/lib/dsgApi';
import { checkLogin } from '@/composables/UserComposoable';
import { useRouter } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';

import Card from '@/components/KERN/Card.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import InputPassword from '@/components/KERN/inputs/InputPassword.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';

const { t } = useI18n();
const router = useRouter();
const showRegisterErrorMessage = ref(false);
const registerErrorMessage = ref('');
const showSuccessMessage = ref(false);

interface RegisterForm {
    email: string;
    password: string;
    password_confirmation: string;
    accept_terms: boolean;
}
const validationSchema = toTypedSchema(
    zod
        .object({
            email: zod
                .string({
                    required_error: 'auth.validation.emailRequired',
                })
                .nonempty('auth.validation.emailRequired')
                .email('auth.validation.emailInvalid'),
            password: zod
                .string({
                    required_error: 'auth.validation.passwordRequired',
                })
                .nonempty('auth.validation.passwordRequired')
                .min(8, 'auth.validation.newPasswordMin8')
                .trim(),
            password_confirmation: zod
                .string({
                    required_error: 'auth.validation.passwordConfirmationRequired',
                })
                .nonempty('auth.validation.passwordRequired')
                .min(8, 'auth.validation.newPasswordMin8')
                .trim()
                .default(import.meta.env.VITE_LOGIN_PASSWORD || ''),
            accept_terms: zod
                .boolean()
                .refine((val) => val, {
                    message: 'auth.validation.acceptTerms',
                })
                .default(false),
        })
        .superRefine(({ password, password_confirmation }, ctx) => {
            if (password !== password_confirmation) {
                ctx.addIssue({
                    path: ['password_confirmation'],
                    code: zod.ZodIssueCode.custom,
                    message: 'auth.validation.passwordsDoNotMatch',
                });
            }
        })
);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
    initialValues: {
        email: import.meta.env.VITE_LOGIN_EMAIL || '',
        password: import.meta.env.VITE_LOGIN_PASSWORD || '',
        password_confirmation: import.meta.env.VITE_LOGIN_PASSWORD || '',
        accept_terms: false,
    },
});
const { value: email } = useField<string>('email');
const { value: password } = useField<string>('password');
const { value: password_confirmation } = useField<string>('password_confirmation');
const { value: accept_terms } = useField<boolean>('accept_terms');

const onSubmit = handleSubmit(async (values) => {
    try {
        await dsgApi.post('/auth/register', <RegisterForm>{
            ...values,
        });
        showSuccessMessage.value = true;
    } catch (error) {
        console.error(error);
        showRegisterErrorMessage.value = true;
        registerErrorMessage.value = error?.response?.data?.message;
    }
});

if (checkLogin()) {
    router.push('/app');
}
</script>
<template>
    <div class="flex align-self-center align-items-center justify-content-center">
        <div class="register-card">
            <Card
                :title="t('auth.register.title')"
                class="p-2"
            >
                <Fieldset
                    v-if="!showSuccessMessage"
                    class="px-1"
                >
                    <Alert
                        v-if="showRegisterErrorMessage"
                        :title="t('common.error')"
                        :content="registerErrorMessage ?? t('auth.register.error')"
                        severity="danger"
                    />
                    <form
                        novalidate
                        class="flex flex-column gap-2"
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
                        <InputPassword
                            v-model="password_confirmation"
                            :label="t('auth.passwordConfirm')"
                            name="password_confirmation"
                            :errors="submitCount === 0 ? undefined : errors.password_confirmation"
                        />
                        <InputCheckbox
                            v-model="accept_terms"
                            name="accept_terms"
                            :label="t('auth.terms.label')"
                            :errors="submitCount === 0 ? undefined : errors.accept_terms"
                        >
                            <template #label>
                                <I18nT scope="global" keypath="auth.terms.text">
                                    <template #terms>
                                        <RouterLink
                                            :to="{ name: 'public.terms' }"
                                            class="terms-link"
                                        >
                                            {{ t('auth.terms.terms') }}
                                        </RouterLink>
                                    </template>
                                    <template #privacy>
                                        <RouterLink
                                            :to="{ name: 'public.privacy' }"
                                            class="terms-link"
                                        >
                                            {{ t('auth.terms.privacy') }}
                                        </RouterLink>
                                    </template>
                                </I18nT>
                            </template>
                        </InputCheckbox>
                        <Button
                            type="submit"
                            class="mt-3"
                            :disabled="isSubmitting"
                        >
                            {{ t('auth.register.submit') }}
                        </Button>
                    </form>
                </Fieldset>
                <Alert
                    v-else
                    :title="t('common.success')"
                    :content="t('auth.register.success')"
                    severity="success"
                />
            </Card>
        </div>
    </div>
</template>
<style lang="scss">
.register-card {
    max-width: 685px !important;
}
.terms-link {
    color: #111;
    text-decoration: underline;
}
</style>
