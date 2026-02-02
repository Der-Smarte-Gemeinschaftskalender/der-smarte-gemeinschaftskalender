<script setup lang="ts">
import { ref } from 'vue';
import zod from '@/lib/zod';

import { useRoute, useRouter } from 'vue-router';
import { useForm, useField } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { type AxiosError, dsgApi } from '@/lib/dsgApi';

import Card from '@/components/KERN/Card.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputPassword from '@/components/KERN/inputs/InputPassword.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';

const route = useRoute();

const showErrorMessage = ref(false);
const showSuccessMessage = ref(false);
const messageContent = ref('');
const token = ref(route.path.split('/').at(-1) || '');

interface ResetPasswordForm {
  token: string;
  password: string;
  password_confirmation: string;
}

const validationSchema = toTypedSchema(
    zod.object({
      password: zod.string().min(6, 'Das Passwort muss mindestens 6 Zeichen haben.'),
      password_confirmation: zod.string().min(6, 'Bitte Passwortbestätigung eingeben.'),
    })
        .refine((data) => data.password === data.password_confirmation, {
          message: 'Passwörter stimmen nicht überein.',
          path: ['password_confirmation'],
        })
);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});


const { value: password } = useField<string>('password');
const { value: password_confirmation } = useField<string>('password_confirmation');

const onSubmit = handleSubmit(async (values) => {
    try {
        const response = await dsgApi.post('/auth/reset-password', {
            ...values,
            token: token.value,
        } as ResetPasswordForm);

        showSuccessMessage.value = true;
        messageContent.value = response.data.message;
    }
    catch (error: AxiosError|any) {
        console.error(error);
        showErrorMessage.value = true;
        messageContent.value = error.response?.data?.error || 'Es ist ein Fehler aufgetreten.';
    }
});
</script>

<template>
    <div class="flex align-self-center align-items-center justify-content-center">
        <div class="reset-password-card">
            <Card title="Passwort zurücksetzen">
                <Fieldset>
                    <Alert
                        v-if="showSuccessMessage"
                        title="Erfolg"
                        :content="messageContent"
                        severity="success"
                    />
                    <Alert
                        v-if="showErrorMessage"
                        title="Fehler"
                        :content="messageContent"
                        severity="danger"
                    />

                    <form
                        v-if="!showSuccessMessage"
                        novalidate
                        @submit.prevent="onSubmit"
                    >
                        <InputPassword
                            v-model="password"
                            label="Neues Passwort"
                            name="password"
                            required
                            :errors="submitCount === 0 ? undefined : errors.password"
                        />

                        <InputPassword
                            v-model="password_confirmation"
                            label="Passwort bestätigen"
                            name="password_confirmation"
                            required
                            :errors="submitCount === 0 ? undefined : errors.password_confirmation"
                        />

                        <Button type="submit" class="mt-3" :disabled="isSubmitting">
                            Passwort speichern
                        </Button>
                    </form>
                </Fieldset>

                <div class="text-center mt-3">
                    <RouterLink :to="{ name: 'login' }">Zurück zum Login</RouterLink>
                </div>
            </Card>
        </div>
    </div>
</template>

<style scoped lang="scss">
.reset-password-card {
  max-width: 685px !important;
}
</style>
