<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import zod from '@/lib/zod';

import { useRouter } from 'vue-router';
import { type AxiosError, dsgApi } from '@/lib/dsgApi';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';

import Card from '@/components/KERN/Card.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import Button from '@/components/KERN/Button.vue';
import Alert from '@/components/KERN/Alert.vue';

import { ZodType } from 'zod';

const { t } = useI18n();
const router = useRouter();
const showErrorMessage = ref(false);
const showSuccessMessage = ref(false);
const messageContent = ref('');

interface ForgotPasswordForm {
  email: string;
}

const validationSchema = toTypedSchema(
    zod.object({
        email: zod
            .string({
                required_error: 'auth.validation.emailRequired',
            })
            .nonempty('auth.validation.emailRequired')
            .email('auth.validation.emailInvalid')
            .default(import.meta.env.VITE_LOGIN_EMAIL || ''),
    }) satisfies ZodType<ForgotPasswordForm>
);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
});

const { value: email } = useField<string>('email');

const onSubmit = handleSubmit(async (values) => {
    try {
        const resp = await dsgApi.post('/auth/forgot-password', <ForgotPasswordForm>{
            ...values,
        });

        showSuccessMessage.value = true;
        showErrorMessage.value = false;
        messageContent.value = resp.data.message;
    }
    catch (error: AxiosError|any) {
        console.error(error);
        showErrorMessage.value = true;
        showSuccessMessage.value = false;
        messageContent.value = error.response?.data?.message || t('auth.errorOccurred')
    }
});
</script>

<template>
  <div class="flex align-self-center align-items-center justify-content-center">
    <div class="forgot-password-card">
      <Card
          :title="t('auth.forgotPassword.title')"
          class="p-2"
      >
        <Fieldset class="px-1">
          <Alert
              v-if="showSuccessMessage"
              :title="t('common.success')"
              :content="messageContent"
              severity="success"
          />
          <Alert
              v-if="showErrorMessage"
              :title="t('common.error')"
              :content="messageContent"
              severity="danger"
          />
          <form
              v-if="!showSuccessMessage"
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
            <Button
                type="submit"
                class="mt-3"
                :disabled="isSubmitting"
            >
              {{ t('auth.forgotPassword.submit') }}
            </Button>
          </form>
        </Fieldset>
        <div class="text-sm mt-3">
          <RouterLink :to="{ name: 'login' }">{{ t('auth.backToLogin') }}</RouterLink>
        </div>
      </Card>
    </div>
  </div>
</template>

<style lang="scss">
.forgot-password-card {
  max-width: 685px !important;
}
</style>
