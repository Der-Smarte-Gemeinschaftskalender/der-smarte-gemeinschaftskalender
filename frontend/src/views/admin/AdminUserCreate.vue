<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRouter } from 'vue-router';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import zod from '@/lib/zod';
import { ZodType } from 'zod';
import { user_types_options, user_is_active_options } from '@/lib/const';
import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import InputPassword from '@/components/KERN/inputs/InputPassword.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import Alert from '@/components/KERN/Alert.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { UserView } from '@/types/User';
import type { AxiosError } from 'axios';

const router = useRouter();
const showErrorMessage = ref<boolean>(false);
const errorMessageContent = ref<string | undefined>(undefined);

const validationSchema = toTypedSchema(
    zod.object({
        email: zod.string({
                required_error: 'Die E-Mail-Adresse ist erforderlich.',
            })
            .email({
                message: 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            }),
        password: zod
            .string({
                required_error: 'Das Passwort ist erforderlich.'
            })
            .min(8, 'Das Passwort muss mindestens 8 Zeichen lang sein.')
            .refine((val) => val.trim().length > 0, {
                message: 'Das Passwort darf nicht leer sein.'
            }),
        type: zod
            .string()
            .nonempty()
            .default('user'),
    }) satisfies ZodType<UserView>
);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});
const { value: email } = useField<string>('email');
const { value: password } = useField<string>('password');
const { value: type } = useField<string>('type');

const onSubmit = handleSubmit(async (values) => {
    try {
        const { data } = await dsgApi.post('/admin/user', <UserView>{
            ...values,
        });

        router.push({ name: 'admin.user.edit', params: { id: data.id } });
    } catch (error: any | AxiosError) {
        showErrorMessage.value = true;
        console.error(error);
        errorMessageContent.value = error?.response?.data?.error || 'Benutzer konnte nicht gespeichert werden.';
    }
});
</script>
<template>
    <h1 class="kern-heading text-theme-primary">Neuen Benutzer anlegen</h1>
    <p class="mb-4">
        <b>Hinweis:</b>
        Als Administrator*in können Sie hier manuell neue Nutzer*innen für Ihre Instanz anlegen.
        <LinkToDocs
            path="Terminverwaltung/Instanz/"
            fragment="nutzer-innen-verwalten"
        />
    </p>
    <Alert
        v-if="showErrorMessage"
        title="Fehler"
        :content="errorMessageContent"
        severity="danger"
    />
    <form
        novalidate
        @submit.prevent="onSubmit"
    >
        <Fieldset>
            <InputEmail
                v-model="email"
                label="E-Mail"
                name="email"
                :errors="submitCount === 0 ? undefined : errors.email"
            />
            <InputPassword
                v-model="password"
                label="Passwort"
                name="password"
                :errors="submitCount === 0 ? undefined : errors.password"
            />
            <InputSelect
                v-model="type"
                label="Typ"
                name="type"
                :options="user_types_options"
                :errors="submitCount === 0 ? undefined : errors.type"
            />
        </Fieldset>
        <Button
            type="submit"
            :disabled="isSubmitting"
            class="mt-2"
        >
            Benutzer anlegen
        </Button>
    </form>
</template>
