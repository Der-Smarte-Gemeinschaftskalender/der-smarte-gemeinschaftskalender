<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm, useField } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { dsgApi } from '@/lib/dsgApi';

import Alert from '@/components/KERN/Alert.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import Button from '@/components/KERN/Button.vue';
import InputPassword from '@/components/KERN/inputs/InputPassword.vue';

import { PasswordFormSchema, type PasswordForm } from '@/types/Profile';
import type { AxiosError } from 'axios';

const { t } = useI18n();

const errorMessage = ref<string>('');
const successMessage = ref<string>('');

const { errors, handleSubmit, isSubmitting, submitCount } = useForm<PasswordForm>({
    validationSchema: toTypedSchema(PasswordFormSchema),
});

const { value: current_password } = useField<string>('current_password');
const { value: new_password } = useField<string>('new_password');
const { value: new_password_confirm } = useField<string>('new_password_confirm');

const resetPassword = handleSubmit(async (values) => {
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const { data } = await dsgApi.post('/user/updatePassword', values);
        console.log(data);
        successMessage.value = data.message;
    } catch (error: AxiosError | any) {
        errorMessage.value =
            error.response?.data?.error ||
            t('app.profile.password.error');
        console.error('Error resetting password:', error);
    }
});
</script>

<template>
    <form
        novalidate
        class="mt-6"
        @submit.prevent="resetPassword"
    >
        <h3 class="kern-heading text-theme-primary mb-3">{{ t('app.profile.password.title') }}</h3>
        <Fieldset>
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
                <InputPassword
                    v-model="current_password"
                    name="current_password"
                    type="password"
                    class="col-12 md:col-6 md:pr-4"
                    :label="t('app.profile.password.current')"
                    required
                    :errors="submitCount === 0 ? undefined : errors.current_password"
                />

                <div class="flex flex-column md:flex-row gap-6">
                    <InputPassword
                        v-model="new_password"
                        name="new_password"
                        type="password"
                        class="w-full"
                        :label="t('app.profile.password.new')"
                        required
                        :errors="submitCount === 0 ? undefined : errors.new_password"
                    />

                    <InputPassword
                        v-model="new_password_confirm"
                        name="new_password_confirm"
                        type="password"
                        class="w-full"
                        :label="t('app.profile.password.confirm')"
                        required
                        :errors="submitCount === 0 ? undefined : errors.new_password_confirm"
                    />
                </div>
            </div>
        </Fieldset>
        <div class="text-center">
            <Button
                type="submit"
                class="mt-6"
                :disabled="isSubmitting"
            >
                {{ t('app.profile.password.submit') }}
            </Button>
        </div>
    </form>
</template>
