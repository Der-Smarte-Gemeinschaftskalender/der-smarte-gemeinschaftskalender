<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { checkLogin } from '@/composables/UserComposoable';
import { useRouter, useRoute } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { ZodType } from 'zod';
import zod from '@/lib/zod';
import Card from '@/components/KERN/Card.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import InputPassword from '@/components/KERN/inputs/InputPassword.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';
import InputCheckbox from '@/components/KERN/inputs/InputCheckbox.vue';

const router = useRouter();
const route = useRoute();
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
    zod.object({
        email: zod
            .string()
            .nonempty()
            .email()
            .default(import.meta.env.VITE_LOGIN_EMAIL || ''),
        password: zod
            .string()
            .min(6)
            .nonempty()
            .default(import.meta.env.VITE_LOGIN_PASSWORD || ''),
        password_confirmation: zod
            .string()
            .min(6)
            .nonempty()
            .default(import.meta.env.VITE_LOGIN_PASSWORD || ''),
        accept_terms: zod
            .boolean()
            .default(false)
            .refine((val) => val === true, {
                message: 'Die Nutzungsbedingungen müssen akzeptiert werden.',
            }),
    }) satisfies ZodType<RegisterForm>
);
const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});
const { value: email } = useField<string>('email');
const { value: password } = useField<string>('password');
const { value: password_confirmation } = useField<string>('password_confirmation');
const { value: accept_terms } = useField<boolean>('accept_terms');

const onSubmit = handleSubmit(async (values) => {
    try {
        const { data } = await dsgApi.post('/auth/register', <RegisterForm>{
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
                title="Registrieren"
                class="p-2"
            >
                <Fieldset
                    v-if="!showSuccessMessage"
                    class="px-1"
                >
                    <Alert
                        v-if="showRegisterErrorMessage"
                        title="Fehler"
                        :content="registerErrorMessage ?? 'Account konnte nicht erstellt werden.'"
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
                        <InputPassword
                            v-model="password_confirmation"
                            label="Passwort bestätigen"
                            name="password_confirm"
                            :errors="submitCount === 0 ? undefined : errors.password_confirmation"
                        />
                        <InputCheckbox
                            v-model="accept_terms"
                            name="accept_terms"
                            label="Nutzungsbedingungen"
                            :errors="submitCount === 0 ? undefined : errors.accept_terms"
                        >
                            <template #label>
                                Ich habe die
                                <RouterLink
                                    :to="{ name: 'public.terms' }"
                                    class="terms-link"
                                >
                                    Nutzungsbedingungen
                                </RouterLink>
                                gelesen und akzeptiere sie.
                            </template>
                        </InputCheckbox>
                        <Button
                            type="submit"
                            class="mt-3"
                            :disabled="isSubmitting"
                        >
                            Jetzt registrieren
                        </Button>
                    </form>
                </Fieldset>
                <Alert
                    v-else
                    title="Erfolg"
                    content="Dein Account wurde erfolgreich erstellt. Bitte bestätige deine E-Mail-Adresse, um fortzufahren."
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
