<script setup lang="ts">
import { ref } from 'vue';
import { dsgApi } from '@/lib/dsgApi';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useField, useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import zod from '@/lib/zod';
import { ZodType } from 'zod';
import { user_types_options, user_is_active_options } from '@/lib/const';
import { localeOptions, SUPPORTED_LOCALES } from '@/i18n';
import Button from '@/components/KERN/Button.vue';
import Fieldset from '@/components/KERN/Fieldset.vue';
import InputEmail from '@/components/KERN/inputs/InputEmail.vue';
import InputPassword from '@/components/KERN/inputs/InputPassword.vue';
import InputSelect from '@/components/KERN/inputs/InputSelect.vue';
import Alert from '@/components/KERN/Alert.vue';
import LinkToDocs from '@/components/LinkToDocs.vue';

import type { UserView } from '@/types/User';
import type { AxiosError } from 'axios';

const { t } = useI18n();
const router = useRouter();
const showErrorMessage = ref<boolean>(false);
const errorMessageContent = ref<string | undefined>(undefined);

const validationSchema = toTypedSchema(
    zod.object({
        email: zod.string({
                required_error: 'app.admin.users.validation.emailRequired',
            })
            .email({
                message: 'app.admin.users.validation.emailInvalid',
            }),
        password: zod
            .string({
                required_error: 'app.admin.users.validation.passwordRequired'
            })
            .min(8, 'app.admin.users.validation.passwordMin')
            .refine((val) => val.trim().length > 0, {
                message: 'app.admin.users.validation.passwordEmpty'
            }),
        type: zod
            .string()
            .nonempty()
            .default('user'),
        locale: zod.enum(SUPPORTED_LOCALES).default('de'),
    }) satisfies ZodType<UserView>
);

const { handleSubmit, errors, isSubmitting, submitCount } = useForm({
    validationSchema,
    initialValues: { locale: 'de' },
});
const { value: email } = useField<string>('email');
const { value: password } = useField<string>('password');
const { value: type } = useField<string>('type');
const { value: locale } = useField<string>('locale');

const onSubmit = handleSubmit(async (values) => {
    try {
        const { data } = await dsgApi.post('/admin/user', <UserView>{
            ...values,
        });

        router.push({ name: 'admin.user.edit', params: { id: data.id } });
    } catch (error: any | AxiosError) {
        showErrorMessage.value = true;
        console.error(error);
        errorMessageContent.value = error?.response?.data?.error || t('app.admin.users.saveError');
    }
});
</script>
<template>
    <h1 class="kern-heading text-theme-primary">{{ t('app.admin.users.createTitle') }}</h1>
    <p class="mb-4">
        <b>{{ t('app.admin.shared.note') }}</b>
        {{ t('app.admin.users.createHint') }}
        <LinkToDocs
            path="Terminverwaltung/Instanz/"
            fragment="nutzer-innen-verwalten"
        />
    </p>
    <Alert
        v-if="showErrorMessage"
        :title="t('common.error')"
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
                :label="t('app.admin.users.columns.email')"
                name="email"
                :errors="submitCount === 0 ? undefined : errors.email"
            />
            <InputPassword
                v-model="password"
                :label="t('app.admin.users.password')"
                name="password"
                :errors="submitCount === 0 ? undefined : errors.password"
            />
            <InputSelect
                v-model="type"
                :label="t('app.admin.users.type')"
                name="type"
                :options="user_types_options"
                :errors="submitCount === 0 ? undefined : errors.type"
            />
            <InputSelect
                v-model="locale"
                :label="t('app.admin.users.emailLanguage')"
                name="locale"
                :options="localeOptions"
                :errors="submitCount === 0 ? undefined : errors.locale"
            />
        </Fieldset>
        <Button
            type="submit"
            :disabled="isSubmitting"
            class="mt-2"
        >
            {{ t('app.admin.users.createSubmit') }}
        </Button>
    </form>
</template>
